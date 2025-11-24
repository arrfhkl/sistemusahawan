CREATE DATABASE IF NOT EXISTS parcel_tracking;
USE parcel_tracking;


CREATE TABLE admins (
id INT AUTO_INCREMENT PRIMARY KEY,
email VARCHAR(150) UNIQUE NOT NULL,
password VARCHAR(255) NOT NULL,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE parcels (
id INT AUTO_INCREMENT PRIMARY KEY,
tracking_no VARCHAR(50) UNIQUE,
sender_name VARCHAR(100),
receiver_name VARCHAR(100),
receiver_phone VARCHAR(50),
origin VARCHAR(100),
destination VARCHAR(100),
status VARCHAR(50),
created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE parcel_logs (
id INT AUTO_INCREMENT PRIMARY KEY,
tracking_no VARCHAR(50),
status VARCHAR(50),
location VARCHAR(100),
remarks TEXT,
log_time DATETIME DEFAULT CURRENT_TIMESTAMP
);


-- sample admin
INSERT INTO admins (email, password) VALUES
('admin@example.com', '$2y$10$u1f8lYq7Gkz9x6K/1qE0xe0pTnQ/0bQHk6ZVqDqz1GfQG1pZ8K9m');
-- Password: Password123!


-- sample parcels + logs
INSERT INTO parcels (tracking_no, sender_name, receiver_name, receiver_phone, origin, destination, status)
VALUES
('MY1000000001','Ali','Siti','012-3456789','Kuala Lumpur','Penang','In Transit'),
('MY1000000002','John','Ahmad','013-9876543','Selangor','Johor','Delivered');


INSERT INTO parcel_logs (tracking_no, status, location, remarks)
VALUES
('MY1000000001','Order Received','KL Hub','Parcel received at hub'),
('MY1000000001','Shipped','KL Hub','Left hub towards Penang'),
('MY1000000001','In Transit','North Highway','On the way'),
('MY1000000002','Delivered','Johor Bahru','Delivered to recipient');