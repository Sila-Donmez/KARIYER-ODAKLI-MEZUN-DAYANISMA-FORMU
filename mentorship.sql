
-- ==========================================
-- DATABASE OLUŞTURMA
-- ==========================================
DROP DATABASE IF EXISTS mentorship_db;

CREATE DATABASE mentorship_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE mentorship_db;

-- ==========================================
-- USERS & ROL TABLOLARI
-- ==========================================

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'graduate', 'admin') NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    gender ENUM('male', 'female', 'other') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE TABLE students (
    user_id INT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(255) NOT NULL,
    grade INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE graduates (
    user_id INT PRIMARY KEY,
    graduate_year INT NOT NULL,
    document_link TEXT NULL,
    is_open_to_mentorship BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE admins (
    user_id INT PRIMARY KEY,
    admin_level INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- PROFİL & SKILL
-- ==========================================

CREATE TABLE profiles (
    user_id INT PRIMARY KEY,
    bio TEXT NULL,
    website_url VARCHAR(255) NULL,
    linkedin_url VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    skill_name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE user_skills (
    user_id INT,
    skill_id INT,
    PRIMARY KEY (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- ==========================================
-- COMPANY & EXPERIENCE
-- ==========================================

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(255) NOT NULL
);

CREATE TABLE positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_name VARCHAR(255) NOT NULL
);

CREATE TABLE experiences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    position_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
);

CREATE TABLE company_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- AUTH LOGS
-- ==========================================

CREATE TABLE auth_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    is_success BOOLEAN DEFAULT FALSE,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- MENTORLUK
-- ==========================================

CREATE TABLE mentor_ads (
    ads_id INT PRIMARY KEY AUTO_INCREMENT,
    graduate_id INT NOT NULL,
    expertise VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (graduate_id) REFERENCES graduates(user_id) ON DELETE CASCADE
);

CREATE TABLE mentor_applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    ads_id INT NOT NULL,
    student_id INT NOT NULL,
    message TEXT,
    status ENUM('Waiting', 'Approved', 'Rejected') DEFAULT 'Waiting',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ads_id) REFERENCES mentor_ads(ads_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE
);

-- ==========================================
-- FORUM
-- ==========================================

CREATE TABLE forum_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE forum_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FULLTEXT(title, content)
);

CREATE TABLE forum_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL,
    content TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES forum_comments(id) ON DELETE SET NULL
);

-- ==========================================
-- DUMMY DATA
-- ==========================================

-- ==========================================
-- USERS
-- ==========================================

INSERT INTO users (first_name, last_name, email, password, role, is_verified) VALUES 
('Ayşe', 'Yılmaz', '1111111111@ogrenci.karabuk.edu.tr', '$2y$10$testhash', 'student', 1),
('Ahmet', 'Kara', '1111111112@ogrenci.karabuk.edu.tr', '$2y$10$testhash', 'student', 1),
('Zeynep', 'Koç', '1111111113@ogrenci.karabuk.edu.tr', '$2y$10$testhash', 'student', 1),

('Mehmet', 'Kaya', 'mehmet@gmail.com', '$2y$10$testhash', 'graduate', 1),
('Elif', 'Demir', 'elif@hotmail.com', '$2y$10$testhash', 'graduate', 1),
('Can', 'Aydın', 'can@gmail.com', '$2y$10$testhash', 'graduate', 1),

('Admin', 'Sistem', 'admin@mentorship.com', '$2y$10$testhash', 'admin', 1);

-- ==========================================
-- STUDENTS
-- ==========================================

INSERT INTO students (user_id, student_number, department, grade) VALUES 
(1, '1111111111', 'Bilgisayar Mühendisliği', 3),
(2, '1111111112', 'Yazılım Mühendisliği', 2),
(3, '1111111113', 'Bilgisayar Mühendisliği', 4);

-- ==========================================
-- GRADUATES
-- ==========================================

INSERT INTO graduates (user_id, graduate_year, is_open_to_mentorship) VALUES 
(4, 2020, 1),
(5, 2019, 1),
(6, 2018, 1);

-- ==========================================
-- ADMINS
-- ==========================================

INSERT INTO admins (user_id, admin_level) VALUES 
(7, 1);

-- ==========================================
-- SKILLS
-- ==========================================

INSERT INTO skills (skill_name) VALUES 
('PHP'), ('MySQL'), ('Python'), ('JavaScript'),
('AI'), ('Machine Learning'), ('Cyber Security'),
('HTML/CSS'), ('React'), ('Docker');

-- ==========================================
-- USER SKILLS
-- ==========================================

