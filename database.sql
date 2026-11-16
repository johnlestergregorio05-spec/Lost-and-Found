-- Web-Based Campus Lost and Found Asset Tracking System
-- Database schema (3NF) + seed data
-- Import via phpMyAdmin or:  mysql -u root < database.sql

DROP DATABASE IF EXISTS campus_lostfound;
CREATE DATABASE campus_lostfound CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_lostfound;

-- ---------------------------------------------------------------
-- Lookup tables
-- ---------------------------------------------------------------
CREATE TABLE roles (
    role_id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name  VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE categories (
    category_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE locations (
    location_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Users
-- ---------------------------------------------------------------
CREATE TABLE users (
    user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id        TINYINT UNSIGNED NOT NULL,
    school_id_no   VARCHAR(30) NOT NULL UNIQUE,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(120) NOT NULL UNIQUE,
    contact_no     VARCHAR(20) NULL,
    password_hash  VARCHAR(255) NOT NULL,
    account_status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Items (lost / found reports)
-- ---------------------------------------------------------------
CREATE TABLE items (
    item_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id     INT UNSIGNED NOT NULL,
    category_id     INT UNSIGNED NOT NULL,
    location_id     INT UNSIGNED NOT NULL,
    report_type     ENUM('Lost','Found') NOT NULL,
    item_name       VARCHAR(120) NOT NULL,
    description     TEXT NOT NULL,
    date_happened   DATE NOT NULL,
    photo_path      VARCHAR(255) NULL,
    claim_status    ENUM('Unclaimed','Claimed') NOT NULL DEFAULT 'Unclaimed',
    approval_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_reporter FOREIGN KEY (reporter_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_items_location FOREIGN KEY (location_id) REFERENCES locations(location_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_items_filter (report_type, claim_status, approval_status),
    INDEX idx_items_date (date_happened)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Claims (a user asks to claim an item; staff/admin verifies)
-- ---------------------------------------------------------------
CREATE TABLE claims (
    claim_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id       INT UNSIGNED NOT NULL,
    claimant_id   INT UNSIGNED NOT NULL,
    proof_details TEXT NOT NULL,
    claim_state   ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    reviewed_by   INT UNSIGNED NULL,
    reviewed_at   DATETIME NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_claims_item     FOREIGN KEY (item_id)     REFERENCES items(item_id)  ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_claims_claimant FOREIGN KEY (claimant_id) REFERENCES users(user_id)  ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_claims_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(user_id)  ON UPDATE CASCADE ON DELETE SET NULL,
    UNIQUE KEY uq_claim_once (item_id, claimant_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Audit logs
-- ---------------------------------------------------------------
CREATE TABLE audit_logs (
    log_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(50) NOT NULL,
    entity      VARCHAR(40) NOT NULL,
    entity_id   INT UNSIGNED NULL,
    details     VARCHAR(255) NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Seed data
-- Demo password for all accounts below: Password123
-- ---------------------------------------------------------------
INSERT INTO roles (role_name) VALUES ('Admin'), ('Staff'), ('User');

INSERT INTO categories (category_name) VALUES
('ID / Cards'), ('Electronics'), ('Bags'), ('Wallets'), ('Keys'),
('Clothing'), ('Books / School Supplies'), ('Umbrella / Tumbler'), ('Others');

INSERT INTO locations (location_name) VALUES
('Main Library'), ('Canteen'), ('Gymnasium'), ('Admin Building'),
('Computer Laboratory'), ('Science Building'), ('Parking Area'), ('Chapel'), ('Campus Grounds');

INSERT INTO users (role_id, school_id_no, full_name, email, contact_no, password_hash) VALUES
(1, 'ADM-0001', 'System Administrator', 'admin@campus.edu',   '09170000001', '$2y$10$eQF6pDLmbakOVsIv2w4tveAlHJCREDVoevXsTmxSvx7wfNZ0HzvJ.'),
(2, 'STF-0001', 'Maria Santos (Security Office)', 'staff@campus.edu', '09170000002', '$2y$10$eQF6pDLmbakOVsIv2w4tveAlHJCREDVoevXsTmxSvx7wfNZ0HzvJ.'),
(3, '2023-00123', 'Juan Dela Cruz', 'juan@campus.edu',    '09170000003', '$2y$10$eQF6pDLmbakOVsIv2w4tveAlHJCREDVoevXsTmxSvx7wfNZ0HzvJ.'),
(3, '2023-00456', 'Ana Reyes',       'ana@campus.edu',     '09170000004', '$2y$10$eQF6pDLmbakOVsIv2w4tveAlHJCREDVoevXsTmxSvx7wfNZ0HzvJ.');

INSERT INTO items (reporter_id, category_id, location_id, report_type, item_name, description, date_happened, claim_status, approval_status) VALUES
(3, 4, 2, 'Lost',  'Brown leather wallet', 'Contains student ID and some cash. Lost near the canteen counter.', '2026-11-03', 'Unclaimed', 'Approved'),
(4, 2, 1, 'Found', 'Black wireless earbuds', 'Found on a table on the 2nd floor of the library. Charging case included.', '2026-11-05', 'Unclaimed', 'Approved'),
(4, 5, 5, 'Found', 'Set of keys with blue keychain', 'Three keys on a blue lanyard, found in Computer Lab 2.', '2026-11-06', 'Claimed',   'Approved'),
(3, 3, 3, 'Lost',  'Gray backpack', 'Gray Jansport backpack with a physics notebook inside.', '2026-11-08', 'Unclaimed', 'Pending');

INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES
(1, 'SEED', 'system', NULL, 'Initial database seeded', '127.0.0.1');
