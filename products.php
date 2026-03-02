<?php
require __DIR__ . "/config.php";

// ใช้ชื่อตารางตามที่คุณมีใน phpMyAdmin (Products ตัว P ใหญ่)
$T_PRODUCTS = "`Products`";

$q = trim((string)($_GET['q'] ?? ''));
$msg = trim((string)($_GET['msg'] ?? ''));

try {
  if ($q === '') {
    $stmt = $pdo->query("SELECT * FROM {$T_PRODUCTS} ORDER BY id DESC LIMIT 200");
  } else {
    $stmt = $pdo->prepare("SELECT * FROM {$T_PRODUCTS} WHERE name LIKE :q OR barcode LIKE :q ORDER BY name ASC LIMIT 200");
    $stmt->execute([':q'=>"%{$q}%"]);
  }
  $items = $stmt->fetchAll();
} catch (Throwable $e) {
  echo "<pre>DB ERROR: ".htmlspecialchars($e->getMessage())."</pre>";
  exit;
}

// สร้างบาร์โค้ดภายในร้านอัตโนมัติ (ถ้ายังไม่ใส่)
function gen_barcode(PDO $pdo, string $T_PRODUCTS): string {
  while(true){
    $bc = "250" . date("ymd") . str_pad((string)rand(0,9999), 4, "0", STR_PAD_LEFT);
    $st = $pdo->prepare("SELECT id FROM {$T_PRODUCTS} WHERE barcode=:b LIMIT 1");
    $st->execute([':b'=>$bc]);
    if(!$st->fetch()) return $bc;
  }
}
$auto_barcode = gen_barcode($pdo, $T_PRODUCTS);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>สินค้า/สต็อก</title>
  <link rel="stylesheet" href="assets/app.css" />
</head>
<body>
<header class="topbar">
  <div class="brand">POS Local</div>
  <nav class="nav">
    <a href="index.php">ขาย</a>
    <a href="products.php">สินค้า/สต็อก</a>
    <a href="barcode_labels.php">พิมพ์บาร์โค้ด</a>
    <a href="sales.php">บิลย้อนหลัง</a>
  </nav>
</header>

<main class="container">
  <?php if($msg !== ''): ?>
    <div class="card"><div class="notice"><?= htmlspecialchars($msg) ?></div></div>
  <?php endif; ?>

  <div class="card">
    <h2 class="h">สินค้า/สต็อก (แก้ไขได้ในหน้าเว็บ)</h2>

    <form class="row" method="get" style="margin-bottom:12px">
      <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="ค้นหาชื่อ/บาร์โค้ด" style="flex:1;min-width:220px">
      <button>ค้นหา</button>
      <a class="badge" href="products.php">ล้าง</a>
    </form>

    <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
      <div class="card" style="box-shadow:none;border:1px solid #eef0f6">
        <h3 class="h">เพิ่มสินค้าใหม่</h3>
        <form method="post" action="product_save.php">
          <input type="hidden" name="mode" value="create">
          <div class="row">
            <label style="flex:1">
              <div class="small muted">บาร์โค้ด</div>
              <input name="barcode" value="<?= htmlspecialchars($auto_barcode) ?>" required>
            </label>
            <label style="flex:1">
              <div class="small muted">หมวดหมู่</div>
              <input name="category" placeholder="เช่น น้ำดื่ม/ขนม">
            </label>
          </div>

          <label style="display:block;margin-top:8px">
            <div class="small muted">ชื่อสินค้า</div>
            <input name="name" required>
          </label>

          <div class="row" style="margin-top:8px">
            <label style="flex:1">
              <div class="small muted">ราคาทุน</div>
              <input type="number" min="0" step="0.01" name="cost_price" value="0">
            </label>
            <label style="flex:1">
              <div class="small muted">ราคาขาย</div>
              <input type="number" min="0" step="0.01" name="sell_price" value="0" required>
            </label>
          </div>

          <div class="row" style="margin-top:8px">
            <label style="flex:1">
              <div class="small muted">สต็อกเริ่มต้น</div>
              <input type="number" min="0" step="1" name="stock_qty" value="0">
            </label>
            <label style="flex:1">
              <div class="small muted">แจ้งเตือนเมื่อเหลือน้อยกว่า</div>
              <input type="number" min="0" step="1" name="low_stock" value="0">
            </label>
          </div>

          <div class="row" style="margin-top:10px">
            <button style="flex:1">บันทึกสินค้า</button>
            <a class="badge" href="barcode_labels.php">ไปพิมพ์บาร์โค้ด</a>
          </div>
        </form>
      </div>

      <div class="card" style="box-shadow:none;border:1px solid #eef0f6">
        <h3 class="h">เพิ่มสต็อก (รับของเข้าเร็วๆ)</h3>
        <form method="post" action="product_save.php">
          <input type="hidden" name="mode" value="restock">
          <div class="row">
            <input name="barcode" placeholder="ยิงบาร์โค้ดสินค้า" required style="flex:1">
            <input type="number" min="1" step="1" name="qty" value="1" required style="width:140px">
            <button>เพิ่มสต็อก</button>
          </div>
          <div class="small muted" style="margin-top:6px">
            * ระบบจะเพิ่มสต็อกเข้า และบันทึก stock_moves เป็น restock
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:12px">
    <h3 class="h">รายการสินค้า (แก้ไขได้)</h3>
    <table>
      <thead>
        <tr>
          <th>สินค้า</th>
          <th class="right">ทุน</th>
          <th class="right">ขาย</th>
          <th class="right">คงเหลือ</th>
          <th>แก้ไข</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($items as $p): ?>
        <tr>
          <td>
            <b><?= htmlspecialchars($p['name']) ?></b>
            <div class="small muted"><?= htmlspecialchars($p['barcode']) ?> • <?= htmlspecialchars($p['category'] ?? '') ?></div>
          </td>
          <td class="right"><?= money((float)$p['cost_price']) ?></td>
          <td class="right"><?= money((float)$p['sell_price']) ?></td>
          <td class="right"><?= (int)$p['stock_qty'] ?></td>
          <td class="right">
            <a class="badge" href="product_save.php?mode=edit&id=<?= (int)$p['id'] ?>">แก้ไข</a>
            <a class="badge" style="background:#ffe6e6"
               href="product_save.php?mode=delete&id=<?= (int)$p['id'] ?>"
               onclick="return confirm('ลบสินค้านี้แน่ใจไหม?');">ลบ</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<footer class="footer">
  <small>Local POS • จัดการสินค้า/สต็อกได้บนหน้าเว็บ</small>
</footer>
</body>
</html>