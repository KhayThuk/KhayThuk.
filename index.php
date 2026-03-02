<?php
require __DIR__ . "/config.php";
ensure_cart();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>POS Local</title>
  <link rel="stylesheet" href="assets/app.css" />
</head>
<body>
<header class="topbar">
  <div class="brand">POS Local (XAMPP)</div>
  <nav class="nav">
    <a href="index.php">ขาย</a>
    <a href="products.php">สินค้า/สต็อก</a>
    <a href="barcode_labels.php">พิมพ์บาร์โค้ด</a>
    <a href="sales.php">บิลย้อนหลัง</a>
  </nav>
</header>

<main class="container">
  <div class="grid">
    <section class="card">
      <h2 class="h">หน้าขาย (สแกนบาร์โค้ด/ค้นหาสินค้า)</h2>

      <div class="row">
        <input id="barcode" type="text" placeholder="ยิงบาร์โค้ดแล้วกด Enter..." style="flex:1;min-width:220px" autofocus>
        <input id="q" type="text" placeholder="ค้นหาชื่อสินค้า..." style="flex:1;min-width:220px">
        <button id="btnSearch">ค้นหา</button>
      </div>

      <div id="searchResult" style="margin-top:12px"></div>

      <hr>

      <h3 class="h">ตะกร้าสินค้า</h3>
      <div id="cartWrap"></div>
    </section>

    <aside class="card">
      <h2 class="h">ชำระเงิน</h2>

      <div class="notice small">
        ✅ ระบบจะ “ตัดสต็อก” ตอนกด <b>ชำระเงิน</b><br>
        (ถ้าสต็อกไม่พอ ระบบจะไม่ให้ขาย)
      </div>

      <div style="margin-top:12px">
        <div class="row" style="justify-content:space-between">
          <div class="muted">ยอดรวม (Subtotal)</div>
          <div id="subtotal" class="right">0.00</div>
        </div>

        <div class="row" style="justify-content:space-between;margin-top:8px">
          <div class="muted">ส่วนลด (บาท)</div>
          <input id="discount" type="number" min="0" step="0.01" value="0" style="width:160px;text-align:right">
        </div>

        <div class="row" style="justify-content:space-between;margin-top:10px">
          <div class="muted">ยอดสุทธิ</div>
          <div id="total" class="total right">0.00</div>
        </div>

        <hr>

        <div class="row">
          <select id="pay_method" style="flex:1">
            <option value="cash">เงินสด</option>
            <option value="transfer">โอน/QR</option>
            <option value="card">บัตร</option>
            <option value="mix">ผสม</option>
          </select>
          <input id="cash_received" type="number" min="0" step="0.01" value="0" style="flex:1" placeholder="รับเงิน (บาท)">
        </div>

        <div class="row" style="justify-content:space-between;margin-top:10px">
          <div class="muted">เงินทอน</div>
          <div id="change" class="right">0.00</div>
        </div>

        <!-- ✅ กล่องแสดง QR PromptPay (บนหน้าเดิม) -->
        <div id="qrWrap" style="display:none; margin-top:12px; padding:12px; border:1px solid #eee; border-radius:12px; background:#fff">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <b>สแกนจ่าย PromptPay</b>
            <span id="qrAmount" style="font-weight:700;">0.00 บาท</span>
          </div>
          <div class="small muted" style="margin-top:6px">QR จะอัปเดตตามยอดสุทธิอัตโนมัติ</div>
          <div id="qrcode" style="margin-top:10px;"></div>
        </div>

        <div class="row" style="margin-top:14px">
          <button id="btnCheckout" style="flex:1">ชำระเงิน</button>
          <button id="btnClear" class="secondary">ล้างตะกร้า</button>
        </div>

        <div id="checkoutMsg" class="small" style="margin-top:10px"></div>
        <div class="small muted" style="margin-top:6px">
          คีย์ลัด: <b>F9</b> = เปิดชำระเงิน, <b>Esc</b> = ปิดหน้าต่าง
        </div>
      </div>
    </aside>
  </div>
</main>

<footer class="footer">
  <small>Local POS • ใช้ในเครื่องเดียว • ตัดสต็อกอัตโนมัติ</small>
</footer>

