<?php
declare(strict_types=1);

// ✅ เปิดแสดง error กันจอขาว
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . "/config.php";

$T_SALES = "`sales`";
$T_ITEMS = "`sale_items`";

$rid = trim((string)($_GET['rid'] ?? ''));

?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>บิลย้อนหลัง</title>
  <link rel="stylesheet" href="assets/app.css" />
  <style>
    @media print {.topbar,.footer,.nav,button{display:none!important} .container{max-width:none;margin:0}}
  </style>
</head>
<body>
<header class="topbar">
  <div class="brand">บิลย้อนหลัง</div>
  <nav class="nav">
    <a href="index.php">ขาย</a>
    <a href="products.php">สินค้า/สต็อก</a>
    <a href="barcode_labels.php">พิมพ์บาร์โค้ด</a>
    <a href="sales.php">บิลย้อนหลัง</a>
  </nav>
</header>

<main class="container">
<?php
try {
  if ($rid !== '') {
    $st = $pdo->prepare("SELECT * FROM {$T_SALES} WHERE receipt_no=:r LIMIT 1");
    $st->execute([':r'=>$rid]);
    $sale = $st->fetch();

    if(!$sale){
      echo "<div class='card'><div class='notice'>ไม่พบเลขบิล: ".htmlspecialchars($rid)."</div></div>";
      echo "</main></body></html>"; exit;
    }

    $it = $pdo->prepare("SELECT * FROM {$T_ITEMS} WHERE sale_id=:id ORDER BY id ASC");
    $it->execute([':id'=>(int)$sale['id']]);
    $items = $it->fetchAll();
    ?>
    <div class="card">
      <div class="row" style="justify-content:space-between;align-items:center">
        <h2 class="h">ใบเสร็จ</h2>
        <button onclick="window.print()">พิมพ์</button>
      </div>

      <div class="small muted">
        เลขบิล: <b><?= htmlspecialchars($sale['receipt_no']) ?></b> • เวลา: <?= htmlspecialchars($sale['sold_at']) ?>
      </div>

      <hr>

      <table>
        <thead>
          <tr>
            <th>สินค้า</th>
            <th class="right">ราคา</th>
            <th class="right">จำนวน</th>
            <th class="right">รวม</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($items as $x): ?>
          <tr>
            <td>
              <b><?= htmlspecialchars($x['name']) ?></b>
              <div class="small muted"><?= htmlspecialchars($x['barcode']) ?></div>
            </td>
            <td class="right"><?= money((float)$x['price']) ?></td>
            <td class="right"><?= (int)$x['qty'] ?></td>
            <td class="right"><?= money((float)$x['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <hr>

      <div class="row" style="justify-content:space-between"><div class="muted">Subtotal</div><div><?= money((float)$sale['subtotal']) ?></div></div>
      <div class="row" style="justify-content:space-between"><div class="muted">Discount</div><div><?= money((float)$sale['discount']) ?></div></div>
      <div class="row" style="justify-content:space-between"><div class="muted"><b>Total</b></div><div class="total"><?= money((float)$sale['total']) ?></div></div>

      <div class="row" style="justify-content:space-between;margin-top:8px"><div class="muted">จ่ายโดย</div><div><span class="badge"><?= htmlspecialchars($sale['pay_method']) ?></span></div></div>
      <div class="row" style="justify-content:space-between"><div class="muted">รับเงิน</div><div><?= money((float)$sale['cash_received']) ?></div></div>
      <div class="row" style="justify-content:space-between"><div class="muted">เงินทอน</div><div><?= money((float)$sale['change_amount']) ?></div></div>
    </div>
    <?php
    echo "</main></body></html>";
    exit;
  }

  // รายการบิลย้อนหลัง
  $stmt = $pdo->query("SELECT receipt_no, sold_at, total, pay_method FROM {$T_SALES} ORDER BY id DESC LIMIT 200");
  $sales = $stmt->fetchAll();
  ?>
  <div class="card">
    <h2 class="h">บิลย้อนหลัง (ล่าสุด 200)</h2>
    <table>
      <thead>
        <tr>
          <th>เวลา</th>
          <th>เลขบิล</th>
          <th class="right">ยอด</th>
          <th>ชำระ</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($sales as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['sold_at']) ?></td>
          <td><b><?= htmlspecialchars($s['receipt_no']) ?></b></td>
          <td class="right"><?= money((float)$s['total']) ?></td>
          <td><span class="badge"><?= htmlspecialchars($s['pay_method']) ?></span></td>
          <td class="right"><a href="sales.php?rid=<?= urlencode($s['receipt_no']) ?>">ดูใบเสร็จ</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php

} catch (Throwable $e) {
  echo "<div class='card'><pre>ERROR: ".htmlspecialchars($e->getMessage())."</pre></div>";
}
?>
</main>

<footer class="footer"><small>Local POS</small></footer>
</body>
</html>