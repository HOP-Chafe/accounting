<?php
if (!defined('ACCOUNTING_APP')) { http_response_code(403); exit; }
?>
      <section class="page" id="page-income-expense">
        <div class="page-head">
          <div>
            <h2>สรุปบัญชีรายรับ - รายจ่าย</h2>
            <p>บันทึกตามแบบฟอร์ม Excel โดยยอดยกมาจะอ้างอิงยอดคงเหลือล่าสุดเฉพาะภายในเดือนเดียวกัน</p>
          </div>
          <div class="controls">
            <label style="font-size:12px;color:var(--muted)">เดือนที่ใช้งาน</label>
            <select id="ieMonth" onchange="changeIncomeExpenseMonth()"></select>
          </div>
        </div>

        <form class="card ledger-card" id="incomeExpenseForm" onsubmit="saveIncomeExpense(event)">
          <div class="ledger-heading">
            <div>
              <span class="ledger-kicker">HOP Chafe'</span>
              <h3>สรุปบัญชีรายรับ - รายจ่าย</h3>
              <p id="ieYearLabel">ประจำปี —</p>
            </div>
            <div class="ledger-date-box">
              <label for="ieDate">วันที่บันทึก</label>
              <input type="date" id="ieDate" required onchange="loadIncomeExpenseDate()">
            </div>
          </div>

          <div class="ledger-tools">
            <div class="field carry-field">
              <label>ยอดยกมาภายในเดือน</label>
              <div class="suffix"><input type="number" step="0.01" id="ieCarry" value="0" onfocus="clearZeroOnFocus(this)" onblur="restoreZeroOnBlur(this)" oninput="updateIncomeExpenseCalc()"><b>บาท</b></div>
              <small id="ieCarryHint">ระบบจะค้นหายอดคงเหลือล่าสุดเฉพาะภายในเดือนเดียวกัน</small>
            </div>
            <div class="field note-field">
              <label>หมายเหตุ</label>
              <input id="ieNote" maxlength="500" placeholder="เช่น ค่าใช้จ่ายพิเศษ / รายละเอียดเพิ่มเติม">
            </div>
            <div class="ledger-tool-buttons">
              <button type="button" class="btn ghost" onclick="loadPreviousBalance(true)">↻ ดึงยอดยกมาใหม่</button>
              <button type="button" class="btn ghost" onclick="prefillIncomeExpenseFromDaily()">ดึงยอดจากบันทึกประจำวัน</button>
            </div>
          </div>

          <div class="ledger-grid">
            <div class="ledger-panel income-panel">
              <div class="ledger-panel-head">
                <div><span>รายการรับ</span><small>รายรับของวันที่เลือก</small></div>
                <button type="button" class="btn ghost small" onclick="addLedgerRow('income')">+ เพิ่มรายการ</button>
              </div>
              <div class="ledger-carry-row">
                <span>ยอดยกมา</span>
                <strong id="ieCarryDisplay">฿0.00</strong>
              </div>
              <div class="ledger-rows" id="ieIncomeRows"></div>
              <div class="ledger-total-row"><span>รวมรายรับวันนี้</span><strong id="ieIncomeTotal">฿0.00</strong></div>
              <div class="ledger-total-row grand"><span>รวมทั้งสิ้น</span><strong id="ieGrandTotal">฿0.00</strong></div>
            </div>

            <div class="ledger-panel expense-panel">
              <div class="ledger-panel-head">
                <div><span>รายการจ่าย</span><small>รายจ่ายของวันที่เลือก</small></div>
                <button type="button" class="btn ghost small" onclick="addLedgerRow('expense')">+ เพิ่มรายการ</button>
              </div>
              <div class="ledger-rows" id="ieExpenseRows"></div>
              <div class="ledger-spacer"></div>
              <div class="ledger-total-row"><span>รวมรายจ่าย</span><strong id="ieExpenseTotal">฿0.00</strong></div>
              <div class="ledger-total-row grand balance"><span>ยอดคงเหลือ</span><strong id="ieBalance">฿0.00</strong></div>
            </div>
          </div>

          <div class="form-actions ledger-actions">
            <button type="button" class="btn ghost" onclick="resetIncomeExpenseForm(true)">ล้าง/เริ่มรายการใหม่</button>
            <button type="submit" class="btn primary" id="ieSaveBtn">บันทึกสรุปบัญชี</button>
          </div>
        </form>

        <div class="card section" style="margin-top:18px">
          <div class="section-title">
            <div>
              <h3>ประวัติสรุปรายรับ - รายจ่าย</h3>
              <span id="ieHistoryLabel">—</span>
            </div>
          </div>

          <div class="ie-report-tools">
            <div class="ie-report-copy">
              <strong>ออกรายงาน PDF</strong>
              <small>เลือกช่วงวันที่ ระบบจะดึงข้อมูลจากประวัติสรุปรายรับ - รายจ่าย และแยกรายงาน 1 วันที่ต่อ 1 หน้า</small>
            </div>
            <div class="ie-report-range">
              <label>ตั้งแต่วันที่
                <input type="date" id="ieReportFrom">
              </label>
              <span class="ie-report-sep">ถึง</span>
              <label>ถึงวันที่
                <input type="date" id="ieReportTo">
              </label>
            </div>
            <div class="ie-report-actions">
              <button type="button" class="btn ghost" onclick="syncIncomeExpenseReportRangeFromMonth(true)">ใช้เดือนที่เลือก</button>
              <button type="button" class="btn primary" onclick="openIncomeExpensePdfReport()">▤ ออกรายงาน PDF</button>
            </div>
          </div>

          <div class="table-wrap">
            <table class="table income-expense-history" style="min-width:850px">
              <thead><tr><th>วันที่</th><th>ยอดยกมา</th><th>รายรับวันนี้</th><th>รวมทั้งสิ้น</th><th>รายจ่าย</th><th>ยอดคงเหลือ</th><th>หมายเหตุ</th><th>จัดการ</th></tr></thead>
              <tbody id="ieHistoryBody"><tr><td colspan="8" class="empty">กำลังโหลด...</td></tr></tbody>
              <tfoot id="ieHistoryFoot"></tfoot>
            </table>
          </div>
        </div>
      </section>
