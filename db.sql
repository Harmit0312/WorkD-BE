
-- Freelancing Platform Database
CREATE DATABASE IF NOT EXISTS WorkD;
USE WorkD;

-- USERS TABLE
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role ENUM('client','freelancer','admin'),
  is_active BOOLEAN DEFAULT 1
);

-- JOBS TABLE
CREATE TABLE jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT,
  title VARCHAR(200),
  description TEXT,
  budget INT,
  deadline DATETIME,
  status ENUM('open','assigned','completed') DEFAULT 'open',
  FOREIGN KEY (client_id) REFERENCES users(id)
);

-- APPLICATIONS TABLE
CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT,
  freelancer_id INT,
  proposal TEXT,
  status ENUM('pending','accepted','rejected') DEFAULT 'pending',
  FOREIGN KEY (job_id) REFERENCES jobs(id),
  FOREIGN KEY (freelancer_id) REFERENCES users(id)
);

-- ORDERS TABLE
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT,
  client_id INT,
  freelancer_id INT,
  amount INT,
  commission_percentage INT,
  commission_amount INT,
  freelancer_amount INT,
  status ENUM('in_progress','completed')
);

-- REVIEWS TABLE
CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT,
  rating INT,
  comment TEXT,
  FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- ADMIN SETTINGS TABLE
CREATE TABLE admin_settings (
  id INT PRIMARY KEY,
  commission_percentage INT
);

-- DEFAULT COMMISSION SETTING
INSERT INTO admin_settings (id, commission_percentage)
VALUES (1, 10);

-- SAMPLE ADMIN USER
INSERT INTO users (name,email,password,role)
VALUES ('Admin','admin@test.com','$2y$10$abcdefghijklmnopqrstuv','admin');
