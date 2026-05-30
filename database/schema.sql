-- EduSaaS - Primary School Management
-- MySQL schema + sample seed data
-- Run via: mysql -u root -p < schema.sql
-- Or use install.php in your browser.

CREATE DATABASE IF NOT EXISTS school_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_saas;

DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS sliders;
DROP TABLE IF EXISTS gallery;
DROP TABLE IF EXISTS school_messages;
DROP TABLE IF EXISTS notices;
DROP TABLE IF EXISTS school_info;
DROP TABLE IF EXISTS settings;

-- Admin / staff login accounts
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','teacher') NOT NULL DEFAULT 'admin',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    section VARCHAR(20) DEFAULT NULL,
    teacher_id INT DEFAULT NULL,
    capacity INT DEFAULT 40,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) UNIQUE,
    phone VARCHAR(30),
    subject VARCHAR(80),
    designation VARCHAR(120) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    gender ENUM('male','female','other') DEFAULT 'male',
    joined_on DATE,
    status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(20) NOT NULL,
    name VARCHAR(120) NOT NULL,
    class_id INT,
    gender ENUM('male','female','other') DEFAULT 'male',
    dob DATE,
    parent_name VARCHAR(120),
    phone VARCHAR(30),
    address VARCHAR(255),
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    code VARCHAR(20),
    full_marks INT DEFAULT 100,
    pass_marks INT DEFAULT 33
) ENGINE=InnoDB;

CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_term ENUM('first','mid','final') NOT NULL DEFAULT 'final',
    marks_obtained INT NOT NULL,
    grade VARCHAR(5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_result (student_id, subject_id, exam_term)
) ENGINE=InnoDB;

-- ==== Public site tables ====

CREATE TABLE school_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_bn VARCHAR(255),
    name_en VARCHAR(255),
    tagline VARCHAR(255),
    address VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(120),
    website VARCHAR(120),
    eiin VARCHAR(20),
    established YEAR,
    logo VARCHAR(255),
    about_bn TEXT,
    map_embed TEXT
) ENGINE=InnoDB;

CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    pdf_url VARCHAR(255) DEFAULT NULL,
    pdf_size INT DEFAULT NULL,
    is_floating TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE school_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120),
    designation VARCHAR(120),
    photo VARCHAR(255),
    content TEXT,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255),
    caption VARCHAR(255),
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE sliders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255),
    caption VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

-- ==== Sample data ====
INSERT INTO teachers (name,email,phone,subject,designation,photo,gender,joined_on,status) VALUES
('Sarah Johnson','sarah.j@school.test','555-0101','English','Senior Teacher','https://placehold.co/200x200/1a237e/ffffff?text=SJ','female','2022-04-12','active'),
('Michael Brown','m.brown@school.test','555-0102','Mathematics','Head of Department','https://placehold.co/200x200/283593/ffffff?text=MB','male','2021-08-01','active'),
('Priya Sharma','priya.s@school.test','555-0103','Science','Senior Teacher','https://placehold.co/200x200/1a237e/ffffff?text=PS','female','2023-01-15','active'),
('David Wilson','d.wilson@school.test','555-0104','Social Studies','Assistant Teacher','https://placehold.co/200x200/283593/ffffff?text=DW','male','2020-09-10','active'),
('Aisha Khan','aisha.k@school.test','555-0105','Computer','Assistant Teacher','https://placehold.co/200x200/1a237e/ffffff?text=AK','female','2024-02-20','active'),
('Robert Miller','r.miller@school.test','555-0106','Physical Ed.','Sports Coordinator','https://placehold.co/200x200/283593/ffffff?text=RM','male','2019-06-05','inactive');

INSERT INTO classes (name,section,teacher_id,capacity) VALUES
('Grade 1','A',1,35),
('Grade 2','A',2,35),
('Grade 3','A',3,40),
('Grade 4','A',4,40),
('Grade 5','A',5,40),
('Grade 5','B',2,40);

