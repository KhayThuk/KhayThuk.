<?php
require __DIR__ . "/../config.php";

$T_PRODUCTS = "`Products`";

$input = json_decode(file_get_contents("php://input"), true) ?: [];
$q = trim((string)($input['q'] ?? ''));

try {
  if ($q === '') {
    $stmt = $pdo->query("SELECT id, barcode, name, sell_price, stock_qty FROM {$T_PRODUCTS} ORDER BY id DESC LIMIT 30");
  } else {
    $stmt = $pdo->prepare("SELECT id, barcode, name, sell_price, stock_qty
                           FROM {$T_PRODUCTS}
                           WHERE name LIKE :q OR barcode LIKE :q
                           ORDER BY name ASC LIMIT 50");
    $stmt->execute([':q' => "%{$q}%"]);
  }
  $items = $stmt->fetchAll();
  json_out(['ok'=>true,'items'=>$items]);
} catch (Throwable $e) {
  json_out(['ok'=>false,'error'=>$e->getMessage()]);
}