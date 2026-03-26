-- Calendar Module Migration
-- Run this against the 'kiss' database after importing schema.sql

USE kiss;

CREATE TABLE IF NOT EXISTS calendar_events (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    created_by  INT UNSIGNED NOT NULL,
    event_date  DATE NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type        ENUM('broadcast','print','general') NOT NULL DEFAULT 'general',
    metadata    JSON NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_cal_events_dept
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_cal_events_user
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    INDEX idx_cal_events_dept   (department_id),
    INDEX idx_cal_events_date   (event_date),
    INDEX idx_cal_events_type   (type),
    INDEX idx_cal_events_created_by (created_by)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
