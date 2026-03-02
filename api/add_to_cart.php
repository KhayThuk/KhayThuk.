<?php
require __DIR__ . "/../config.php";
ensure_cart();

$T_PRODUCTS = "`Products`";

$input = json_decode(file_get_contents("php://input"), true) ?: [];
$barcode = trim((string)($input['barcode'] ?? ''));

if ($barcode === '') json_out(['ok'=>false,'error'=>'กรุณาใส่บาร์โค้ด']);

$stmt = $pdo->prepare("SELECT id, barcode, name, sell_price FROM {$T_PRODUCTS} WHERE barcode = :b LIMIT 1");
$stmt->execute([':b'=>$barcode]);
$p = $stmt->fetch();

if (!$p) json_out(['ok'=>false,'error'=>'ไม่พบสินค้า: ' . $barcode]);

$pid = (int)$p['id'];
if (!isset($_SESSION['cart'][$pid])) {
  $_SESSION['cart'][$pid] = [
    'product_id'=>$pid,
    'barcode'=>$p['barcode'],
    'name'=>$p['name'],
    'price'=>(float)$p['sell_price'],
    'qty'=>1
  ];
} else {
  $_SESSION['cart'][$pid]['qty'] += 1;
}

json_out(['ok'=>true]);