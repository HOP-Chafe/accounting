<?php
if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; }
?>
      <section class="page active" id="page-dashboard">
        <div class="page-head"><div><h2>ภาพรวมร้าน</h2><p>ตัวเลขทั้งหมดอ่านจากฐานข้อมูลของร้าน</p></div><div class="controls"><label style="font-size:12px;color:var(--muted)">เดือน</label><select id="dashMonth" onchange="renderDashboard()"></select></div></div>
        <div class="grid kpis">
          <div class="card kpi highlight"><div class="label">จำนวนแก้วทั้งหมด</div><div class="value" id="kpiCups">0</div><div class="hint" id="kpiDays">0 วันที่มีข้อมูล</div></div>
          <div class="card kpi"><div class="label">หน้าร้าน</div><div class="value" id="kpiFront">0</div><div class="hint" id="kpiFrontPct">0% ของทั้งหมด</div></div>
          <div class="card kpi"><div class="label">Delivery</div><div class="value" id="kpiDelivery">0</div><div class="hint" id="kpiDeliveryPct">0% ของทั้งหมด</div></div>
          <div class="card kpi"><div class="label">ยอดขายรวม</div><div class="value" id="kpiRevenue">—</div><div class="hint" id="kpiRevenueHint">รอข้อมูล</div></div>
        </div>
        <div class="grid dashboard-grid">
          <div class="card section"><div class="section-title"><h3>แนวโน้มจำนวนแก้วรายวัน</h3><span id="peakLabel">—</span></div><div class="trend" id="trend"></div></div>
          <div class="card section"><div class="section-title"><h3>สัดส่วนช่องทางขาย</h3><span>จำนวนแก้ว</span></div><div class="bars" id="channelBars"></div></div>
          <div class="card section"><div class="section-title"><h3>ภาพรวมทางบัญชี</h3><span>รายรับ − รายจ่าย</span></div><div id="financialSummary"></div></div>
          <div class="card section"><div class="section-title"><h3>ทางลัด</h3><span>งานที่ใช้ทุกวัน</span></div><div class="quick"><button onclick="gotoPage('daily')">+ บันทึกยอดวันนี้<span>จำนวนแก้ว + รายรับ + รายจ่าย</span></button><button onclick="gotoPage('close')">✓ ปิดรอบเงินสด<span>นับธนบัตรและตรวจเงินขาด/เกิน</span></button><button onclick="gotoPage('inventory')">▦ อัปเดตวัตถุดิบ<span>ใช้ไปและยอดคงเหลือ</span></button></div></div>
        </div>
        <div class="notice" style="margin-top:16px"><b>เวอร์ชันนี้ใช้ฐานข้อมูลจริง:</b> เมื่อกดบันทึก ข้อมูลจะถูกเขียนลง MySQL/MariaDB ทันที และเปิดจากเครื่องอื่นในเครือข่ายได้ในอนาคตเมื่อกำหนดสิทธิ์/การเชื่อมต่อเพิ่มเติม</div>
      </section>