INSERT INTO subjects (name,code,full_marks,pass_marks) VALUES
('English','ENG',100,33),
('Mathematics','MATH',100,33),
('Science','SCI',100,33),
('Social Studies','SST',100,33),
('Computer','COMP',100,33);

INSERT INTO students (roll_no,name,class_id,gender,dob,parent_name,phone,address,photo,status) VALUES
('STU-1001','Aria Khan',5,'female','2014-03-12','Imran Khan','555-1001','12 Maple Ave','https://placehold.co/200x250/4f46e5/ffffff?text=AK','active'),
('STU-1002','Daniel Ortiz',4,'male','2015-07-22','Maria Ortiz','555-1002','45 Oak Street','https://placehold.co/200x250/0ea5e9/ffffff?text=DO','active'),
('STU-1003','Zoya Ahmed',3,'female','2016-11-04','Fahim Ahmed','555-1003','7 Pine Road','https://placehold.co/200x250/ec4899/ffffff?text=ZA','active'),
('STU-1004','Mason Lee',2,'male','2017-05-18','Jenny Lee','555-1004','89 Cedar Blvd','https://placehold.co/200x250/10b981/ffffff?text=ML','active'),
('STU-1005','Emma Watson',1,'female','2018-01-30','Chris Watson','555-1005','22 Elm Court','https://placehold.co/200x250/8b5cf6/ffffff?text=EW','active'),
('STU-1006','Liam Garcia',5,'male','2014-09-09','Sofia Garcia','555-1006','33 Birch Way','https://placehold.co/200x250/f59e0b/ffffff?text=LG','active'),
('STU-1007','Olivia Patel',4,'female','2015-12-15','Raj Patel','555-1007','11 Willow Ln','https://placehold.co/200x250/ef4444/ffffff?text=OP','active'),
('STU-1008','Noah Cooper',3,'male','2016-02-27','Amy Cooper','555-1008','4 Cherry St',NULL,'inactive');

INSERT INTO results (student_id,subject_id,exam_term,marks_obtained,grade) VALUES
-- Aria Khan (id 1) - final
(1,1,'final',88,'A'),(1,2,'final',92,'A+'),(1,3,'final',79,'B+'),(1,4,'final',85,'A'),(1,5,'final',95,'A+'),
-- Daniel Ortiz (id 2) - final
(2,1,'final',75,'B+'),(2,2,'final',68,'B'),(2,3,'final',82,'A'),(2,4,'final',74,'B+'),(2,5,'final',80,'A'),
-- Zoya Ahmed (id 3) - final
(3,1,'final',91,'A+'),(3,2,'final',85,'A'),(3,3,'final',88,'A'),(3,4,'final',79,'B+'),(3,5,'final',93,'A+'),
-- Mason Lee (id 4)
(4,1,'final',60,'C'),(4,2,'final',55,'C'),(4,3,'final',70,'B'),(4,4,'final',65,'B'),(4,5,'final',72,'B+'),
-- Emma Watson (id 5)
(5,1,'final',82,'A'),(5,2,'final',78,'B+'),(5,3,'final',85,'A'),(5,4,'final',80,'A'),(5,5,'final',88,'A');



-- ==== Public site seed data ====

INSERT INTO school_info (name_bn, name_en, tagline, address, phone, email, website, eiin, established, logo, about_bn, map_embed) VALUES
('আদর্শ সরকারি উচ্চ বিদ্যালয়',
 'Adarsha Government High School',
 'শিক্ষা, সংস্কৃতি ও মূল্যবোধের আলোয় আলোকিত আগামী',
 'School Road, Dhaka, Bangladesh',
 '+880-2-1234567',
 'info@adarshaschool.edu.bd',
 'https://adarshaschool.edu.bd',
 '115349',
 1955,
 'https://placehold.co/200x200/1a237e/f9a825?text=School&font=roboto',
 'আমাদের শিক্ষা প্রতিষ্ঠানে আপনাকে স্বাগতম। আমরা মানসম্মত শিক্ষা এবং শিক্ষার্থীদের উজ্জ্বল ভবিষ্যৎ গড়ার লক্ষে নিরলসভাবে কাজ করে যাচ্ছি।',
 'https://www.openstreetmap.org/export/embed.html?bbox=90.39%2C23.78%2C90.42%2C23.81&amp;layer=mapnik');