INSERT INTO user_skills (user_id, skill_id) VALUES 
(1,1),(1,2),(1,4),
(2,3),(2,8),
(3,1),(3,9),
(4,5),(4,6),
(5,3),(5,7),
(6,4),(6,10);

-- ==========================================
-- COMPANIES
-- ==========================================

INSERT INTO companies (name, industry) VALUES
('Google', 'Yazılım'),
('Microsoft', 'Yazılım'),
('Amazon', 'Cloud'),
('Meta', 'Sosyal Medya'),
('Trendyol', 'E-Ticaret'),
('Hepsiburada', 'E-Ticaret'),
('Getir', 'Mobil'),
('ASELSAN', 'Savunma'),
('HAVELSAN', 'Savunma'),
('TÜBİTAK', 'Ar-Ge'),
('Peak Games', 'Oyun'),
('Insider', 'SaaS'),
('Papara', 'Fintech');

-- ==========================================
-- POSITIONS
-- ==========================================

INSERT INTO positions (position_name) VALUES
('Backend Developer'),
('Frontend Developer'),
('Full Stack Developer'),
('Mobile Developer'),
('DevOps Engineer'),
('AI Engineer'),
('Data Scientist'),
('Cyber Security Specialist'),
('QA Engineer'),
('Software Intern');

-- ==========================================
-- EXPERIENCES
-- ==========================================

INSERT INTO experiences (user_id, company_id, position_id, start_date, end_date) VALUES
(4,1,1,'2021-01-01','2023-01-01'),
(4,3,5,'2023-02-01',NULL),
(5,2,6,'2019-01-01','2022-01-01'),
(5,4,7,'2022-02-01',NULL),
(6,5,3,'2020-06-01','2021-01-01'),
(6,7,4,'2018-01-01','2019-01-01');

-- ==========================================
-- COMPANY REVIEWS
-- ==========================================

INSERT INTO company_reviews (company_id, user_id, content, rating, is_anonymous) VALUES
(1,4,'Çok yoğun ama öğretici',5,0),
(2,5,'Harika ekip ortamı',5,1),
(3,4,'Work-life balance zayıf',4,0),
(5,6,'İyi başlangıç yeri',4,1),
(7,4,'Startup temposu zor',4,0),
(8,5,'Disiplinli çalışma ortamı',5,1),
(10,4,'Araştırma odaklı güzel ortam',3,0),
(13,6,'Fintech öğrenmek için iyi',5,0);

-- ==========================================
-- MENTOR ADS
-- ==========================================

INSERT INTO mentor_ads (graduate_id, expertise, title) VALUES 
(4,'Backend','PHP & Laravel Mentorluk'),
(5,'AI','Machine Learning Rehberlik'),
(6,'Cyber Security','Pentest Mentorlugu');

-- ==========================================
-- FORUM CATEGORIES
-- ==========================================

INSERT INTO forum_categories (name) VALUES
('Yazılım & Teknoloji'),
('İş Görüşmeleri & Mülakatlar'),
('Özgeçmiş (CV) Hazırlama'),
('Sektörel Sohbetler'),
('Staj İmkanları'),
('Yurtdışı Fırsatları'),
('Freelance & Uzaktan Çalışma');

-- ==========================================
-- FORUM POSTS
-- ==========================================

INSERT INTO forum_posts (user_id, category_id, title, content) VALUES
(1,1,'PHP mi Python mı?','Backend için hangisi daha mantıklı?'),
(2,1,'React öğrenilir mi?','Frontend için React yeterli mi?'),
(3,2,'Mülakatta ne soruluyor?','Junior için deneyim paylaşır mısınız?'),
(1,3,'CV dolduramıyorum','Hiç deneyim yok ne yazılır?'),
(2,4,'Yazılım sektörü zorlaştı mı?','İş bulmak zor mu artık?'),
(3,5,'Staj bulamıyorum','Geri dönüş alamıyorum'),
(1,6,'Yurtdışına nasıl gidilir?','Erasmus dışında seçenek var mı?'),
(2,7,'Freelance nasıl başlanır?','Upwork mantıklı mı?');

-- ==========================================
-- FORUM COMMENTS
-- ==========================================

INSERT INTO forum_comments (post_id, user_id, content) VALUES
(1,4,'İkisini de öğrenmek en iyisi'),
(1,5,'Python daha hızlı'),
(2,6,'React şu an çok popüler'),
(3,4,'Algoritma soruyorlar genelde'),
(4,5,'Projeler ekle'),
(5,6,'Rekabet arttı evet'),
(6,4,'LinkedIn kullan'),
(7,5,'Erasmus en kolayı'),
(8,6,'Freelance sabır işi');
