-- ============================================
-- RELOOP ELECTRONIC HUB - COMPLETE DATABASE
-- Includes: Tables, Users, and ALL Products
-- Just import this ONE file - Everything works!
-- ============================================

CREATE DATABASE IF NOT EXISTS prototype_reloop;
USE prototype_reloop;

-- ============================================
-- FIRST: Drop tables in correct order
-- ============================================
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS users;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type VARCHAR(50) DEFAULT 'customer',
    profile_image VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    reset_token VARCHAR(255) NULL,
    reset_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. PRODUCTS TABLE
-- ============================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    brand VARCHAR(100),
    category VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0,
    original_price DECIMAL(10,2) DEFAULT 0,
    discount INT DEFAULT 0,
    description TEXT,
    warranty VARCHAR(50) DEFAULT '1 Year',
    stock_status VARCHAR(50) DEFAULT 'Available',
    image_url VARCHAR(500) DEFAULT NULL,
    image_url_2 VARCHAR(500) DEFAULT NULL,
    image_url_3 VARCHAR(500) DEFAULT NULL,
    image_url_4 VARCHAR(500) DEFAULT NULL,
    image_url_5 VARCHAR(500) DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT NULL,
    colors TEXT DEFAULT NULL,
    color_hex TEXT DEFAULT NULL,
    specs TEXT DEFAULT NULL,
    seller_rating DECIMAL(3,2) DEFAULT 4.5,
    seller_orders INT DEFAULT 0,
    ratings DECIMAL(3,2) DEFAULT 4.5,
    reviews_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 3. CART TABLE