INSERT INTO sliders (image, caption, is_active, sort_order) VALUES
('https://picsum.photos/seed/school1/1400/500', 'আমাদের নতুন একাডেমিক ভবন উদ্বোধন', 1, 1),
('https://picsum.photos/seed/school2/1400/500', 'বার্ষিক ক্রীড়া প্রতিযোগিতা ২০২৬', 1, 2),
('https://picsum.photos/seed/school3/1400/500', 'বিজ্ঞান মেলায় শিক্ষার্থীদের অংশগ্রহণ', 1, 3);

INSERT INTO notices (title, body, pdf_url, is_floating, is_published, posted_at) VALUES
('ভর্তি কার্যক্রম ২০২৬-২৭ শুরু হয়েছে', 'আগামী শিক্ষাবর্ষের জন্য অনলাইন আবেদন গ্রহণ শুরু হয়েছে। বিস্তারিত নোটিশ দেখুন।', NULL, 1, 1, NOW() - INTERVAL 1 DAY),
('বার্ষিক পরীক্ষার সময়সূচী প্রকাশ', 'সকল শ্রেণীর বার্ষিক পরীক্ষার সময়সূচী ওয়েবসাইটে প্রকাশ করা হয়েছে।', NULL, 0, 1, NOW() - INTERVAL 4 DAY),
('পরিচ্ছন্নতা সপ্তাহ পালন', 'আগামী ১৫ জুন থেকে ২১ জুন পর্যন্ত পরিচ্ছন্নতা সপ্তাহ পালিত হবে।', NULL, 0, 1, NOW() - INTERVAL 7 DAY),
('শিক্ষক প্রশিক্ষণ কর্মশালা', 'মাসিক শিক্ষক প্রশিক্ষণ কর্মশালা আগামী শনিবার অনুষ্ঠিত হবে।', NULL, 0, 1, NOW() - INTERVAL 12 DAY),
('গ্রীষ্মকালীন ছুটির বিজ্ঞপ্তি', 'গ্রীষ্মকালীন ছুটি ১ জুলাই থেকে ১৫ জুলাই পর্যন্ত।', NULL, 0, 1, NOW() - INTERVAL 18 DAY),
('পাঠ্যপুস্তক বিতরণ', 'নতুন শিক্ষাবর্ষের পাঠ্যপুস্তক বিতরণ ১ জানুয়ারি থেকে শুরু।', NULL, 0, 1, NOW() - INTERVAL 25 DAY);

INSERT INTO school_messages (name, designation, photo, content, sort_order) VALUES
('জনাব মোঃ রফিকুল ইসলাম',
 'প্রধান শিক্ষক',
 'https://placehold.co/200x250/1a237e/ffffff?text=Principal',
 'একটি সভ্য জাতি বিনির্মানে শিক্ষার বিকল্প নেই। শিক্ষার রস ও মাধুর্য নিয়ে প্রতিটি মানুষ যখন নিজেকে সঠিক কাজে উৎসর্গ করে তখন জাতির কল্যাণ নিশ্চিত হয়। আমাদের বিদ্যালয়টি শুরু থেকেই শিক্ষার গুণগত মানকে সমুন্নত রেখে আসছে। দক্ষ গভর্নিং বডির প্রত্যক্ষ তত্ত্বাবধানে অভিজ্ঞ ও সৃজনশীল শিক্ষক-শিক্ষিকার পরিচর্যায় গড়ে উঠছে এক আত্মবিশ্বাসী প্রজন্ম।',
 1),
