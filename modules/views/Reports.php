<?php
if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; }
?>
      <section class="page" id="page-reports">
        <div class="page-head"><div><h2>รายงานย้อนหลัง</h2><p>แก้ไขและลบรายการจากฐานข้อมูลได้โดยตรง</p></div><div class="controls"><select id="reportMonth" onchange="renderReports()"></select><button class="btn ghost" onclick="exportCSV()">Export CSV</button></div></div>
        <div class="table-wrap"><table class="table"><thead><tr><th>วันที่</th><th>หน้าร้าน</th><th>LINE MAN</th><th>GRAB</th><th>SHOPEE</th><th>รวมแก้ว</th><th>รายรับ</th><th>รายจ่าย</th><th>สุทธิ</th><th>จัดการ</th></tr></thead><tbody id="reportBody"></tbody><tfoot id="reportFoot"></tfoot></table></div>
      </section>
