
-- Freelancing Platform Database
CREATE DATABASE IF NOT EXISTS WorkD;
USE WorkD;

-- USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','client','freelancer') NOT NULL,
    deleted_at DATE NULL DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    join_date DATE DEFAULT CURRENT_TIMESTAMP,
    experience INT(11) DEFAULT NULL,
    skills TEXT DEFAULT NULL
);


-- JOBS TABLE
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    budget INT NOT NULL,
    deadline DATETIME NOT NULL,
    status ENUM('open','assigned','completed','paid', 'deleted') DEFAULT 'open',
    client_deleted TINYINT(1) DEFAULT 0,
    freelancer_deleted TINYINT(1) DEFAULT 0,
    admin_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);


-- APPLICATIONS TABLE
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    proposal TEXT NOT NULL,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
);


-- ORDERS TABLE
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    client_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    amount INT NOT NULL,
    commission_percentage INT NOT NULL,
    commission_amount INT NOT NULL,
    freelancer_amount INT NOT NULL,
    status ENUM('in_progress','completed','paid') DEFAULT 'in_progress',
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
);


-- USER ACTIVITY LOG TABLE
CREATE TABLE user_activity (
    user_id INT PRIMARY KEY,
    jobs_posted INT DEFAULT 0,
    proposals_sent INT DEFAULT 0,
    completed_orders INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- REVIEWS TABLE
-- CREATE TABLE reviews (
--   id INT AUTO_INCREMENT PRIMARY KEY,
--   order_id INT,
--   rating INT,
--   comment TEXT,
--   FOREIGN KEY (order_id) REFERENCES orders(id)
-- );

-- ADMIN SETTINGS TABLE
CREATE TABLE admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commission_percentage INT NOT NULL DEFAULT 10
);


-- DEFAULT COMMISSION SETTING
INSERT INTO admin_settings (commission_percentage) VALUES (10);

-- SAMPLE ADMIN USER
INSERT INTO users (name,email,password,role)
VALUES ('Admin','admin@test.com','$2y$10$abcdefghijklmnopqrstuv','admin');