('জনাব মোছাঃ ফাতেমা বেগম',
 'সহকারী প্রধান শিক্ষক',
 'https://placehold.co/200x250/283593/ffffff?text=Vice',
 'শিক্ষার্থীদের নৈতিক, মানসিক ও বুদ্ধিবৃত্তিক বিকাশের লক্ষ্যে আমরা প্রতিনিয়ত কাজ করে যাচ্ছি। আধুনিক প্রযুক্তিনির্ভর পাঠদান, সহশিক্ষা কার্যক্রম ও ক্রীড়া সংস্কৃতির মাধ্যমে শিক্ষার্থীদের সর্বাঙ্গীণ বিকাশ সাধনই আমাদের লক্ষ্য।',
 2);

INSERT INTO gallery (image, caption, sort_order) VALUES
('https://picsum.photos/seed/g1/600/400', 'বার্ষিক ক্রীড়া দিবস', 1),
('https://picsum.photos/seed/g2/600/400', 'বিজ্ঞান মেলা ২০২৬', 2),
('https://picsum.photos/seed/g3/600/400', 'সাংস্কৃতিক অনুষ্ঠান', 3),
('https://picsum.photos/seed/g4/600/400', 'শ্রেণীকক্ষ কার্যক্রম', 4),
('https://picsum.photos/seed/g5/600/400', 'কম্পিউটার ল্যাব', 5),
('https://picsum.photos/seed/g6/600/400', 'লাইব্রেরি', 6);



-- ==== Settings (theme + global) ====
CREATE TABLE settings (
    `key`   VARCHAR(64) PRIMARY KEY,
    `value` TEXT
) ENGINE=InnoDB;

INSERT INTO settings (`key`,`value`) VALUES
('theme_primary', '#1a237e'),
('theme_accent',  '#f9a825'),
('floating_notice_enabled', '1');



-- Homepage section visibility (super admin can toggle each section on/off)
INSERT INTO settings (`key`,`value`) VALUES
('home_show_hero',         '1'),
('home_show_stats',        '1'),
('home_show_quick_menu',   '1'),
('home_show_notices',      '1'),
('home_show_messages',     '1'),
('home_show_services',     '1'),
('home_show_gallery',      '1'),
('home_show_extras',       '1'),
('home_show_map',          '1'),
('home_show_about_widget', '1'),
('home_show_calendar',     '1'),
('home_show_anthem',       '1'),
('home_show_links',        '1');



-- ==== CMS pages (super admin can create unlimited pages) ====
DROP TABLE IF EXISTS pages;
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    body MEDIUMTEXT,
    is_published TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pages (slug, title, body, is_published) VALUES
