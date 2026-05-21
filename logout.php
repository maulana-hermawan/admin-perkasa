<?php
declare(strict_types=1);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/db.php';
require_once __DIR__ . '/app/core/helpers.php';
require_once __DIR__ . '/app/core/auth.php';

session_bootstrap();
auth_logout();

// Flash setelah session baru dimulai (session_bootstrap dipanggil lagi oleh login.php)
session_start();
flash('info', 'Anda telah berhasil keluar dari sistem.');

redirect('login.php');