<!-- ✅ Payment Modal -->
<div id="payModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999;">
  <div style="max-width:520px; margin:8vh auto; background:#fff; border-radius:16px; padding:16px;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <div style="font-size:18px; font-weight:800;">ชำระเงิน</div>
      <button id="btnClosePay" class="secondary">ปิด</button>
    </div>

    <div style="margin-top:10px; border-top:1px solid #eee; padding-top:10px;">
      <div class="row" style="justify-content:space-between">
        <div class="muted">ยอดสุทธิ</div>
        <div id="mTotal" style="font-size:22px; font-weight:900;">0.00</div>
      </div>

      <div class="row" style="margin-top:10px;">
        <select id="mPayMethod" style="flex:1">
          <option value="cash">เงินสด</option>
          <option value="transfer">โอน/QR</option>
        </select>
        <input id="mCash" type="number" min="0" step="0.01" value="0" style="flex:1" placeholder="รับเงิน (บาท)">
      </div>

      <div class="row" style="justify-content:space-between; margin-top:10px;">
        <div class="muted">เงินทอน</div>
        <div id="mChange" style="font-weight:800;">0.00</div>
      </div>

      <div id="mQrWrap" style="display:none; margin-top:10px; padding:12px; border:1px solid #eee; border-radius:12px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <b>สแกนจ่าย PromptPay</b>
          <span id="mQrAmount" style="font-weight:800;">0.00 บาท</span>
        </div>
        <div id="mQrcode" style="margin-top:10px;"></div>
      </div>

      <div class="row" style="margin-top:12px;">
        <button id="btnConfirmPay" style="flex:1">ยืนยันชำระเงิน (Enter)</button>
      </div>

      <div class="small muted" style="margin-top:8px;">
        ทิป: ยืนยันด้วย Enter, ปิดด้วย Esc
      </div>
    </div>
  </div>
</div>

<script>
async function api(url, data) {
  const res = await fetch(url, {
    method: "POST",
    cache: "no-store",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify(data || {})
  });
  return res.json();
}
function escapeHtml(s){return String(s).replace(/[&<>"']/g,m=>({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;" }[m]))}

async function loadCart() {
  const res = await api("api/update_cart.php", {action:"get"});
  renderCart(res);
}

function renderCart(res){
  const wrap = document.getElementById("cartWrap");
  if(!res.ok){
    wrap.innerHTML = `<div class="notice">${escapeHtml(res.error||"เกิดข้อผิดพลาด")}</div>`;
    return;
  }
  const items = res.cart || [];
  if(items.length === 0){
    wrap.innerHTML = `<div class="muted">ยังไม่มีสินค้าในตะกร้า</div>`;
  } else {
    let html = `<table>
      <thead><tr>
        <th>สินค้า</th><th class="right">ราคา</th><th class="right">จำนวน</th><th class="right">รวม</th><th></th>
      </tr></thead><tbody>`;
    for(const it of items){
      html += `<tr>
        <td>
          <div><b>${escapeHtml(it.name)}</b></div>
          <div class="small muted">${escapeHtml(it.barcode)}</div>
        </td>
        <td class="right">${Number(it.price).toFixed(2)}</td>
        <td class="right">
          <input type="number" min="1" step="1" value="${it.qty}"
            style="width:90px;text-align:right"
            onchange="updateQty(${it.product_id}, this.value)">
        </td>
        <td class="right">${(Number(it.price)*Number(it.qty)).toFixed(2)}</td>
        <td class="right"><button class="danger" onclick="removeItem(${it.product_id})">ลบ</button></td>
      </tr>`;
    }
    html += `</tbody></table>`;
    wrap.innerHTML = html;
  }

  document.getElementById("subtotal").textContent = Number(res.subtotal||0).toFixed(2);
  recalcTotal();
}

function recalcTotal(){
  const subtotal = Number(document.getElementById("subtotal").textContent||0);
  const discount = Number(document.getElementById("discount").value||0);
  const total = Math.max(0, subtotal - discount);
  document.getElementById("total").textContent = total.toFixed(2);

  const cash = Number(document.getElementById("cash_received").value||0);
  const change = Math.max(0, cash - total);
  document.getElementById("change").textContent = change.toFixed(2);
}

document.getElementById("discount").addEventListener("input", recalcTotal);
document.getElementById("cash_received").addEventListener("input", recalcTotal);

async function updateQty(product_id, qty){
  qty = parseInt(qty,10);
  if(!qty || qty < 1) qty = 1;
  const res = await api("api/update_cart.php", {action:"set_qty", product_id, qty});
  renderCart(res);
}

async function removeItem(product_id){
  const res = await api("api/remove_from_cart.php", {product_id});
  if(!res.ok){
    alert(res.error || "ลบไม่สำเร็จ");
    return;
  }
  await loadCart();
  document.getElementById("barcode").focus();
}

async function addToCartByBarcode(barcode){
  const res = await api("api/add_to_cart.php", {barcode});
  if(!res.ok){
    const box = document.getElementById("checkoutMsg");
    box.innerHTML = `<span style="color:#c62828"><b>ไม่สำเร็จ:</b> ${escapeHtml(res.error||"")}</span>`;
    return;
  }
  await loadCart();
  document.getElementById("barcode").focus();
}

