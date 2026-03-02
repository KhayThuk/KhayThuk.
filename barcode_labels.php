<?php
declare(strict_types=1);

// ✅ เปิดแสดง error กันจอขาว
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . "/config.php";

$T_PRODUCTS = "`Products`";

try {
  $stmt = $pdo->query("SELECT id, barcode, name, sell_price FROM {$T_PRODUCTS} ORDER BY id DESC LIMIT 60");
  $items = $stmt->fetchAll();
} catch (Throwable $e) {
  echo "<pre>ERROR: ".htmlspecialchars($e->getMessage())."</pre>";
  exit;
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>พิมพ์บาร์โค้ด</title>
  <link rel="stylesheet" href="assets/app.css" />
  <!-- ใช้ CDN: ถ้าเน็ตไม่มี "บาร์" อาจไม่ขึ้น แต่หน้าเว็บจะไม่ขาว -->
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
  <style>
    @media print {.topbar,.footer,.nav,button{display:none!important} .container{max-width:none;margin:0}}
    .labels{display:grid;grid-template-columns:repeat(3, 1fr);gap:10px}
    .label{border:1px dashed #cfd3e3;border-radius:10px;padding:10px;background:#fff}
    .label .nm{font-weight:800}
    svg{width:100%;height:60px}
  </style>
</head>
<body>
<header class="topbar">
  <div class="brand">พิมพ์บาร์โค้ด</div>
  <nav class="nav">
    <a href="index.php">ขาย</a>
    <a href="products.php">สินค้า/สต็อก</a>
    <a href="barcode_labels.php">พิมพ์บาร์โค้ด</a>
    <a href="sales.php">บิลย้อนหลัง</a>
  </nav>
</header>

<main class="container">
  <div class="card">
    <div class="row" style="justify-content:space-between;align-items:center">
      <h2 class="h">บาร์โค้ดสินค้า</h2>
      <button onclick="window.print()">พิมพ์</button>
    </div>
    <div class="small muted">แสดงล่าสุด 60 รายการ • ถ้าไม่เห็น “แท่งบาร์” ให้เช็กว่าเครื่องมีอินเทอร์เน็ต (เพราะโหลดไลบรารีจาก CDN)</div>

    <hr>

    <div class="labels">
      <?php foreach($items as $p): ?>
        <div class="label">
          <div class="nm"><?= htmlspecialchars($p['name']) ?></div>
          <div class="small muted"><?= htmlspecialchars($p['barcode']) ?></div>
          <svg class="bc" data-code="<?= htmlspecialchars($p['barcode']) ?>"></svg>
          <div style="margin-top:6px"><b><?= money((float)$p['sell_price']) ?></b> บาท</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<footer class="footer"><small>Local POS</small></footer>

<script>
document.querySelectorAll("svg.bc").forEach(svg=>{
  const code = svg.getAttribute("data-code") || "";
  try{
    if (window.JsBarcode) {
      JsBarcode(svg, code, {format:"CODE128", displayValue:true, fontSize:14, height:55, margin:0});
    } else {
      // ถ้าโหลด jsbarcode ไม่ได้ อย่างน้อยให้หน้าไม่พัง
      svg.outerHTML = "<div class='small' style='color:#c62828'>โหลด JsBarcode ไม่ได้ (ไม่มีเน็ต?)</div>";
    }
  }catch(e){
    svg.outerHTML = "<div class='small' style='color:#c62828'>สร้างบาร์โค้ดไม่สำเร็จ</div>";
  }
});
</script>
</body>
</html>