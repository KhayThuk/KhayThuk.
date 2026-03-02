<?php
require __DIR__ . "/../config.php";
ensure_cart();

$T_PRODUCTS = "`Products`";
$T_SALES = "`sales`";
$T_ITEMS = "`sale_items`";
$T_MOVES = "`stock_moves`";

$input = json_decode(file_get_contents("php://input"), true) ?: [];
$discount = (float)($input['discount'] ?? 0);
$pay_method = (string)($input['pay_method'] ?? 'cash');
$cash_received = (float)($input['cash_received'] ?? 0);

$cart = array_values($_SESSION['cart']);
if (count($cart) === 0) json_out(['ok'=>false,'error'=>'ตะกร้าว่าง']);

if (!in_array($pay_method, ['cash','transfer','card','mix'], true)) {
  $pay_method = 'cash';
}

try {
  $pdo->beginTransaction();

  // 1) ล็อก & ตรวจสต็อกพอไหม
  foreach ($cart as $it) {
    $pid = (int)$it['product_id'];
    $qty = (int)$it['qty'];

    $stmt = $pdo->prepare("SELECT stock_qty, name FROM {$T_PRODUCTS} WHERE id=:id FOR UPDATE");
    $stmt->execute([':id'=>$pid]);
    $p = $stmt->fetch();

    if (!$p) throw new Exception("ไม่พบสินค้าในระบบ (ID {$pid})");
    if ((int)$p['stock_qty'] < $qty) {
      throw new Exception("สต็อกไม่พอ: {$p['name']} (คงเหลือ {$p['stock_qty']})");
    }
  }

  // 2) คำนวณเงิน
  $subtotal = 0.0;
  foreach ($cart as $it) $subtotal += ((float)$it['price']) * ((int)$it['qty']);
  $discount = max(0, $discount);
  $total = max(0, $subtotal - $discount);

  $change = 0.0;
  if ($pay_method === 'cash') {
    if ($cash_received < $total) throw new Exception("รับเงินสดไม่พอ (ต้องอย่างน้อย {$total})");
    $change = max(0, $cash_received - $total);
  }

  // 3) เลขบิล
  $receipt_no = "R" . date("YmdHis") . rand(100,999);

  // 4) หัวบิล
  $stmt = $pdo->prepare("INSERT INTO {$T_SALES}(receipt_no, sold_at, subtotal, discount, total, pay_method, cash_received, change_amount)
                         VALUES(:r, :t, :s, :d, :tt, :pm, :cr, :ch)");
  $stmt->execute([
    ':r'=>$receipt_no,
    ':t'=>date("Y-m-d H:i:s"),
    ':s'=>$subtotal,
    ':d'=>$discount,
    ':tt'=>$total,
    ':pm'=>$pay_method,
    ':cr'=>$cash_received,
    ':ch'=>$change
  ]);
  $sale_id = (int)$pdo->lastInsertId();

  // 5) รายการ + ตัดสต็อก + บันทึกการเคลื่อนไหว
  foreach ($cart as $it) {
    $pid = (int)$it['product_id'];
    $qty = (int)$it['qty'];
    $price = (float)$it['price'];
    $line_total = $price * $qty;

    $stmt = $pdo->prepare("INSERT INTO {$T_ITEMS}(sale_id, product_id, barcode, name, qty, price, line_total)
                           VALUES(:sid, :pid, :bc, :nm, :q, :p, :lt)");
    $stmt->execute([
      ':sid'=>$sale_id,
      ':pid'=>$pid,
      ':bc'=>$it['barcode'],
      ':nm'=>$it['name'],
      ':q'=>$qty,
      ':p'=>$price,
      ':lt'=>$line_total
    ]);

    $stmt = $pdo->prepare("UPDATE {$T_PRODUCTS} SET stock_qty = stock_qty - :q WHERE id=:id");
    $stmt->execute([':q'=>$qty, ':id'=>$pid]);

    $stmt = $pdo->prepare("INSERT INTO {$T_MOVES}(moved_at, product_id, move_type, qty_change, ref, note)
                           VALUES(:t, :pid, 'sale', :chg, :ref, :note)");
    $stmt->execute([
      ':t'=>date("Y-m-d H:i:s"),
      ':pid'=>$pid,
      ':chg'=> -$qty,
      ':ref'=>$receipt_no,
      ':note'=>'ขายสินค้า'
    ]);
  }

  $pdo->commit();
  $_SESSION['cart'] = [];

  json_out(['ok'=>true,'receipt_no'=>$receipt_no]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_out(['ok'=>false,'error'=>$e->getMessage()]);
}