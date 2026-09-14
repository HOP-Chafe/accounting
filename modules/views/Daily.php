<?php
if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; }
?>
      <section class="page" id="page-daily">
        <div class="page-head"><div><h2>บันทึกยอดประจำวัน</h2><p>ถ้าบันทึกวันที่เดิม ระบบจะอัปเดตรายการเดิมในฐานข้อมูล</p></div></div>
        <form class="card form-card" id="dailyForm" onsubmit="saveDaily(event)">
          <div class="form-section"><h3>ข้อมูลทั่วไป</h3><div class="form-grid"><div class="field"><label>วันที่</label><input type="date" id="dDate" required></div><div class="field" style="grid-column:span 2"><label>หมายเหตุ</label><input id="dNote" maxlength="500" placeholder="เช่น ฝนตก / โปรโมชัน"></div></div></div>
          <div class="form-section"><h3>จำนวนแก้วตามช่องทางขาย</h3><div class="form-grid"><div class="field"><label>หน้าร้าน</label><div class="suffix"><input type="number" min="0" id="dFront" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>แก้ว</b></div></div><div class="field"><label>LINE MAN</label><div class="suffix"><input type="number" min="0" id="dLine" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>แก้ว</b></div></div><div class="field"><label>GRAB</label><div class="suffix"><input type="number" min="0" id="dGrab" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>แก้ว</b></div></div><div class="field"><label>SHOPEE</label><div class="suffix"><input type="number" min="0" id="dShopee" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>แก้ว</b></div></div></div><div class="calc-strip" style="margin-top:13px"><span>รวมจำนวนแก้ววันนี้</span><strong id="dailyCupTotal">0 แก้ว</strong></div></div>
          <div class="form-section"><h3>รายรับหน้าร้าน</h3><div class="form-grid"><div class="field"><label>เงินสด</label><div class="suffix"><input type="number" min="0" step="0.01" id="dCash" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div><div class="field"><label>เงินโอน</label><div class="suffix"><input type="number" min="0" step="0.01" id="dTransfer" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div><div class="field"><label>ไทยช่วยไทย / อื่น ๆ</label><div class="suffix"><input type="number" min="0" step="0.01" id="dThai" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div></div></div>
          <div class="form-section"><h3>รายรับออนไลน์</h3><div class="form-grid"><div class="field"><label>LINE MAN</label><div class="suffix"><input type="number" min="0" step="0.01" id="dLineRev" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div><div class="field"><label>GRAB</label><div class="suffix"><input type="number" min="0" step="0.01" id="dGrabRev" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div><div class="field"><label>SHOPEE</label><div class="suffix"><input type="number" min="0" step="0.01" id="dShopeeRev" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div></div></div>
          <div class="form-section">
            <div class="daily-section-head">
              <div><h3>รายจ่ายร้าน</h3><small>รายการพื้นฐานและรายจ่ายเพิ่มเติมของวัน</small></div>
              <button type="button" class="btn ghost small" onclick="addDailyExpenseItem()">+ เพิ่มรายการ</button>
            </div>
            <div class="form-grid">
              <div class="field"><label>ค่าน้ำแข็ง</label><div class="suffix"><input type="number" min="0" step="0.01" id="dIce" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div>
              <div class="field"><label>ค่าแรง</label><div class="suffix"><input type="number" min="0" step="0.01" id="dWage" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div>
              <div class="field"><label>ค่าแก้ว</label><div class="suffix"><input type="number" min="0" step="0.01" id="dCupCost" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div>
              <div class="field"><label>รายจ่ายอื่น ๆ</label><div class="suffix"><input type="number" min="0" step="0.01" id="dOtherExp" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateDailyCalc()"><b>บาท</b></div></div>
            </div>
            <div class="daily-extra-expense-list" id="dailyExtraExpenseRows"></div>
            <div class="calc-strip" style="margin-top:13px"><span>สรุปวันนี้ <small style="color:var(--muted)">รายรับ − รายจ่าย</small></span><strong id="dailyNet">฿0.00</strong></div>
          </div>
          <div class="form-actions"><button type="button" class="btn ghost" onclick="resetDailyForm()">ล้างข้อมูล</button><button type="submit" class="btn primary" id="dailySaveBtn">บันทึกรายการ</button></div>
        </form>
      </section>
