CREATE DATABASE IF NOT EXISTS bf_logger CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bf_logger;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  username VARCHAR(255) DEFAULT NULL,
  user_agent VARCHAR(512) DEFAULT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_created_at (ip, created_at),
  INDEX idx_username_created_at (username, created_at)
);
