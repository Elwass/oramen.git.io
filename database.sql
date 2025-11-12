-- Database structure for Ramen 1 ordering system
CREATE DATABASE IF NOT EXISTS ramen1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ramen1;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS menu_categories;
DROP TABLE IF EXISTS tables;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE menu_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    status ENUM('Baru','Sedang Dibuat','Selesai','Dibayar','Dibatalkan') DEFAULT 'Baru',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

INSERT INTO users (username, password) VALUES
('admin', '$2y$10$uBXwVnR1ZHphSCe6xPD58OC9frN7kYMva9n32CuWHa3gwxM/H2y1a'); -- password: admin123

INSERT INTO tables (table_number) VALUES
('1'),('2'),('3'),('4'),('5'),('6'),('7'),('8'),('9'),('10');

INSERT INTO menu_categories (name) VALUES
('Ramen'),
('Sushi'),
('Tsukemen'),
('Bento'),
('Minuman');

INSERT INTO menu_items (category_id, name, description, price, image) VALUES
(1, 'Classic Shoyu Ramen', 'Kuah shoyu gurih dengan chashu dan telur ajitsuke.', 48000.00, '/uploads/sample/shoyu.jpg'),
(1, 'Spicy Miso Ramen', 'Miso pedas dengan topping jagung manis dan butter.', 52000.00, '/uploads/sample/miso.jpg'),
(1, 'Tonkotsu Ramen', 'Kuah kaldu babi kremi dengan black garlic oil.', 58000.00, '/uploads/sample/tonkotsu.jpg'),
(2, 'Salmon Aburi Sushi', 'Sushi salmon torch dengan saus spesial.', 45000.00, '/uploads/sample/salmon.jpg'),
(2, 'Unagi Nigiri', 'Belut panggang saus tare klasik.', 62000.00, '/uploads/sample/unagi.jpg'),
(3, 'Tsukemen Yuzu', 'Mi dingin dengan kuah dipping yuzu citrus.', 55000.00, '/uploads/sample/tsukemen.jpg'),
(4, 'Chicken Katsu Bento', 'Bento ayam katsu dengan salad segar.', 50000.00, '/uploads/sample/bento.jpg'),
(4, 'Gyudon Bento', 'Bento beef bowl dengan saus manis gurih.', 52000.00, '/uploads/sample/gyudon.jpg'),
(5, 'Ocha Panas', 'Teh hijau Jepang hangat.', 12000.00, '/uploads/sample/ocha.jpg'),
(5, 'Matcha Latte', 'Matcha latte creamy dengan susu segar.', 28000.00, '/uploads/sample/matcha.jpg');