-- ============================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    color VARCHAR(100) DEFAULT 'Standard',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- 4. ORDERS TABLE
-- ============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date DATETIME NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    order_status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 5. ORDER ITEMS TABLE
-- ============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    color VARCHAR(100) DEFAULT 'Standard',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ============================================
-- 6. WISHLIST TABLE
-- ============================================
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- ============================================
-- 7. REVIEWS TABLE
-- ============================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================
-- 8. CONTACT MESSAGES TABLE
-- ============================================
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 9. INSERT DEFAULT USERS
-- ============================================
INSERT INTO users (id, full_name, email, password, user_type, phone, address) VALUES 
(1, 'Admin', 'admin@reloop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL),
(2, 'Demo Seller', 'seller@reloop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', '0300-1234567', '123 Electronics Street, Karachi'),
(3, 'Demo Customer', 'customer@reloop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '0300-7654321', '456 Shopping Mall, Lahore')
ON DUPLICATE KEY UPDATE id=id;

-- ============================================
-- 10. INSERT ALL PRODUCTS (IDs 100-115)
-- ============================================

INSERT INTO products (id, user_id, name, brand, category, price, original_price, discount, description, warranty, stock_status, image_url, image_url_2, image_url_3, created_at) VALUES 
(100, 1, 'iPhone 15 Pro Max', 'Apple', 'Smartphones', 350000, 389999, 10, 'The ultimate iPhone with A17 Pro chip, titanium design, and advanced camera system.', '1 Year', 'Available', 'https://clevercel.mx/cdn/shop/files/4_0bb4ba2c-c334-4fce-8807-05b800c26bb2.jpg?v=1763065322&width=1214', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ71Mh-BnN0H0k-g_J6UmO-22zkwv1E5xpOs343laNyQFf6mrB_', 'https://i.blogs.es/718a10/img_2085/500_333.jpeg', NOW()),
(101, 1, 'Samsung Galaxy S24 Ultra', 'Samsung', 'Smartphones', 320000, 359999, 11, 'AI-powered smartphone with 200MP camera and built-in S Pen.', '1 Year', 'Available', 'https://img.drz.lazcdn.com/static/np/p/b8aa2f26580d2a81fe83e3792c21a964.png_720x720q80.png', 'https://i.ytimg.com/vi/5PFp7c8lc6o/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLDx67Eys4b-3Bqy3hZhTpSlpN-AdQ', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR-Lo6hMhwhOcZTbcwrT2iu6VxZ4LROORYsPQ&st', NOW()),
(102, 1, 'Google Pixel 8 Pro', 'Google', 'Smartphones', 250000, 279999, 11, 'Pure Android experience with amazing camera and AI features.', '1 Year', 'Limited', 'https://discountstore.pk/cdn/shop/files/71h9zq4viSL._AC_SL1500.webp?v=1754118093', 'https://virtual2web.com/12162-superlarge_default/google-pixel-8-pro-5g-12gb-128gb-blanco-porcelain-dual-sim-ga04798.jpg', 'https://propakistani.pk/wp-content/uploads/2023/10/Google-Pixel-8-e1696484862815.jpg', NOW()),
(103, 1, 'MacBook Pro 16"', 'Apple', 'Laptops', 450000, 499999, 10, 'M3 Max chip with 48GB RAM, 1TB SSD for ultimate performance.', '2 Years', 'Available', 'https://laptopmedia.com/wp-content/uploads/2024/12/5-26.jpg', 'https://laptopchoice.pk/wp-content/uploads/2024/05/4-5.jpg', 'https://propakistani.pk/wp-content/uploads/2023/10/M3-MacBook-e1698731159314.jpg', NOW()),
(104, 1, 'Dell XPS 15', 'Dell', 'Laptops', 320000, 349999, 9, 'Premium Windows laptop with OLED display and powerful performance.', '2 Years', 'Available', 'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/20030547/mchin_180905_4061_0009.jpg?quality=90&strip=all&crop=16.666666666667,0,66.666666666667,100', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT30f1MhQGBEuW1Q0WtqC4uwsoXEa21HBv_ww&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNSj__o3O_Mr39YI_jn1mZhhmRIwNVRo-xRA&s', NOW()),
(105, 1, 'ASUS ROG Strix', 'ASUS', 'Laptops', 280000, 309999, 10, 'High-performance gaming laptop with RGB lighting and powerful cooling system.', '1 Year', 'Limited', 'https://dlcdnwebimgs.asus.com/files/media/982b43f2-03f0-4780-b552-cf2a58d515bf/v1/images/m-kv_1.webp', 'https://dlcdnrog.asus.com/rog/media/1774328720881.webp', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ83mG4QVm70rvOOibO3uKSmA27BYychMRo3g&s', NOW()),
(106, 1, 'Apple Watch Series 9', 'Apple', 'Smart Watches', 85000, 94999, 11, 'Latest smartwatch with double tap gesture and advanced health features.', '1 Year', 'Available', 'https://www.apple.com/newsroom/images/2023/09/apple-introduces-the-advanced-new-apple-watch-series-9/article/Apple-Watch-S9-hero-230912_Full-Bleed-Image.jpg.large.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRv-aF_ddTM6jYAYO94k-hdmVNayUTfrJ3WzQ&s', 'https://www.apple.com/newsroom/videos/apple-watch-s9-sip/posters/Apple-Watch-S9-SiP-230912.jpg.large_2x.jpg', NOW()),
(107, 1, 'Samsung Galaxy Watch 6', 'Samsung', 'Smart Watches', 65000, 74999, 13, 'Sleek design with advanced health tracking and long battery life.', '1 Year', 'Available', 'https://img.global.news.samsung.com/ph/wp-content/uploads/2023/08/003-galaxy-watch6-watch6-classic-body-composition-e1693475900315.jpg', 'https://cdn.mos.cms.futurecdn.net/5UtezHJwnDvsXAVSkFJxb4.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWcR_W5OspuRp5B-IYKzO3evD0G5wqWiY0AA&s', NOW()),
(108, 1, 'Garmin Fenix 7', 'Garmin', 'Smart Watches', 120000, 139999, 14, 'Premium multisport GPS smartwatch with solar charging and advanced fitness metrics.', '2 Years', 'Available', 'https://www.garmin.pk/images/product_gallery/1642602184_010-02540-31.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS4zANXGPvIJdlezaEp4l5ie-2V7Zq7aJQRfA&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWQqLReKk_1VCHezCmUzP2TGZkDb4Y_OaKSA&s', NOW()),
(109, 1, 'AirPods Pro 2', 'Apple', 'Accessories', 55000, 59999, 8, 'Active noise cancellation, adaptive audio and MagSafe charging case.', '1 Year', 'Available', 'https://hmnstudio.com/cdn/shop/files/Pro2ANCIMG-3.jpg?v=1711316190&width=1445', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQYxRpE16v780RLp-Kmsr0FiO35qxHToUwo1Q&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR7xzxgGEjoiS0b6oz7fZbn8VUYcJUB-Ukd0Q&s', NOW()),
(110, 1, 'Samsung Buds2 Pro', 'Samsung', 'Accessories', 35000, 39999, 13, 'Premium wireless earbuds with 24-bit Hi-Fi sound and intelligent active noise cancellation.', '1 Year', 'Available', 'https://eezepc.com/wp-content/uploads/2022/09/Buds2-Pro-Purple-EEZEPC-6.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSW_zyPchXWvHrTeIQYwwfg3ihvgJZLjXQDjQ&s', 'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/23932914/DSC03286_buds_2_pro.jpg?quality=90&strip=all&crop=0%2C0%2C100%2C100&w=2400', NOW()),
(111, 1, 'Logitech MX Master 3S', 'Logitech', 'Accessories', 25000, 29999, 17, 'Ultra-quiet wireless mouse with 8K DPI sensor and MagSpeed scroll wheel.', '2 Years', 'Limited', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQjZ2OljgmozExZblB01jPL6PclNTo8BXokJw&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFdIUMtNsp1ZPgfw2jhBF9V2fjJJgfV89p3g&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ4ljrDNeLt4u8miKvQictqgTofOhFRBFsn5A&s', NOW()),
(112, 1, 'Sony WH-1000XM5', 'Sony', 'Audio Devices', 85000, 89999, 6, 'Industry-leading noise cancellation with exceptional sound quality.', '1 Year', 'Available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSD26xGUpXdHxOJvn9MOX9HA4R1-R7ylq3sCg&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQD3McjbBLdJr6N8tGBaYh8kpi1eCxEfcTvZw&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQuOuh56M-AIkaY-Ke27xW77T_MZFNMMfHroQ&s', NOW()),
(113, 1, 'Bose QuietComfort Ultra', 'Bose', 'Audio Devices', 95000, 99999, 5, 'Immersive audio experience with spatial sound and legendary noise cancellation.', '1 Year', 'Available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTO5sGlsyaMANudgQ7Lvz51OY4_Pkk2njWuRg&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTzsZCSolgHF0PeHDtX3xwNjTSx4u3tARsfFg&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFEClCSSWg-91nXKq0eAkZCS3EtzVBTajtEw&s', NOW()),
(114, 1, 'JBL Charge 5', 'JBL', 'Audio Devices', 35000, 39999, 13, 'Powerful portable Bluetooth speaker with long battery life and rugged design.', '1 Year', 'Available', 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500&auto=format', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQG44dILl5acFLdURvA81T3mrXH5Prx0Wc7Tg&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRXdWrnp6O_2_rB4Sa2MNMTLi6RxV754vKPvQ&s', NOW()),
(115, 1, 'OnePlus 12', 'OnePlus', 'Smartphones', 180000, 199999, 10, 'Powerful flagship with Snapdragon 8 Gen 3, 50MP camera, and 5400mAh battery with 100W charging.', '1 Year', 'Available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzpM-Nfec2aYgYcjqt9nFofbdt11q-CbJBiA&s', 'https://propakistani.pk/wp-content/uploads/2023/07/oneplus-12-scaled-e1689764889477.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzJ7653G1MaHG7zI1IM9pUTJxcYGLzbxhXXw&s', NOW());

-- ============================================
-- 11. RESET AUTO_INCREMENT
-- ============================================
ALTER TABLE products AUTO_INCREMENT = 200;

-- ============================================
-- 12. INSERT SAMPLE REVIEW
-- ============================================
INSERT INTO reviews (user_id, product_id, rating, comment) VALUES 
(3, 100, 5, 'Amazing phone! The camera quality is outstanding and battery life is great.')
ON DUPLICATE KEY UPDATE id=id;

-- ============================================
-- DONE!
-- ============================================
SELECT 'Database Setup Complete!' as Status;
SELECT COUNT(*) as TotalUsers FROM users;
SELECT COUNT(*) as TotalProducts FROM products;