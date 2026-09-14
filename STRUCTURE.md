# HOP Chafe' Accounting — Project Structure

โปรเจกต์ใช้โครงสร้าง **Multi-page PHP**: แต่ละเมนูมี URL ของตัวเองจริง และใช้ Layout ส่วนกลางจาก `includes/`

## URL หลักของแต่ละเมนู

- `modules/Dashboard.php` — ภาพรวมร้าน
- `modules/Daily.php` — บันทึกยอดประจำวัน
- `modules/Close.php` — ปิดรอบเงินสด
- `modules/Inventory.php` — วัตถุดิบ
- `modules/Reports.php` — รายงานย้อนหลัง
- `modules/IncomeExpense.php` — สรุปบัญชีรายรับ - รายจ่าย

`modules/index.php` เก็บไว้เพื่อรองรับลิงก์เก่า และจะ Redirect ไป `Dashboard.php`

## modules/

ไฟล์ใน `modules/*.php` เป็น Route จริงของแต่ละหน้า โดยตั้ง `$currentPage` และโหลด Layout กลางจาก `includes/page.php`

HTML ของแต่ละหน้าถูกแยกเก็บไว้ใน:

```text
modules/views/Dashboard.php
modules/views/Daily.php
modules/views/Close.php
modules/views/Inventory.php
modules/views/Reports.php
modules/views/IncomeExpense.php
```

ดังนั้นต้องการแก้เฉพาะเนื้อหาหน้าใด ให้แก้ไฟล์ใน `modules/views/` ของหน้านั้น

## includes/

- `app.php` — Authentication, Session และตัวแปรกลาง
- `navigation.php` — รายการเมนู + Route ของทุกหน้า
- `page.php` — Layout กลางสำหรับ Route ทุกหน้า
- `head.php` — `<head>`, Bootstrap, CSS
- `sidebar.php` — เมนูซ้าย Desktop
- `topbar.php` — แถบด้านบน
- `mobile_nav.php` — เมนูมือถือ
- `footer.php` — Toast และส่วนกลางท้ายหน้า
- `scripts.php` — ตัวแปร JS, Bootstrap JS และ `app.js`
- `modules.php` — ไฟล์ Legacy เท่านั้น ไม่ได้ใช้โหลดทุก Module แล้ว

## การเพิ่มเมนูใหม่

1. เพิ่มรายการใน `includes/navigation.php`
2. สร้าง Route เช่น `modules/NewPage.php`
3. สร้าง View เช่น `modules/views/NewPage.php`
4. ตั้ง `$currentPage` ใน Route ให้ตรงกับค่า `page` ใน navigation

ไม่ควรนำ HTML ของ Sidebar/Topbar ไปใส่ซ้ำในแต่ละ Module เพราะ Layout กลางอยู่ใน `includes/page.php`
