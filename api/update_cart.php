<?php
require __DIR__ . "/../config.php";
ensure_cart();

$input = json_decode(file_get_contents("php://input"), true) ?: [];
$action = (string)($input['action'] ?? 'get');

if ($action === 'clear') {
  $_SESSION['cart'] = [];
}

if ($action === 'set_qty') {
  $pid = (int)($input['product_id'] ?? 0);
  $qty = (int)($input['qty'] ?? 1);
  if ($pid > 0 && isset($_SESSION['cart'][$pid])) {
    $_SESSION['cart'][$pid]['qty'] = max(1, $qty);
  }
}

$cart = array_values($_SESSION['cart']);
$subtotal = 0.0;
foreach ($cart as $it) {
  $subtotal += ((float)$it['price']) * ((int)$it['qty']);
}

json_out(['ok'=>true,'cart'=>$cart,'subtotal'=>$subtotal]);