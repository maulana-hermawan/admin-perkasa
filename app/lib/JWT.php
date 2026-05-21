<?php
/**
 * app/lib/JWT.php
 * Minimal JWT (HS256) — pure PHP, no dependencies.
 * Read-only API tokens for external integrations.
 */

declare(strict_types=1);

class JWT
{
    /**
     * Encode payload as JWT.
     * @param array  $payload  Data to encode (exp recommended)
     * @param string $secret   HMAC secret key (at least 32 chars)
     * @param int    $ttl      Token lifetime in seconds (0 = no expiry)
     */
    public static function encode(array $payload, string $secret, int $ttl = 3600): string
    {
        if ($ttl > 0) {
            $payload['iat'] = time();
            $payload['exp'] = time() + $ttl;
        }

        $header = self::b64u(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body   = self::b64u(json_encode($payload));
        $sig    = self::b64u(hash_hmac('sha256', "$header.$body", $secret, true));

        return "$header.$body.$sig";
    }

    /**
     * Decode & verify JWT. Returns payload or throws RuntimeException.
     * @throws RuntimeException on invalid/expired token
     */
    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new RuntimeException('Token format invalid');

        [$header, $body, $sig] = $parts;

        // Verify signature
        $expected = self::b64u(hash_hmac('sha256', "$header.$body", $secret, true));
        if (!hash_equals($expected, $sig)) {
            throw new RuntimeException('Token signature invalid');
        }

        $payload = json_decode(self::b64d($body), true);
        if (!is_array($payload)) throw new RuntimeException('Payload decode failed');

        // Check expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new RuntimeException('Token expired');
        }

        return $payload;
    }

    /** URL-safe base64 encode (no padding). */
    private static function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** URL-safe base64 decode. */
    private static function b64d(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
