<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/* ================= CONFIG ================= */
$uploadDir   = "uploads";
$defaultSkin = "default.png";
$maxSize     = 1024 * 1024; // 1 MB (skin PNG itu kecil)
/* ========================================= */

// Header ringan & aman
header('X-Content-Type-Options: nosniff');

// Pastikan folder uploads ada
$uploadPath = __DIR__ . "/" . $uploadDir;
if (!is_dir($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}

// Hanya POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Validasi file
if (
    empty($_FILES['skin']) ||
    $_FILES['skin']['error'] !== UPLOAD_ERR_OK ||
    $_FILES['skin']['size'] <= 0 ||
    $_FILES['skin']['size'] > $maxSize
) {
    header("Location: index.php");
    exit;
}

$tmp = $_FILES['skin']['tmp_name'];

// Validasi image PNG
$info = @getimagesize($tmp);
if (!$info || $info['mime'] !== 'image/png') {
    header("Location: index.php");
    exit;
}

// Validasi ukuran skin Minecraft (64x64 atau legacy 64x32)
$w = $info[0];
$h = $info[1];
if (!(($w === 64 && $h === 64) || ($w === 64 && $h === 32))) {
    header("Location: index.php");
    exit;
}

// Nama file unik (hindari collision)
$fileName = 'skin_' . time() . '_' . bin2hex(random_bytes(3)) . '.png';
$target   = $uploadPath . "/" . $fileName;

// Atomic move (aman)
if (!move_uploaded_file($tmp, $target)) {
    header("Location: index.php");
    exit;
}

// Permission file aman
@chmod($target, 0644);

// Redirect ke viewer
header("Location: index.php?skin=" . urlencode($fileName));
exit;
