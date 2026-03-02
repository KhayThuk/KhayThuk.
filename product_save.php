<?php
require __DIR__ . "/config.php";

$T_PRODUCTS = "`Products`";
$T_MOVES = "`stock_moves`";

$mode = (string)($_REQUEST['mode'] ?? '');

function go(string $m): void {
  header("Location: products.php?msg=" . urlencode($m));
  exit;
}

try {
  // ------- แสดงฟอร์มแก้ไข -------
  if ($mode === 'edit' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM {$T_PRODUCTS} WHERE id=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $p = $st->fetch();
    if(!$p) throw new Exception("ไม่พบสินค้า");

    ?>
    <!doctype html><html lang="th"><head>
      <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
      <title>แก้ไขสินค้า</title>
      <link rel="stylesheet" href="assets/app.css">
    </head><body>
    <header class="topbar">
      <div class="brand">แก้ไขสินค้า</div>
      <nav class="nav"><a href="products.php">กลับ</a></nav>
    </header>
    <main class="container">
      <div class="card">
        <h2 class="h">แก้ไข: <?= htmlspecialchars($p['name']) ?></h2>
        <form method="post" action="product_save.php">
          <input type="hidden" name="mode" value="update">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

          <div class="row">
            <label style="flex:1">
              <div class="small muted">บาร์โค้ด</div>
              <input name="barcode" value="<?= htmlspecialchars($p['barcode']) ?>" required>
            </label>
            <label style="flex:1">
              <div class="small muted">หมวดหมู่</div>
              <input name="category" value="<?= htmlspecialchars($p['category'] ?? '') ?>">
            </label>
          </div>

          <label style="display:block;margin-top:8px">
            <div class="small muted">ชื่อสินค้า</div>
            <input name="name" value="<?= htmlspecialchars($p['name']) ?>" required>
          </label>

          <div class="row" style="margin-top:8px">
            <label style="flex:1">
              <div class="small muted">ราคาทุน</div>
              <input type="number" min="0" step="0.01" name="cost_price" value="<?= htmlspecialchars((string)$p['cost_price']) ?>">
            </label>
            <label style="flex:1">
              <div class="small muted">ราคาขาย</div>
              <input type="number" min="0" step="0.01" name="sell_price" value="<?= htmlspecialchars((string)$p['sell_price']) ?>" required>
            </label>
          </div>

          <div class="row" style="margin-top:8px">
            <label style="flex:1">
              <div class="small muted">สต็อกคงเหลือ</div>
              <input type="number" min="0" step="1" name="stock_qty" value="<?= (int)$p['stock_qty'] ?>">
            </label>
            <label style="flex:1">
              <div class="small muted">แจ้งเตือนเมื่อเหลือน้อยกว่า</div>
              <input type="number" min="0" step="1" name="low_stock" value="<?= (int)$p['low_stock'] ?>">
            </label>
          </div>

          <div class="row" style="margin-top:12px">
            <button style="flex:1">บันทึกการแก้ไข</button>
            <a class="badge" href="products.php">ยกเลิก</a>
          </div>
        </form>
      </div>
    </main>
    </body></html>
    <?php
    exit;
  }

  // ------- เพิ่มสินค้า -------
  if ($mode === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = trim((string)($_POST['barcode'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $sell_price = (float)($_POST['sell_price'] ?? 0);
    $stock_qty = (int)($_POST['stock_qty'] ?? 0);
    $low_stock = (int)($_POST['low_stock'] ?? 0);

    if ($barcode === '' || $name === '') throw new Exception("กรอกบาร์โค้ดและชื่อสินค้าให้ครบ");

    $stmt = $pdo->prepare("INSERT INTO {$T_PRODUCTS}(barcode,name,category,cost_price,sell_price,stock_qty,low_stock)
                           VALUES(:b,:n,:c,:cp,:sp,:sq,:ls)");
    $stmt->execute([
      ':b'=>$barcode, ':n'=>$name, ':c'=>$category,
      ':cp'=>$cost_price, ':sp'=>$sell_price,
      ':sq'=>$stock_qty, ':ls'=>$low_stock
    ]);

    go("เพิ่มสินค้าแล้ว");
  }

  // ------- อัปเดตสินค้า -------
  if ($mode === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $barcode = trim((string)($_POST['barcode'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $sell_price = (float)($_POST['sell_price'] ?? 0);
    $stock_qty = (int)($_POST['stock_qty'] ?? 0);
    $low_stock = (int)($_POST['low_stock'] ?? 0);

    if ($id <= 0) throw new Exception("ID ไม่ถูกต้อง");

    $stmt = $pdo->prepare("UPDATE {$T_PRODUCTS}
      SET barcode=:b, name=:n, category=:c, cost_price=:cp, sell_price=:sp, stock_qty=:sq, low_stock=:ls
      WHERE id=:id");
    $stmt->execute([
      ':b'=>$barcode, ':n'=>$name, ':c'=>$category,
      ':cp'=>$cost_price, ':sp'=>$sell_price,
      ':sq'=>$stock_qty, ':ls'=>$low_stock,
      ':id'=>$id
    ]);

    go("แก้ไขสินค้าแล้ว");
  }

  // ------- ลบสินค้า -------
  if ($mode === 'delete' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) throw new Exception("ID ไม่ถูกต้อง");
    $stmt = $pdo->prepare("DELETE FROM {$T_PRODUCTS} WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    go("ลบสินค้าแล้ว");
  }

  // ------- รับของเข้า (เพิ่มสต็อก) -------
  if ($mode === 'restock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = trim((string)($_POST['barcode'] ?? ''));
    $qty = (int)($_POST['qty'] ?? 0);
    if ($barcode === '' || $qty < 1) throw new Exception("กรอกบาร์โค้ดและจำนวนให้ถูกต้อง");

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id FROM {$T_PRODUCTS} WHERE barcode=:b FOR UPDATE");
    $stmt->execute([':b'=>$barcode]);
    $p = $stmt->fetch();
    if (!$p) throw new Exception("ไม่พบสินค้า: {$barcode}");
    $pid = (int)$p['id'];

    $stmt = $pdo->prepare("UPDATE {$T_PRODUCTS} SET stock_qty = stock_qty + :q WHERE id=:id");
    $stmt->execute([':q'=>$qty, ':id'=>$pid]);

    $stmt = $pdo->prepare("INSERT INTO {$T_MOVES}(moved_at, product_id, move_type, qty_change, ref, note)
                           VALUES(:t, :pid, 'restock', :chg, '', 'รับของเข้า')");
    $stmt->execute([
      ':t'=>date("Y-m-d H:i:s"),
      ':pid'=>$pid,
      ':chg'=>$qty
    ]);

    $pdo->commit();
    go("เพิ่มสต็อกแล้ว +{$qty}");
  }

  throw new Exception("โหมดไม่ถูกต้อง");

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  echo "<pre>ERROR: " . htmlspecialchars($e->getMessage()) . "</pre>";
}