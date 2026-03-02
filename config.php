<?php
declare(strict_types=1);
session_start();

$DB_HOST = "127.0.0.1";
$DB_NAME = "pos_local";
$DB_USER = "root";
$DB_PASS = ""; // XAMPP ปกติว่าง

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Throwable $e) {
  http_response_code(500);
  echo "DB connection failed: " . htmlspecialchars($e->getMessage());
  exit;
}

function money(float $n): string {
  return number_format($n, 2, '.', ',');
}

function ensure_cart(): void {
  if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }
}

function json_out(array $data): void {
  // กัน browser/proxy cache
  header("Content-Type: application/json; charset=utf-8");
  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Pragma: no-cache");

  // ปล่อย session lock ให้เร็ว (กันหน้าเว็บดูเหมือนค้าง)
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }

  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
  define('PROMPTPAY_PHONE', '0828129100'); // 👈 เปลี่ยนเป็นเบอร์ร้านที่ผูกพร้อมเพย์จริง
}