async function searchProducts(){
  const q = document.getElementById("q").value.trim();
  const res = await api("api/product_search.php", {q});
  const box = document.getElementById("searchResult");
  if(!res.ok){
    box.innerHTML = `<div class="notice">${escapeHtml(res.error||"ค้นหาไม่สำเร็จ")}</div>`;
    return;
  }
  if(res.items.length === 0){
    box.innerHTML = `<div class="muted">ไม่พบสินค้า</div>`;
    return;
  }
  let html = `<table>
    <thead><tr>
      <th>สินค้า</th><th class="right">ราคา</th><th class="right">คงเหลือ</th><th></th>
    </tr></thead><tbody>`;
  for(const p of res.items){
    html += `<tr>
      <td><b>${escapeHtml(p.name)}</b><div class="small muted">${escapeHtml(p.barcode)}</div></td>
      <td class="right">${Number(p.sell_price).toFixed(2)}</td>
      <td class="right">${p.stock_qty}</td>
      <td class="right"><button onclick="addToCartByBarcode('${escapeHtml(p.barcode)}')">เพิ่ม</button></td>
    </tr>`;
  }
  html += `</tbody></table>`;
  box.innerHTML = html;
}
document.getElementById("btnSearch").addEventListener("click", searchProducts);
document.getElementById("q").addEventListener("keydown", (e)=>{ if(e.key==="Enter") searchProducts(); });

// ====== Scanner-safe Barcode input ======
let __lastScanAt = 0;
document.getElementById("barcode").addEventListener("keydown", async (e)=>{
  if(e.key !== "Enter") return;

  // กันบางสแกนเนอร์ส่ง Enter รัว ๆ (CR+LF)
  const now = Date.now();
  if(now - __lastScanAt < 120){
    e.preventDefault();
    return;
  }

  const bc = e.target.value.trim();
  e.target.value = "";

  if(bc){
    __lastScanAt = now; // จำว่าเพิ่งสแกน
    e.preventDefault();
    await addToCartByBarcode(bc);
  } else {
    __lastScanAt = now;
  }
});

document.getElementById("btnClear").addEventListener("click", async ()=>{
  const res = await api("api/update_cart.php", {action:"clear"});
  renderCart(res);
  document.getElementById("checkoutMsg").textContent = "";
});

// ====== QR PromptPay (บนหน้าเดิม) ======
async function renderPromptPayQR(){
  const payMethod = document.getElementById("pay_method").value;
  const wrap = document.getElementById("qrWrap");
  const box  = document.getElementById("qrcode");

  if(payMethod !== "transfer"){
    wrap.style.display = "none";
    return;
  }

  const total = Number((document.getElementById("total").textContent || "0").replace(/,/g,''));
  wrap.style.display = "block";

  if(!total || total <= 0){
    document.getElementById("qrAmount").textContent = "0.00 บาท";
    box.innerHTML = `<div style="color:#c62828">กรุณาเพิ่มสินค้าในตะกร้าก่อน เพื่อสร้าง QR ตามยอดบิล</div>`;
    return;
  }

  document.getElementById("qrAmount").textContent = total.toFixed(2) + " บาท";

  const res = await api("api/promptpay_qr.php", { amount: total });
  if(!res.ok){
    box.innerHTML = `<div style="color:#c62828">สร้าง QR ไม่ได้: ${escapeHtml(res.error||"")}</div>`;
    return;
  }

  box.innerHTML = "";
  if(typeof QRCode === "undefined"){
    box.innerHTML = `<div style="color:#c62828">ยังโหลด QR library ไม่ได้ (เช็ก assets/qrcode.min.js)</div>`;
    return;
  }
  new QRCode(box, { text: res.payload, width: 220, height: 220 });
}
document.getElementById("pay_method").addEventListener("change", renderPromptPayQR);

const __recalcTotal = recalcTotal;
recalcTotal = function(){
  __recalcTotal();
  renderPromptPayQR();
};

// ====== Checkout จริง ======
async function doCheckout(){
  const discount = Number(document.getElementById("discount").value||0);
  const pay_method = document.getElementById("pay_method").value;
  const cash_received = Number(document.getElementById("cash_received").value||0);

  const res = await api("api/checkout.php", {discount, pay_method, cash_received});
  const msg = document.getElementById("checkoutMsg");

  if(!res.ok){
    msg.innerHTML = `<span style="color:#c62828"><b>ไม่สำเร็จ:</b> ${escapeHtml(res.error||"")}</span>`;
    return;
  }

  msg.innerHTML = `<span style="color:#2e7d32"><b>สำเร็จ!</b> เลขบิล: ${escapeHtml(res.receipt_no)}</span>
  <div style="margin-top:6px"><a href="sales.php?rid=${encodeURIComponent(res.receipt_no)}">ดู/พิมพ์ใบเสร็จ</a></div>`;

  document.getElementById("discount").value = 0;
  document.getElementById("cash_received").value = 0;

  await loadCart();
  renderPromptPayQR();
}

