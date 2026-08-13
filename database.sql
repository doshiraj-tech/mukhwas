-- =========================================
-- MukhwasMart Database
-- =========================================

CREATE DATABASE IF NOT EXISTS mukhwas_store;
USE mukhwas_store;

-- =========================================
-- USERS TABLE
-- =========================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    reward_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- ADMIN TABLE
-- =========================================

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL
);

INSERT INTO admin(username,password)
VALUES('admin','$2y$10$PqITtEe6tREMHYh2fDlmi.Uc7Fg/oTQ9vpqotsy.jCRA9S3uIxtuC');

-- =========================================
-- CATEGORIES TABLE
-- =========================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories(category_name) VALUES
('Roasted Mukhwas'),
('Ayurvedic Mukhwas'),
('Sweet Mukhwas');

-- =========================================
-- PRODUCTS TABLE
-- =========================================

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id)
    REFERENCES categories(id)
    ON DELETE CASCADE
);

-- =========================================
-- SAMPLE PRODUCTS
-- =========================================

INSERT INTO products
(category_id,name,price,description,image,stock)
VALUES

(
1,
'Roasted Saunf',
120.00,
'Premium roasted fennel seeds mouth freshener.',
'roasted-saunf.jpg',
100
),

(
2,
'Ayurvedic Digestive Mukhwas',
180.00,
'Natural digestive mukhwas made with herbs.',
'ayurvedic-mukhwas.jpg',
80
),

(
3,
'Sweet Mukhwas Special',
150.00,
'Traditional sweet mouth freshener.',
'sweet-mukhwas.jpg',
120
);

-- =========================================
-- ORDERS TABLE
-- =========================================

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,

    fullname VARCHAR(100),
    mobile VARCHAR(15),
    address TEXT,
    city VARCHAR(100),
    pincode VARCHAR(10),

    payment_method VARCHAR(50) DEFAULT 'COD',
    payment_status VARCHAR(50) DEFAULT 'Pending',

    status VARCHAR(50) DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
);

-- =========================================
-- ORDER ITEMS TABLE
-- =========================================

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (order_id)
    REFERENCES orders(id)
    ON DELETE CASCADE,

    FOREIGN KEY (product_id)
    REFERENCES products(id)
    ON DELETE CASCADE
);

-- =========================================
-- CONTACT MESSAGES TABLE
-- =========================================

CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- SAMPLE USER
-- Password = 123456
-- =========================================

INSERT INTO users
(name,email,mobile,password)
VALUES
(
'Raj Doshi',
'raj@gmail.com',
'9876543210',
'$2y$10$fk5lML.mooOv50B2N8CKHeKWHkoIa8d5FH6QLeTKs6iKcsJNgkM1q'
);

-- =========================================
-- END DATABASE
-- =========================================