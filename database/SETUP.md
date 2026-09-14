# Database setup

โปรเจกต์นี้ใช้ MySQL/MariaDB ผ่าน XAMPP และแยก entry point ตามโครงสร้างใหม่ดังนี้

- `public/login.php` — หน้าเข้าสู่ระบบ
- `public/logout.php` — ออกจากระบบ (POST + CSRF)
- `modules/Dashboard.php` — หน้า Dashboard หลัก (แต่ละเมนูมีไฟล์ Route ของตัวเองใน `modules/`)
- `api.php` — API สำหรับบันทึก/อ่านข้อมูล
- `plugins/bootstrap/` — Bootstrap 5.3.8 ที่ใช้งานจริง
- `plugins/` — plugin bundle ที่ผู้ใช้แนบมา (Bootstrap 3 เดิมถูกเก็บไว้ที่ `plugins/bootstrap-legacy/` และไม่ได้ถูกเรียกใช้งาน เพื่อไม่ให้ชนกับ Bootstrap 5)

ตั้งค่าฐานข้อมูลได้ใน `config.php` และตั้งค่า URL หลักของโปรเจกต์ด้วย `base_url` (ค่าเริ่มต้น `/accounting`)

สำหรับติดตั้งฐานข้อมูลเดิมสามารถนำเข้า `database.sql` และ `users.sql` ผ่าน phpMyAdmin ตามความเหมาะสม จากนั้นเข้าสู่ระบบที่ `http://localhost/accounting/public/login.php`
