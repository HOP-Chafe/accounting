<?php
if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; }
$closeUserName = trim((string)(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')));
if ($closeUserName === '') {
    $closeUserName = trim((string)($_SESSION['user']['username'] ?? ''));
}
?>
      <section class="page" id="page-close">
        <div class="page-head"><div><h2>ปิดรอบเงินสด</h2><p>บันทึกประวัติการนับเงินลงฐานข้อมูลทุกครั้ง</p></div></div>
        <div class="close-grid">
          <div class="card form-card">
            <div class="form-section"><h3>ข้อมูลรอบ</h3><div class="form-grid close-round-grid"><div class="field close-date-field"><label for="cDate">วันที่ปิดรอบ</label><input type="date" id="cDate" class="close-date-input" required></div><div class="field close-user-field"><label for="closedBy">ผู้ปิดรอบ</label><input id="closedBy" value="<?=htmlspecialchars($closeUserName, ENT_QUOTES, 'UTF-8')?>" readonly aria-readonly="true"><small class="close-user-hint">ดึงจากผู้ใช้งานที่ Login อยู่</small></div></div></div>
            <div class="form-section"><h3>1. นับเงินสดตามชนิดธนบัตร/เหรียญ</h3><div class="denom-grid" id="denomGrid"></div></div>
            <div class="form-section"><h3>2. เปรียบเทียบกับยอดที่ควรมี</h3><div class="form-grid"><div class="field"><label>ยอดเงินสดที่ควรมี</label><div class="suffix"><input type="number" min="0" step="0.01" id="expectedCash" value="0" inputmode="decimal" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateCloseCalc()"><b>บาท</b></div></div></div></div>
            <div class="form-actions"><button class="btn primary" type="button" onclick="saveClose()">บันทึกการปิดรอบ</button></div>
          </div>
          <div>
            <div class="card section"><div class="section-title"><h3>สรุปการปิดรอบ</h3><span>คำนวณทันที</span></div><div class="summary-box"><div class="row"><span>เงินสดที่นับได้</span><b id="countedCash">฿0.00</b></div><div class="row"><span>เงินสดที่ควรมี</span><b id="expectedCashText">฿0.00</b></div><div class="row"><span>ผลต่าง</span><b class="big" id="cashVariance">฿0.00</b></div><div class="row"><span>สถานะ</span><b id="cashStatus">ปกติ</b></div></div></div>
            <div class="card section" style="margin-top:16px"><div class="section-title"><h3>ประวัติปิดรอบล่าสุด</h3><span>20 รายการ</span></div><div id="closeHistory"><div class="empty">กำลังโหลด...</div></div></div>
          </div>
        </div>
      </section>
