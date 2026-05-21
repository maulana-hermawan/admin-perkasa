<?php
/**
 * app/lib/TOTP.php
 * RFC 6238 TOTP — pure PHP, no dependencies.
 * Compatible with Google Authenticator, Authy, Microsoft Authenticator.
 */

declare(strict_types=1);

class TOTP
{
    private const STEP    = 30;   // Time window in seconds
    private const DIGITS  = 6;    // OTP digits
    private const ALGO    = 'sha1';
    private const DRIFT   = 1;    // Accept ±1 window (90s tolerance)

    /**
     * Generate a cryptographically random Base32 secret (160-bit).
     */
    public static function generateSecret(): string
    {
        $bytes = random_bytes(20); // 160 bits
        return self::base32Encode($bytes);
    }

    /**
     * Verify a TOTP code against the secret.
     * Accepts DRIFT windows before/after current time.
     */
    public static function verify(string $secret, string $code, int $drift = self::DRIFT): bool
    {
        $code = preg_replace('/\s/', '', $code);
        if (!ctype_digit($code) || strlen($code) !== self::DIGITS) return false;

        $counter = (int)floor(time() / self::STEP);

        for ($i = -$drift; $i <= $drift; $i++) {
            if (hash_equals(self::hotp($secret, $counter + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get current TOTP code (for testing/display).
     */
    public static function getCurrentCode(string $secret): string
    {
        return self::hotp($secret, (int)floor(time() / self::STEP));
    }

    /**
     * Build a otpauth:// URI for QR code generation.
     */
    public static function getUri(string $secret, string $email, string $issuer = 'Perkasa Admin'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::STEP
        );
    }

    /**
     * Generate QR code URL via Google Charts API.
     * No server-side library needed — renders client-side.
     */
    public static function getQRUrl(string $secret, string $email, string $issuer = 'Perkasa Admin'): string
    {
        $uri  = self::getUri($secret, $email, $issuer);
        $size = '200x200';
        return 'https://chart.googleapis.com/chart?chs=' . $size
             . '&chld=M|0&cht=qr&chl=' . rawurlencode($uri);
    }

    // ── Internal ──────────────────────────────────────────────────

    /** HOTP computation (RFC 4226). */
    private static function hotp(string $secret, int $counter): string
    {
        $key   = self::base32Decode($secret);
        $msg   = pack('J', $counter); // big-endian 64-bit
        $hash  = hash_hmac(self::ALGO, $msg, $key, true);
        $offset= ord($hash[-1]) & 0x0F;
        $code  = (
            ((ord($hash[$offset])   & 0x7F) << 24) |
            ((ord($hash[$offset+1]) & 0xFF) << 16) |
            ((ord($hash[$offset+2]) & 0xFF) << 8)  |
             (ord($hash[$offset+3]) & 0xFF)
        ) % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** Base32 encode (RFC 4648). */
    private static function base32Encode(string $bytes): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bin   = '';
        foreach (str_split($bytes) as $b) {
            $bin .= str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bin . str_repeat('0', (8 - strlen($bin) % 8) % 8), 5) as $chunk) {
            $out .= $chars[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    /** Base32 decode. */
    private static function base32Decode(string $base32): string
    {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));
        $bin    = '';
        foreach (str_split($base32) as $c) {
            $pos  = strpos($chars, $c);
            $bin .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split(substr($bin, 0, (int)(strlen($bin) / 8) * 8), 8) as $chunk) {
            $out .= chr(bindec($chunk));
        }
        return $out;
    }
}