// ====== Payment Modal ======
function cartHasItems(){
  const subtotal = Number((document.getElementById("subtotal").textContent || "0").replace(/,/g,''));
  return subtotal > 0;
}
function openPayModal(){
  if(!cartHasItems()){
    document.getElementById("checkoutMsg").innerHTML =
      `<span style="color:#c62828"><b>ยังไม่มีสินค้าในตะกร้า</b></span>`;
    return;
  }

  const total = Number((document.getElementById("total").textContent || "0").replace(/,/g,''));
  const payMethod = document.getElementById("pay_method").value;

  document.getElementById("mTotal").textContent = total.toFixed(2);
  document.getElementById("mPayMethod").value = (payMethod === "transfer") ? "transfer" : "cash";
  document.getElementById("mCash").value = 0;

  document.getElementById("payModal").style.display = "block";
  renderModalQR();
  recalcModalChange();
  setTimeout(()=> document.getElementById("mCash").focus(), 0);
}
function closePayModal(){
  document.getElementById("payModal").style.display = "none";
}
function recalcModalChange(){
  const total = Number(document.getElementById("mTotal").textContent || 0);
  const cash = Number(document.getElementById("mCash").value || 0);
  const change = Math.max(0, cash - total);
  document.getElementById("mChange").textContent = change.toFixed(2);
}
async function renderModalQR(){
  const method = document.getElementById("mPayMethod").value;
  const wrap = document.getElementById("mQrWrap");
  const box  = document.getElementById("mQrcode");
  const total = Number(document.getElementById("mTotal").textContent || 0);

  if(method !== "transfer"){
    wrap.style.display = "none";
    return;
  }
  wrap.style.display = "block";
  document.getElementById("mQrAmount").textContent = total.toFixed(2) + " บาท";

  const res = await api("api/promptpay_qr.php", { amount: total });
  if(!res.ok){
    box.innerHTML = `<div style="color:#c62828">สร้าง QR ไม่ได้: ${escapeHtml(res.error||"")}</div>`;
    return;
  }
  box.innerHTML = "";
  new QRCode(box, { text: res.payload, width: 220, height: 220 });
}
async function confirmPay(){
  const method = document.getElementById("mPayMethod").value;
  const cash   = Number(document.getElementById("mCash").value || 0);

  document.getElementById("pay_method").value = method;
  document.getElementById("cash_received").value = cash;
  recalcTotal();

  closePayModal();
  await doCheckout();
}

document.getElementById("btnClosePay").addEventListener("click", closePayModal);
document.getElementById("btnConfirmPay").addEventListener("click", confirmPay);
document.getElementById("mCash").addEventListener("input", recalcModalChange);
document.getElementById("mPayMethod").addEventListener("change", ()=>{
  renderModalQR();
  document.getElementById("mCash").focus();
});

// ปุ่ม "ชำระเงิน" เปิด Modal
document.getElementById("btnCheckout").addEventListener("click", (e)=>{
  e.preventDefault();
  openPayModal();
});

// ====== Keyboard shortcuts (scanner-safe) ======
document.addEventListener("keydown", (e)=>{
  const modalOpen = document.getElementById("payModal").style.display === "block";

  if(modalOpen){
    if(e.key === "Escape"){
      e.preventDefault();
      closePayModal();
    }
    if(e.key === "Enter"){
      e.preventDefault();
      confirmPay();
    }
    return;
  }

  // F9 เปิดชำระเงิน (ไม่ชนกับสแกน)
  if(e.key === "F9"){
    if(cartHasItems()){
      e.preventDefault();
      openPayModal();
    }
    return;
  }

  // Enter เปิด modal เฉพาะตอน barcode ว่าง + เว้นหลังสแกน 0.6s กัน Enter ติดจากสแกนเนอร์
  if(e.key === "Enter" && document.activeElement?.id === "barcode"){
    const bcVal = document.getElementById("barcode").value.trim();
    const now = Date.now();
    if(bcVal === "" && cartHasItems() && (now - __lastScanAt) > 600){
      e.preventDefault();
      openPayModal();
    }
  }
});

// โหลดตะกร้าครั้งแรก
loadCart();
</script>

<!-- ✅ ใช้ QR library แบบออฟไลน์ -->
<script src="assets/qrcode.min.js"></script>
</body>
</html>