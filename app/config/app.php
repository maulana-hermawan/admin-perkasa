<?php
/**
 * app/config/app.php
 * Konfigurasi aplikasi — dibaca dari .env
 *
 * CATATAN: APP_NAME, APP_URL, APP_DEBUG, APP_ENV sudah didefinisikan
 * di database.php (yang di-require lebih dulu via db.php).
 * Gunakan defined() guard agar tidak ada "Constant already defined" warning.
 */

// Fonnte WhatsApp Gateway
define('FONNTE_TOKEN',      env('FONNTE_TOKEN',      ''));
define('FONNTE_SENDER',     env('FONNTE_SENDER',     ''));     // nomor pengirim (opsional)

// CAT Psikotes
define('CAT_DB_HOST',       env('CAT_DB_HOST',       'localhost'));
define('CAT_DB_USER',       env('CAT_DB_USER',       ''));
define('CAT_DB_PASS',       env('CAT_DB_PASS',       ''));
define('CAT_DB_NAME',       env('CAT_DB_NAME',       ''));
define('CAT_WEBHOOK_SECRET',env('CAT_WEBHOOK_SECRET',''));     // token rahasia untuk autentikasi webhook

// App — guard agar tidak double-define jika database.php sudah di-load duluan
defined('APP_URL')  || define('APP_URL',  rtrim((string)env('APP_URL', ''), '/'));
defined('APP_NAME') || define('APP_NAME', env('APP_NAME', 'Perkasa Mulia Training Center'));

// APP_TIMEZONE adalah alias yang dipakai di app.php saja (database.php pakai APP_TZ)
define('APP_TIMEZONE', env('APP_TIMEZONE', 'Asia/Jakarta'));

// Pastikan timezone benar (database.php sudah set APP_TZ, ini re-affirm jika app.php di-load sendiri)
date_default_timezone_set(APP_TIMEZONE);
