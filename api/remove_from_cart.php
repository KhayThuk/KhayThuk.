<?php
require __DIR__ . "/../config.php";
ensure_cart();

$input = json_decode(file_get_contents("php://input"), true) ?: [];
$pid = (int)($input['product_id'] ?? 0);

if ($pid > 0 && isset($_SESSION['cart'][$pid])) {
  unset($_SESSION['cart'][$pid]);
}

json_out(['ok'=>true]);