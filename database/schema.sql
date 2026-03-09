-- Internal Complaint Chat System schema
-- Import this file in phpMyAdmin or MySQL CLI.

CREATE DATABASE IF NOT EXISTS kiss
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kiss;

DROP TABLE IF EXISTS message_files;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;

CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'department_admin') NOT NULL DEFAULT 'department_admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_users_role_active (role, is_active),
    INDEX idx_users_department (department_id)
) ENGINE=InnoDB;

CREATE TABLE messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message_text TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_messages_receiver
        FOREIGN KEY (receiver_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_messages_conversation (sender_id, receiver_id, created_at),
    INDEX idx_messages_receiver_unread (receiver_id, read_at, created_at)
) ENGINE=InnoDB;

CREATE TABLE message_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(150) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_files_message
        FOREIGN KEY (message_id) REFERENCES messages(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_message_files_message (message_id)
) ENGINE=InnoDB;

-- Seed data:
-- Password for all seeded users: Admin@123
INSERT INTO departments (name) VALUES
('Super Admin Office'),
('Department A'),
('Department B');

INSERT INTO users (department_id, full_name, email, password_hash, role, is_active)
SELECT d.id, seed.full_name, seed.email, '$2y$10$hwBTtJhHjcHzRCvSTV7VWerjDEl.alHafoc5n0/lEAgKF4pQcUbOu', seed.role, 1
FROM (
    SELECT 'Super Admin Office' AS dept_name, 'Super Admin' AS full_name, 'super.admin@org.local' AS email, 'super_admin' AS role
    UNION ALL
    SELECT 'Department A' AS dept_name, 'Admin A' AS full_name, 'admin.a@org.local' AS email, 'department_admin' AS role
    UNION ALL
    SELECT 'Department B' AS dept_name, 'Admin B' AS full_name, 'admin.b@org.local' AS email, 'department_admin' AS role
) AS seed
INNER JOIN departments d ON d.name = seed.dept_name;
