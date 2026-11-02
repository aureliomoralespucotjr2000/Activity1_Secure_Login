-- Activity 1: Secure Login System
-- Task 1: the users table.  Run this in phpMyAdmin (Import tab, or the SQL tab of wst2_auth).
CREATE DATABASE IF NOT EXISTS wst2_auth CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wst2_auth;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,           -- unique ID for every user
    username        VARCHAR(30)  NOT NULL UNIQUE,                      -- no two accounts share a username
    email           VARCHAR(255) NOT NULL UNIQUE,                      -- contact and duplicate check
    password_hash   VARCHAR(255) NOT NULL,                             -- holds the hash, never the password
    role            ENUM('admin','instructor','student') NOT NULL DEFAULT 'student',   -- least privilege by default
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,      -- when the account was made

    -- Bonus (+10): account lockout
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB;