('about-institution',     'প্রতিষ্ঠান সম্পর্কে',          '<p>আমাদের শিক্ষা প্রতিষ্ঠানে আপনাকে স্বাগতম। এখানে বিস্তারিত তথ্য সুপার অ্যাডমিন প্যানেল থেকে এডিট করা যাবে।</p>', 1),
('honor-board',            'অনার বোর্ড',                '<p>সেরা শিক্ষার্থীদের তালিকা এখানে প্রকাশিত হবে।</p>', 1),
('founder-donor-info',     'প্রতিষ্ঠাতা ও দানকারী',       '<p>প্রতিষ্ঠাতা ও দানকারীগণের তথ্য।</p>', 1),
('annual-report',          'এনুয়াল রিপোর্ট',             '<p>বার্ষিক প্রতিবেদন এখানে যোগ করুন।</p>', 1),
('class-routine',          'ক্লাস রুটিন',               '<p>সকল শ্রেণীর ক্লাস রুটিন এখানে প্রকাশিত হবে।</p>', 1),
('exam-routine',           'পরীক্ষার রুটিন',            '<p>বার্ষিক ও সাময়িক পরীক্ষার রুটিন।</p>', 1),
('syllabus',               'সিলেবাস',                  '<p>শ্রেণীভিত্তিক সিলেবাস এখানে প্রকাশিত হবে।</p>', 1),
('admission',              'অনলাইন ভর্তি',              '<p>ভর্তির আবেদন প্রক্রিয়া ও যোগ্যতা।</p>', 1),
('admit-card',             'এডমিট কার্ড',              '<p>এডমিট কার্ড সংগ্রহের পদ্ধতি।</p>', 1),
('fees-payment',           'বেতন পরিশোধ',              '<p>মাসিক বেতন ও অন্যান্য ফি পরিশোধের নিয়ম।</p>', 1),
('e-book',                 'ই-বুক',                    '<p>ই-বুক ও ডিজিটাল লেকচার শীট।</p>', 1),
('manual',                 'ম্যানুয়াল',                '<p>প্রতিষ্ঠান পরিচালনার ম্যানুয়াল।</p>', 1),
('rules-regulations',      'বিধি বিধান',               '<p>প্রতিষ্ঠানের নিয়মাবলী।</p>', 1),
('hostel-information',     'হোস্টেলের তথ্য',            '<p>হোস্টেলের সুবিধা ও আবাসন তথ্য।</p>', 1),
('scholarship',            'উপবৃত্তি',                  '<p>উপবৃত্তি ও বৃত্তি সংক্রান্ত তথ্য।</p>', 1),
('contact',                'যোগাযোগ',                  '<p>যোগাযোগের ঠিকানা ও ফোন নম্বর।</p>', 1);



-- ==== Extracurricular activities (সহ-পাঠক্রমিক) ====
DROP TABLE IF EXISTS extracurricular;
CREATE TABLE extracurricular (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    image VARCHAR(255),
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO extracurricular (name, image, sort_order) VALUES
('Nature Club',     'https://picsum.photos/seed/nature/400/200', 1),
('Rover Scout',     'https://picsum.photos/seed/rover/400/200',  2),
('BNCC',            'https://picsum.photos/seed/bncc/400/200',   3),
('Red Crescent',    'https://picsum.photos/seed/bdrcs/400/200',  4),
('Debate Club',     'https://picsum.photos/seed/debate/400/200', 5),
('Music Club',      'https://picsum.photos/seed/music/400/200',  6);



-- ==== Academic years (year-aware result tagging) ====
DROP TABLE IF EXISTS academic_years;
CREATE TABLE academic_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(40) NOT NULL,
    start_date DATE,
    end_date DATE,
    is_current TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_year (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO academic_years (name, start_date, end_date, is_current) VALUES
('2025', '2025-01-01', '2025-12-31', 0),
('2026', '2026-01-01', '2026-12-31', 1),
('2027', '2027-01-01', '2027-12-31', 0);

-- ==== Audit log (anti-fraud history trail) ====
DROP TABLE IF EXISTS audit_log;
CREATE TABLE audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT, user_name VARCHAR(120), user_role VARCHAR(40),
    action VARCHAR(40) NOT NULL,
    entity_type VARCHAR(40) NOT NULL,
    entity_id INT, entity_label VARCHAR(255),
    changes_json MEDIUMTEXT,
    ip_address VARCHAR(45), user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user (user_id), INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE results ADD COLUMN year_id INT DEFAULT NULL AFTER exam_term;
ALTER TABLE results DROP INDEX uniq_result;
ALTER TABLE results ADD UNIQUE KEY uniq_result (student_id, subject_id, exam_term, year_id);
UPDATE results SET year_id = (SELECT id FROM academic_years WHERE is_current=1 LIMIT 1);

ALTER TABLE teachers ADD COLUMN subject_id INT DEFAULT NULL AFTER subject;

INSERT INTO settings (`key`,`value`) VALUES
('current_year_id', (SELECT CAST(id AS CHAR) FROM academic_years WHERE is_current=1 LIMIT 1));
