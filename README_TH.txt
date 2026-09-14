HOP Chafe' Accounting — XAMPP + MySQL/MariaDB
==============================================

ตำแหน่งโปรเจกต์:
C:\xampp\htdocs\accounting

โครงสร้างหลัก
- public\login.php       หน้า Login
- public\logout.php      หน้า Logout
- modules\index.php      หน้าโปรแกรมหลัก
- modules\Dashboard.php Dashboard
- modules\Daily.php     บันทึกยอดประจำวัน
- modules\Close.php     ปิดรอบ
- modules\Inventory.php วัตถุดิบ
- modules\Reports.php   รายงานย้อนหลัง
- modules\IncomeExpense.php สรุปบัญชีรายรับ - รายจ่าย
- plugins\bootstrap\  Bootstrap 5.3.8 ที่ระบบเรียกใช้งานจริง
- plugins\               ชุด plugins ที่แนบมา

Bootstrap
- ระบบโหลด Bootstrap 5.3.8 แบบ local ไม่ใช้ CDN
- CSS: plugins/bootstrap/css/bootstrap.min.css
- JS : plugins/bootstrap/js/bootstrap.bundle.min.js
- Bootstrap 3 เดิมจาก plugins.zip ถูกย้ายไว้ที่ plugins\bootstrap-legacy\ และระบบไม่โหลดไฟล์ชุดนั้นเพื่อป้องกัน version conflict

การเปิดใช้งาน
1) วางโฟลเดอร์ accounting ที่ C:\xampp\htdocs\accounting
2) Start Apache และ MySQL ใน XAMPP
3) ตรวจค่า DB และ base_url ใน config.php
4) เปิด http://localhost/accounting/public/login.php
5) หลัง Login ระบบจะไปที่ http://localhost/accounting/modules/Dashboard.php
6) หลังอัปเดตเวอร์ชันนี้ ให้เปิด http://localhost/accounting/install.php แล้วกด “สร้าง / อัปเดตฐานข้อมูลและตาราง” 1 ครั้ง เพื่อสร้างตาราง income_expense_records
7) เปิด http://localhost/accounting/ ได้เช่นกัน โดย .htaccess ตั้ง DirectoryIndex ไปที่ modules/Dashboard.php

ฐานข้อมูลเริ่มต้น
- Host: 127.0.0.1
- Port: 3306
- Database: hop_chafe_accounting
- User: root
- Password: ว่าง
- base_url: /accounting

หมายเหตุ
- API ใช้ PDO Prepared Statements และ CSRF token สำหรับ POST
- ฟอนต์ Sarabun อยู่ใน assets/fonts และใช้งานผ่าน assets/app.css
- หากเปลี่ยนชื่อโฟลเดอร์โปรเจกต์จาก accounting ให้แก้ base_url ใน config.php ให้ตรงด้วย

ฟังก์ชันสรุปบัญชีรายรับ - รายจ่าย
- เมนูอยู่ต่อจาก “รายงานย้อนหลัง”
- ยอดยกมาอ้างอิงยอดคงเหลือล่าสุดก่อนวันที่ที่เลือก
- รายรับเริ่มต้น: เงินหน้าร้าน, เงินโอน, Lineman, Grab, Shopee, ไทยช่วยไทย
- รายจ่ายเริ่มต้น: ค่าเช่าที่, ค่าน้ำแข็ง, ค่าแรง
- เพิ่ม/ลบรายการรับและรายการจ่ายเองได้
- ดึงยอดจากเมนูบันทึกยอดประจำวันมาเติมให้อัตโนมัติได้
- แก้ไข/ลบรายการย้อนหลังและ Export CSV ได้

อัปเดตสรุปบัญชีรายรับ-รายจ่าย (รองรับหลายรายการต่อวัน)
- ตารางประวัติจะแสดงทุกรายการของเดือนที่เลือก เรียงตามวันที่และลำดับการบันทึก
- หลังบันทึก/อัปเดต ระบบจะล้างยอดรายรับและรายจ่ายกลับเป็น 0 เพื่อพร้อมกรอกรายการถัดไป
- สามารถบันทึกหลายรายการในวันเดียวกันได้ โดยไม่เขียนทับรายการเดิม
- หลังนำไฟล์ชุดนี้ไปทับ โปรดเปิด install.php แล้วกด “สร้าง / อัปเดตฐานข้อมูลและตาราง” 1 ครั้ง เพื่อปลด UNIQUE ของ record_date โดยข้อมูลเดิมไม่ถูกลบ


=== รายงาน PDF สรุปบัญชีรายรับ - รายจ่าย ===
- หน้า "สรุปบัญชีรายรับ - รายจ่าย" มีเครื่องมือออกรายงานอยู่เหนือ "ประวัติสรุปรายรับ - รายจ่าย"
- เลือกวันที่เริ่มต้นและวันที่สิ้นสุดได้ รวมถึงช่วงที่ข้ามเดือนได้
- ระบบดึงข้อมูลจากตาราง income_expense_records ตามช่วงวันที่ที่เลือก และจัดรายการรับ/รายการจ่ายลงแบบรายงาน A4 แนวนอน
- เมื่อกด "ออกรายงาน PDF" ระบบจะเปิดหน้าตัวอย่างและเรียกหน้าต่าง Print ของ Browser อัตโนมัติ ให้เลือก "Save to PDF / บันทึกเป็น PDF"
- รายงานใช้ฟอนต์ Sarabun จาก assets/fonts และทำงานแบบ Local ไม่ต้องโหลดฟอนต์จาก Internet
- ข้อมูลผู้ประกอบการ/สถานประกอบการบนรายงานแก้ได้ที่ report_config.php โดยไม่กระทบค่าฐานข้อมูลใน config.php
- การอัปเดตส่วนนี้ไม่เปลี่ยนโครงสร้างฐานข้อมูล จึงไม่ต้องรัน install.php ใหม่

อัปเดตรายงาน PDF (แยกตามวันที่)
- เลือกช่วงวันที่จากหน้า สรุปบัญชีรายรับ - รายจ่าย ได้เหมือนเดิม
- รายงาน PDF จะแยก 1 วันที่ต่อ 1 หน้า A4 แนวนอน
- ถ้าวันเดียวกันบันทึกหลายรายการ จะรวมรายการทั้งหมดของวันนั้นในหน้าเดียว
- ท้ายทุกหน้าจะแสดงวันและเวลาที่พิมพ์เอกสารตามเวลา Asia/Bangkok

โครงสร้างโปรเจกต์
-----------------
หน้าหลักถูกแยกเป็น layout/includes แล้ว ดูรายละเอียดที่ STRUCTURE.md
เมนูหลักแก้ไขจาก includes/navigation.php เพียงจุดเดียว
