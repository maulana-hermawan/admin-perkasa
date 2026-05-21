<?php
/**
 * app/core/cache.php
 * File-based cache untuk shared hosting (tanpa APCu/Redis).
 * TTL default 5 menit. Cache disimpan di storage/cache/*.cache
 */

declare(strict_types=1);

define('CACHE_DIR', __DIR__ . '/../../storage/cache');
define('CACHE_EXT', '.cache');

/**
 * Ambil nilai dari cache. Return null jika miss atau expired.
 */
function cache_get(string $key): mixed
{
    $file = _cache_path($key);
    if (!is_file($file)) return null;

    $raw = file_get_contents($file);
    if ($raw === false) return null;

    $data = @unserialize($raw);
    if (!is_array($data) || !isset($data['exp'], $data['val'])) return null;

    // Expired?
    if ($data['exp'] > 0 && $data['exp'] < time()) {
        @unlink($file);
        return null;
    }

    return $data['val'];
}

/**
 * Simpan nilai ke cache.
 *
 * @param string $key   Cache key (alphanumeric + underscore/dash)
 * @param mixed  $value Nilai yang disimpan (serialize-able)
 * @param int    $ttl   Waktu hidup dalam detik (0 = permanent)
 */
function cache_set(string $key, mixed $value, int $ttl = 300): bool
{
    $dir = CACHE_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $data = ['exp' => $ttl > 0 ? time() + $ttl : 0, 'val' => $value];
    return (bool)file_put_contents(_cache_path($key), serialize($data), LOCK_EX);
}

/**
 * Hapus satu key dari cache.
 */
function cache_delete(string $key): void
{
    $file = _cache_path($key);
    if (is_file($file)) @unlink($file);
}

/**
 * Hapus semua cache (semua file .cache di CACHE_DIR).
 */
function cache_flush(): int
{
    $count = 0;
    foreach (glob(CACHE_DIR . '/*' . CACHE_EXT) ?: [] as $f) {
        if (@unlink($f)) $count++;
    }
    return $count;
}

/**
 * Helper: ambil dari cache atau jalankan callback jika miss.
 *
 * @example
 *   $data = cache_remember('dashboard_stats', fn() => expensive_query(), 300);
 */
function cache_remember(string $key, callable $callback, int $ttl = 300): mixed
{
    $val = cache_get($key);
    if ($val !== null) return $val;

    $val = $callback();
    cache_set($key, $val, $ttl);
    return $val;
}

/** @internal */
function _cache_path(string $key): string
{
    // Sanitize key
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    return CACHE_DIR . '/' . $safe . CACHE_EXT;
}
