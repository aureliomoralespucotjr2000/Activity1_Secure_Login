
CREATE DATABASE IF NOT EXISTS wst2_auth CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wst2_auth;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,           
    username        VARCHAR(30)  NOT NULL UNIQUE,                      
    email           VARCHAR(255) NOT NULL UNIQUE,                      
    password_hash   VARCHAR(255) NOT NULL,                            
    role            ENUM('admin','instructor','student') NOT NULL DEFAULT 'student',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,      

    
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB;
