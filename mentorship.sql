-- ==========================================
-- TÜM TABLOLARI SIRASIYLA SİL
-- ==========================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS forum_comments;
DROP TABLE IF EXISTS forum_posts;
DROP TABLE IF EXISTS forum_categories;

DROP TABLE IF EXISTS mentor_applications;
DROP TABLE IF EXISTS mentor_ads;

DROP TABLE IF EXISTS auth_logs;

DROP TABLE IF EXISTS company_reviews;
DROP TABLE IF EXISTS experiences;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS companies;

DROP TABLE IF EXISTS user_skills;
DROP TABLE IF EXISTS skills;

DROP TABLE IF EXISTS profiles;

DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS graduates;
DROP TABLE IF EXISTS students;

DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- USERS
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- STUDENTS
-- ==========================================

CREATE TABLE students (
    user_id INT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    department VARCHAR(255) NOT NULL,
    grade INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- GRADUATES
-- ==========================================

CREATE TABLE graduates (
    user_id INT PRIMARY KEY,
    graduate_year INT NOT NULL,
    document_link TEXT NULL,
    is_open_to_mentorship BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- ADMINS
-- ==========================================

CREATE TABLE admins (
    user_id INT PRIMARY KEY,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- PROFILES
-- ==========================================

CREATE TABLE profiles (
    user_id INT PRIMARY KEY,
    bio TEXT NULL,
    website_url VARCHAR(255) NULL,
    linkedin_url VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- SKILLS
-- ==========================================

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
-- FORUM KATEGORİLERİ
-- ==========================================

INSERT INTO forum_categories (name) VALUES
('Yazılım & Teknoloji'),
('İş Görüşmeleri & Mülakatlar'),
('Özgeçmiş (CV) Hazırlama'),
('Sektörel Sohbetler'),
('Staj İmkanları'),
('Yurtdışı Fırsatları'),
('Freelance & Uzaktan Çalışma'),
('Diğer');

-- ==========================================
-- SKILLS
-- ==========================================

INSERT INTO skills (skill_name) VALUES
('C'),
('C++'),
('Python'),
('Java'),
('JavaScript'),
('PHP'),
('HTML'),
('CSS'),
('React'),
('Node.js'),
('SQL'),
('MySQL'),
('Linux'),
('Cyber Security'),
('Docker'),
('Git'),
('Figma'),
('UI/UX'),
('Machine Learning'),
('Excel');

-- ==========================================
-- COMPANIES
-- ==========================================

INSERT INTO companies (name, industry) VALUES
('Google', 'Technology'),
('Microsoft', 'Technology'),
('Amazon', 'E-Commerce'),
('Aselsan', 'Defense Industry'),
('HAVELSAN', 'Defense Industry'),
('Trendyol', 'E-Commerce'),
('Turkcell', 'Telecommunication'),
('Tesla', 'Automotive'),
('Getir', 'Delivery'),
('OpenAI', 'Artificial Intelligence');

-- ==========================================
-- POSITIONS
-- ==========================================

INSERT INTO positions (position_name) VALUES
('Frontend Developer'),
('Backend Developer'),
('Full Stack Developer'),
('Cyber Security Analyst'),
('DevOps Engineer'),
('Data Analyst'),
('UI/UX Designer'),
('Software Engineer'),
('Intern'),
('Mobile Developer');

-- ==========================================
-- USERS (SHA256 Hashli: Şifreler '123456')
-- ==========================================

INSERT INTO users 
(first_name, last_name, email, password, role, is_verified, gender) 
VALUES

-- ADMIN
('Admin', 'User', 'admin@mentorhub.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'admin', TRUE, 'other'),

-- STUDENTS
('Ahmet', 'Yılmaz', '2310205051@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'male'),
('Ayşe', 'Demir', '2310205052@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'female'),
('Mehmet', 'Kaya', '2310205053@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'male'),
('Zeynep', 'Çelik', '2310205054@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'female'),
('Burak', 'Aydın', '2310205055@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'male'),
('Elif', 'Koç', '2310205056@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'female'),
('Kerem', 'Şahin', '2310205057@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'male'),
('Sena', 'Aksoy', '2310205058@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'female'),
('Eren', 'Kurt', '2310205059@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'male'),
('Melisa', 'Arslan', '2310205060@ogrenci.karabuk.edu.tr', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'student', TRUE, 'female'),

-- GRADUATES
('Can', 'Öztürk', 'canozturk@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'male'),
('Selin', 'Yıldız', 'selinyildiz@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'female'),
('Mert', 'Demirtaş', 'mertdemirtas@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'male'),
('Derya', 'Korkmaz', 'deryakorkmaz@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'female'),
('Onur', 'Karaca', 'onurkaraca@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'male'),
('Buse', 'Yıldırım', 'buseyildirim@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'female'),
('Furkan', 'Ateş', 'furkanates@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'male'),
('Ceren', 'Taş', 'cerentas@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'female'),
('Emre', 'Doğan', 'emredogan@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'male'),
('Naz', 'Güneş', 'nazgunes@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'graduate', TRUE, 'female');

-- ==========================================
-- ADMINS
-- ==========================================

INSERT INTO admins (user_id)
VALUES (1);

-- ==========================================
-- STUDENTS
-- ==========================================

INSERT INTO students
(user_id, student_number, department, grade)
VALUES
(2, '2310205051', 'Computer Engineering', 1),
(3, '2310205052', 'Software Engineering', 2),
(4, '2310205053', 'Computer Engineering', 3),
(5, '2310205054', 'Artificial Intelligence Engineering', 1),
(6, '2310205055', 'Software Engineering', 4),
(7, '2310205056', 'Computer Engineering', 2),
(8, '2310205057', 'Information Systems Engineering', 3),
(9, '2310205058', 'Computer Engineering', 1),
(10, '2310205059', 'Software Engineering', 2),
(11, '2310205060', 'Computer Engineering', 4);

-- ==========================================
-- GRADUATES
-- ==========================================

INSERT INTO graduates
(user_id, graduate_year, document_link, is_open_to_mentorship)
VALUES
(12, 2021, 'documents/can_cv.pdf', TRUE),
(13, 2020, 'documents/selin_cv.pdf', TRUE),
(14, 2019, 'documents/mert_cv.pdf', TRUE),
(15, 2022, 'documents/derya_cv.pdf', TRUE),
(16, 2021, 'documents/onur_cv.pdf', FALSE),
(17, 2020, 'documents/buse_cv.pdf', TRUE),
(18, 2018, 'documents/furkan_cv.pdf', TRUE),
(19, 2023, 'documents/ceren_cv.pdf', FALSE),
(20, 2022, 'documents/emre_cv.pdf', TRUE),
(21, 2019, 'documents/naz_cv.pdf', TRUE);

-- ==========================================
-- PROFILES
-- ==========================================

INSERT INTO profiles
(user_id, bio, website_url, linkedin_url)
VALUES
(2, 'Backend geliştirme öğreniyorum.', NULL, 'https://linkedin.com/in/ahmet'),
(3, 'Frontend alanında ilerlemek istiyorum.', NULL, 'https://linkedin.com/in/ayse'),
(4, 'Siber güvenliğe ilgim var.', NULL, 'https://linkedin.com/in/mehmet'),
(5, 'Yapay zeka projeleri geliştiriyorum.', NULL, 'https://linkedin.com/in/zeynep'),
(6, 'Staj arıyorum.', NULL, 'https://linkedin.com/in/burak'),
(7, 'Python öğreniyorum.', NULL, 'https://linkedin.com/in/elif'),
(8, 'Linux kullanmayı seviyorum.', NULL, 'https://linkedin.com/in/kerem'),
(9, 'UI/UX çalışıyorum.', NULL, 'https://linkedin.com/in/sena'),
(10, 'Mobil uygulama geliştirme ilgimi çekiyor.', NULL, 'https://linkedin.com/in/eren'),
(11, 'Freelance çalışmak istiyorum.', NULL, 'https://linkedin.com/in/melisa'),

(12, 'Google’da software engineer.', 'https://can.dev', 'https://linkedin.com/in/can'),
(13, 'Microsoft çalışanı.', NULL, 'https://linkedin.com/in/selin'),
(14, 'Cyber security specialist.', NULL, 'https://linkedin.com/in/mert'),
(15, 'Full stack developer.', NULL, 'https://linkedin.com/in/derya'),
(16, 'DevOps engineer.', NULL, 'https://linkedin.com/in/onur'),
(17, 'Frontend developer.', NULL, 'https://linkedin.com/in/buse'),
(18, 'Backend developer.', NULL, 'https://linkedin.com/in/furkan'),
(19, 'Data analyst.', NULL, 'https://linkedin.com/in/ceren'),
(20, 'Mobile developer.', NULL, 'https://linkedin.com/in/emre'),
(21, 'UI/UX designer.', NULL, 'https://linkedin.com/in/naz');

-- ==========================================
-- USER SKILLS
-- ==========================================

INSERT INTO user_skills (user_id, skill_id) VALUES
(2,1),(2,3),(2,11),
(3,5),(3,7),(3,8),
(4,14),(4,13),(4,16),
(5,19),(5,3),
(6,3),(6,11),
(7,3),(7,5),
(8,13),(8,16),
(9,17),(9,18),
(10,4),(10,5),
(11,5),(11,8),

(12,3),(12,10),(12,11),
(13,9),(13,5),(13,16),
(14,14),(14,13),
(15,3),(15,5),(15,6),
(16,15),(16,16),
(17,9),(17,8),
(18,2),(18,11),
(19,6),(19,20),
(20,4),(20,5),
(21,17),(21,18);

-- ==========================================
-- EXPERIENCES
-- ==========================================

INSERT INTO experiences
(user_id, company_id, position_id, start_date, end_date)
VALUES
(12,1,8,'2021-07-01',NULL),
(13,2,1,'2020-06-01',NULL),
(14,4,4,'2019-01-15',NULL),
(15,6,3,'2022-03-10',NULL),
(16,5,5,'2021-09-01',NULL),
(17,3,1,'2020-05-15',NULL),
(18,7,2,'2018-02-01',NULL),
(19,10,6,'2023-01-01',NULL),
(20,8,10,'2022-06-01',NULL),
(21,2,7,'2019-11-11',NULL);

-- ==========================================
-- COMPANY REVIEWS
-- ==========================================

INSERT INTO company_reviews
(company_id, user_id, content, rating, is_anonymous)
VALUES
(1,12,'Çalışma ortamı çok iyi.',5,FALSE),
(2,13,'Takım kültürü güzel.',5,FALSE),
(3,17,'Yoğun ama öğretici.',4,TRUE),
(4,14,'Siber güvenlik projeleri kaliteli.',5,FALSE),
(5,16,'Çok fazla iş yükü olabiliyor.',3,TRUE),
(6,15,'Stajyerlere değer veriliyor.',5,FALSE),
(7,18,'Kurumsal yapı güçlü.',4,FALSE),
(8,20,'Startup ortamı eğlenceli.',4,TRUE),
(9,10,'Staj sürecinde çok şey öğrendim.',5,TRUE),
(10,19,'AI projeleri oldukça ilginç.',5,FALSE);

-- ==========================================
-- AUTH LOGS
-- ==========================================

INSERT INTO auth_logs
(user_id, ip_address, user_agent, is_success)
VALUES
(2,'192.168.1.2','Chrome Windows',TRUE),
(3,'192.168.1.3','Firefox Linux',TRUE),
(4,'192.168.1.4','Edge Windows',FALSE),
(5,'192.168.1.5','Chrome Android',TRUE),
(6,'192.168.1.6','Safari IOS',TRUE),
(7,'192.168.1.7','Opera Windows',FALSE),
(12,'192.168.1.12','Chrome MacOS',TRUE),
(14,'192.168.1.14','Firefox Windows',TRUE),
(18,'192.168.1.18','Edge Linux',TRUE),
(1,'192.168.1.1','Chrome Windows',TRUE);

-- ==========================================
-- MENTOR ADS
-- ==========================================

INSERT INTO mentor_ads
(graduate_id, expertise, title)
VALUES
(12,'Backend Development','Backend mentorlugu veriyorum'),
(13,'Frontend Development','React öğrenmek isteyenler için mentorluk'),
(14,'Cyber Security','Siber güvenlik kariyeri desteği'),
(15,'Full Stack','Full stack roadmap desteği'),
(16,'DevOps','Docker ve CI/CD mentorlugu'),
(17,'Frontend','UI/UX ve frontend gelişimi'),
(18,'Backend','API geliştirme mentorlugu'),
(19,'Data Analysis','Data analyst olmak isteyenlere destek'),
(20,'Mobile Development','Mobil uygulama geliştirme mentorlugu'),
(21,'UI/UX','Tasarım odaklı mentorluk');

-- ==========================================
-- MENTOR APPLICATIONS
-- ==========================================

INSERT INTO mentor_applications
(ads_id, student_id, message, status)
VALUES
(1,2,'Backend konusunda gelişmek istiyorum.','Approved'),
(2,3,'React öğrenmek istiyorum.','Waiting'),
(3,4,'Pentest alanına ilgim var.','Approved'),
(4,5,'Full stack roadmap istiyorum.','Rejected'),
(5,6,'Docker öğrenmek istiyorum.','Waiting'),
(6,7,'Frontend geliştirmek istiyorum.','Approved'),
(7,8,'API geliştirme öğrenmek istiyorum.','Waiting'),
(8,9,'Data analyst olmak istiyorum.','Approved'),
(9,10,'Flutter öğrenmeye başladım.','Waiting'),
(10,11,'UI/UX alanında gelişmek istiyorum.','Approved');

-- ==========================================
-- FORUM POSTS
-- ==========================================

INSERT INTO forum_posts
(user_id, category_id, title, content, is_anonymous)
VALUES
(2,1,'C mi Python mu?','Yeni başlayan biri önce hangi dili öğrenmeli?',FALSE),
(3,3,'CV hazırlarken nelere dikkat edilmeli?','Özellikle staj başvuruları için öneri lazım.',FALSE),
(4,1,'Linux öğrenmek zor mu?','Siber güvenlik için şart mı?',FALSE),
(5,5,'Staj bulmak çok zor','Her yere başvurdum ama dönüş yok.',TRUE),
(6,6,'Erasmus deneyimi olan var mı?','Yurtdışı süreci hakkında bilgi verir misiniz?',FALSE),
(7,7,'Remote çalışmak isteyenler','Uzaktan çalışma için hangi yetenekler gerekli?',FALSE),
(8,1,'Git öğrenmek gerekli mi?','Versiyon kontrol sistemlerini öğrenmeli miyim?',FALSE),
(9,2,'Mülakatlarda algoritma soruyorlar mı?','Junior pozisyonlarda durum nasıl?',FALSE),
(10,4,'Yapay zeka gelecekte işleri bitirir mi?','Yazılım sektörünü etkiler mi?',FALSE),
(11,8,'Motivasyon kaybı yaşıyorum','Bazen çalışmak istemiyorum.',TRUE);

-- ==========================================
-- FORUM COMMENTS
-- ==========================================

INSERT INTO forum_comments
(post_id, user_id, parent_comment_id, content, is_anonymous)
VALUES

(1,3,NULL,'Bence Python daha kolay başlangıç sağlar.',FALSE),
(1,4,NULL,'C öğrenmek algoritma mantığını geliştiriyor.',FALSE),
(1,12,1,'Katılıyorum, Python başlangıç için daha rahat.',FALSE),

(2,13,NULL,'CV tasarımından çok içerik önemli.',FALSE),
(2,15,NULL,'Projelerini mutlaka eklemelisin.',FALSE),

(3,14,NULL,'Linux gerçekten avantaj sağlıyor.',FALSE),
(3,8,NULL,'Ben Arch Linux kullanıyorum.',FALSE),

(4,6,NULL,'Ben de aynı durumdayım.',TRUE),
(4,12,NULL,'LinkedIn üzerinden başvurmayı deneyebilirsin.',FALSE),

(5,3,NULL,'Erasmus gerçekten güzel bir deneyim.',FALSE),
(5,17,NULL,'İngilizce seviyesi önemli oluyor.',FALSE),

(6,18,NULL,'Freelance için iletişim becerisi önemli.',FALSE),

(7,16,NULL,'Git kesinlikle öğrenilmeli.',FALSE),

(8,20,NULL,'Leetcode çözmek faydalı oluyor.',FALSE),

(9,19,NULL,'AI bazı alanları değiştirecek ama yazılım bitmez.',FALSE),

(10,2,NULL,'Küçük projeler yapmak motive edebilir.',FALSE),
(10,21,16,'Ara sıra mola vermek de önemli.',FALSE),

(1,5,NULL,'Ben önce C öğrendim sonra Python geçtim.',FALSE),
(2,7,NULL,'Github linki eklemek avantaj sağlar.',FALSE),
(3,9,NULL,'Kali Linux denemeyi düşünüyorum.',FALSE),
(6,11,NULL,'İngilizce konuşma pratiği de önemli.',FALSE),
(8,4,NULL,'Data structures sorabiliyorlar.',FALSE);


