# School ERP v2.0 — Ultra Premium School Management System

> **Core PHP · No Framework · No Composer · cPanel Ready · PHP 8.1+**

![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange?style=flat-square&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple?style=flat-square&logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

---

## ✨ Features

### 🌐 Public Website
- Ultra-premium dark navy + gold landing page (Playfair Display + DM Sans)
- Hero with animated stats, gallery, notices, testimonials, class cards
- About, Gallery, Notices, Contact pages
- Online Admission form with real-time status tracker
- No admin login on public site — admin has **separate URL**

### 🔐 Authentication
- `/auth/login.php` — Student, Teacher, Parent (public)
- `/admin/login.php` — Admin only (hidden, noindex)
- OTP-based forgot password
- Session timeout (30 min)
- CSRF protection on all forms

### ⚙️ Web Installer
- 5-step WordPress-style installer at `/install/`
- Auto-detects server requirements
- Creates database, imports schema, sets up admin account
- Writes `config/config.php` automatically

### 🎓 Admin Panel
| Module | Features |
|---|---|
| Students | Add/Edit/Delete, ID Card, CSV Export, Photo upload |
| Parents | Add/Edit/Delete, portal account |
| **Parent-Student Linking** | Auto-match from admissions, manual link, create & link new parent |
| Teachers | Add/Edit/Delete, class + subject assignment |
| Classes & Sections | Accordion management |
| Subjects | Per-class or global |
| Admissions | Review, approve/reject, auto email, **auto fee invoice on approval** |
| Admission Fee | ON/OFF toggle, amount, type, due days |
| Attendance | Class/date/section filter, bulk mark, absence email alerts |
| Exams | Create, schedule, status |
| Marks Entry | Per subject/exam, live grade preview |
| Results | Auto-calculated, publish to portals, class rankings |
| Report Cards | Printable PDF-ready report card |
| Fees | Invoices, PayU payment, mark paid, overdue tracking |
| Notifications | Dashboard + email broadcast |
| Landing Page | Full CMS for hero, about, gallery, notices, stats, colours |
| Settings | SMTP, PayU, SEO, logo, favicon, social links |

### 👨‍🎓 Student Portal
- Dashboard: attendance ring, result bars, fee status, exam schedule, subject pills
- Results: full report card with print/PDF
- Attendance: monthly breakdown with calendar
- Fee Payment: PayU integration
- Profile: edit phone/address/photo, change password
- ID Card: printable

### 👨‍🏫 Teacher Portal
- Dashboard: my students with today's attendance dots, quick actions
- Mark Attendance: live button toggle, absence email to parents
- Enter Marks: live grade preview, auto-calculate results
- Report Cards: view + print for any student in class

### 👨‍👩‍👦 Parent Portal
- Dashboard: all children cards with attendance %, grade, fee due
- Per-child: attendance calendar, results with report card print
- Fee invoices across all children
- School notifications

---

## 🚀 Quick Start

### Option 1 — Web Installer (Recommended)
1. Upload all files to `public_html` (or subdirectory) via cPanel File Manager
2. Visit `https://yourdomain.com/install/`
3. Follow 5 steps: Requirements → Database → Import SQL → Admin → Done
4. **Delete `/install/` folder after completion**

### Option 2 — Manual Setup
1. Upload files to server
2. Create MySQL database in cPanel
3. Import `database.sql` via phpMyAdmin
4. Edit `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'your_db_name');
   define('SITE_URL', 'https://yourdomain.com');
   ```
5. Visit `https://yourdomain.com/admin/login.php`

**Default admin credentials:**
- Email: `admin@school.com`
- Password: `Admin@123`
- ⚠️ **Change immediately after first login**

---

## 📁 Project Structure

```
school-erp/
├── admin/              Admin panel
│   ├── admissions/     Admission management
│   ├── attendance/     Attendance marking
│   ├── classes/        Classes & sections
│   ├── exams/          Exam management
│   ├── fees/           Fee invoices
│   ├── landing/        Website CMS
│   ├── marks/          Mark entry
│   ├── notifications/  Broadcast notifications
│   ├── parents/        Parent management
│   ├── results/        Results + report cards
│   ├── settings/       System settings + admission fee
│   ├── students/       Student management + parent linking
│   ├── subjects/       Subject management
│   ├── teachers/       Teacher management
│   └── testimonials/   Testimonial CMS
├── assets/
│   ├── css/style.css   Premium dark navy+gold theme
│   └── js/main.js      Dashboard interactions
├── auth/               Login, logout, forgot password
├── config/             config.php, database.php
├── includes/           header, footer, functions, mailer, notifications
├── install/            Web installer (delete after setup)
├── notifications/      Mark read/mark all read
├── parent/             Parent portal
├── payment/            PayU gateway
├── public/             Public website pages
├── student/            Student portal
├── teacher/            Teacher portal
├── uploads/            User uploads (gitignored)
├── .htaccess           Apache rewrite + security
├── .user.ini           PHP settings for cPanel
├── database.sql        Complete schema + seed data
├── index.php           Smart redirect entry
└── robots.txt          SEO rules
```

---

## 🔗 Parent-Student Linking

Three ways to link students to their parents:

1. **At creation** — When adding a student, use the built-in Parent section to link an existing parent or create a new one simultaneously.

2. **Auto-match** — `Admin → Students → Link Parents → Auto-Match Now`  
   Reads admission form data (parent_name, parent_phone) and automatically creates/links parent records.

3. **Manual** — `Admin → Students → Link Parents`  
   Find any unlinked student → click Link Parent (pick existing) or New Parent (create + link).

---

## 📧 SMTP Configuration

`Admin → Settings → SMTP`

| Provider | Host | Port | Encryption |
|---|---|---|---|
| Gmail | smtp.gmail.com | 587 | TLS |
| Outlook | smtp.office365.com | 587 | TLS |
| cPanel | mail.yourdomain.com | 465 | SSL |

> Gmail: enable 2FA → generate App Password → use that as SMTP password.

---

## 💳 PayU Payment Gateway

`Admin → Settings → Payment`

1. Get Merchant Key + Salt from [dashboard.payu.in](https://dashboard.payu.in)
2. Enter in Admin Settings
3. Set Success/Failure URLs:
   - `https://yourdomain.com/payment/success.php`
   - `https://yourdomain.com/payment/failure.php`

---

## 🔒 Security Checklist

- [ ] Change default admin password
- [ ] Delete `/install/` folder after setup
- [ ] Enable HTTPS
- [ ] Set `SITE_URL` to `https://` URL in config
- [ ] Delete `database.sql` from server after import
- [ ] Set file permissions: directories `755`, PHP files `644`
- [ ] Enable ModSecurity in cPanel if available

---

## 🛠️ Requirements

| Item | Minimum |
|---|---|
| PHP | 8.1+ |
| MySQL | 5.7+ / MariaDB 10.4+ |
| Apache | mod_rewrite enabled |
| PHP Extensions | PDO, PDO_MySQL, openssl, fileinfo, mbstring |
| Disk Space | 100MB+ |

---

## 📋 Changelog

### v2.0.0
- Complete UI overhaul: dark navy + gold premium theme
- Playfair Display + DM Sans typography
- Admin login moved to separate URL (hidden from public)
- Parent-Student linking system with auto-match
- Admission fee toggle (ON/OFF via admin settings)
- Installer: 5-step web-based setup
- Student dashboard: circular attendance ring, subject pills, result bars
- Teacher dashboard: student list with today's attendance
- Parent dashboard: children cards with full stats
- Student report card accessible from student portal (no admin redirect)
- Premium public website: full dark theme, all sections

### v1.0.0
- Initial release

---

## 📄 License

MIT License — free to use, modify, and distribute.

---

*Built with Core PHP, no frameworks, no Composer. Runs on any shared cPanel hosting.*
