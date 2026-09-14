<?php
if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; }
?>
      <section class="page" id="page-inventory">
        <div class="page-head"><div><h2>วัตถุดิบ</h2><p>ข้อมูลใช้ไปและคงเหลือถูกเก็บในฐานข้อมูล</p></div><div class="controls"><button class="btn primary" type="button" onclick="$('addInventoryForm').hidden=false;$('newIngredientName').focus()">+ เพิ่มรายการวัตถุดิบ</button><button class="btn ghost" onclick="saveInventory()">บันทึกวัตถุดิบ</button></div></div>
        <form id="addInventoryForm" class="card form-card" style="margin-bottom:16px" hidden onsubmit="addInventory(event)">
          <div class="form-grid"><div class="field"><label for="newIngredientName">ชื่อวัตถุดิบ</label><input id="newIngredientName" required maxlength="150" placeholder="เช่น ผงชาไทย"></div><div class="field"><label for="newIngredientUnit">หน่วย/หมายเหตุ</label><input id="newIngredientUnit" maxlength="100" placeholder="เช่น ถุง / ขวด"></div></div>
          <div class="form-actions"><button type="button" class="btn ghost" onclick="$('addInventoryForm').hidden=true">ยกเลิก</button><button id="addInventoryBtn" type="submit" class="btn primary">เพิ่มวัตถุดิบ</button></div>
        </form>
        <div class="inventory-grid"><div class="table-wrap"><table class="table" style="min-width:620px"><thead><tr><th>รายการวัตถุดิบ</th><th>ใช้ไป</th><th>คงเหลือ</th><th>หน่วย/หมายเหตุ</th></tr></thead><tbody id="inventoryBody"></tbody></table></div><div class="card section"><div class="section-title"><h3>รายการที่ติดตาม</h3><span>ฐานข้อมูล</span></div><div class="stat-list"><div class="stat-line"><span><i class="green-dot"></i>จำนวนรายการ</span><b id="ingredientCount">0</b></div><div class="stat-line"><span>มีการกรอกใช้ไป</span><b id="usedCount">0</b></div><div class="stat-line"><span>มีการกรอกคงเหลือ</span><b id="remainCount">0</b></div></div><div class="notice" style="margin-top:14px">รอบถัดไปสามารถแยกเป็น “รับเข้า / เบิกใช้ / ยอดคงเหลือรายวัน” เพื่อทำ Stock Card ได้ละเอียดขึ้น</div></div></div>
      </section>
