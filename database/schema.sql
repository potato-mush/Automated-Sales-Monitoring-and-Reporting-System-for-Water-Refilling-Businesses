-- Database Schema for Water Refilling Business System
-- Created: February 9, 2026

DROP DATABASE IF EXISTS water_refilling_system;
CREATE DATABASE water_refilling_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE water_refilling_system;

-- ========================================
-- Users Table
-- ========================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- Gallons Table
-- ========================================
CREATE TABLE gallons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gallon_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'QR Code unique identifier',
    status ENUM('IN', 'OUT', 'MISSING') NOT NULL DEFAULT 'IN',
    last_transaction_id BIGINT UNSIGNED NULL,
    last_borrowed_date DATETIME NULL,
    last_returned_date DATETIME NULL,
    is_overdue BOOLEAN DEFAULT FALSE,
    overdue_days INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gallon_code (gallon_code),
    INDEX idx_status (status),
    INDEX idx_overdue (is_overdue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- Transactions Table
-- ========================================
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NULL,
    customer_address TEXT NULL,
    transaction_type ENUM('walk-in', 'delivery', 'refill-only') NOT NULL,
    payment_method ENUM('cash', 'gcash', 'card', 'bank-transfer') NOT NULL DEFAULT 'cash',
    quantity INT NOT NULL DEFAULT 0 COMMENT 'Number of gallons',
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    employee_id BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_transaction_code (transaction_code),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_employee_id (employee_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- Transaction Items Table (Links transactions to gallons)
-- ========================================
CREATE TABLE transaction_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    gallon_id BIGINT UNSIGNED NOT NULL,
    action ENUM('BORROW', 'RETURN') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_gallon_id (gallon_id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (gallon_id) REFERENCES gallons(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- Gallon Logs Table (Audit trail)
-- ========================================
CREATE TABLE gallon_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gallon_id BIGINT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    action ENUM('BORROW', 'RETURN', 'CREATED', 'STATUS_CHANGE') NOT NULL,
    old_status ENUM('IN', 'OUT', 'MISSING') NULL,
    new_status ENUM('IN', 'OUT', 'MISSING') NULL,
    performed_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gallon_id (gallon_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (gallon_id) REFERENCES gallons(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- Inventory Table
-- ========================================
CREATE TABLE inventory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    item_type ENUM('gallon', 'water', 'supplies', 'other') NOT NULL DEFAULT 'supplies',
    quantity DECIMAL(10, 2) NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    low_stock_threshold DECIMAL(10, 2) DEFAULT 10,
    is_low_stock BOOLEAN DEFAULT FALSE,
    last_restocked_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_type (item_type),
    INDEX idx_is_low_stock (is_low_stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- Inventory Logs Table
-- ========================================
CREATE TABLE inventory_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_id BIGINT UNSIGNED NOT NULL,
    action ENUM('ADD', 'DEDUCT', 'ADJUST') NOT NULL,
    quantity_change DECIMAL(10, 2) NOT NULL,
    old_quantity DECIMAL(10, 2) NOT NULL,
    new_quantity DECIMAL(10, 2) NOT NULL,
    performed_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory_id (inventory_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- System Settings Table
-- ========================================
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- Insert Default Settings
-- ========================================
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('gallon_price', '25.00', 'Default price per gallon'),
('delivery_fee', '50.00', 'Delivery fee for delivery transactions'),
('overdue_days_threshold', '7', 'Days before gallon is considered overdue'),
('missing_days_threshold', '30', 'Days before gallon is marked as missing'),
('business_name', 'Water Refilling Station', 'Business name'),
('business_address', '', 'Business address'),
('business_phone', '', 'Business contact number');

-- ========================================
-- System Logs Table
-- ========================================
CREATE TABLE system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    user_role ENUM('admin', 'employee') NOT NULL,
    action ENUM('login', 'logout') NOT NULL,
    platform VARCHAR(50) NULL COMMENT 'web or mobile',
    device VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- Insert Sample Admin User
-- Password: admin123 (hashed)
-- ========================================
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@waterrefilling.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Employee One', 'employee@waterrefilling.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee');

-- ========================================
-- Insert Sample Gallons (50 gallons)
-- ========================================
INSERT INTO gallons (gallon_code, status) VALUES
('WR-GAL-0001', 'IN'), ('WR-GAL-0002', 'IN'), ('WR-GAL-0003', 'IN'), ('WR-GAL-0004', 'IN'), ('WR-GAL-0005', 'IN'),
('WR-GAL-0006', 'IN'), ('WR-GAL-0007', 'IN'), ('WR-GAL-0008', 'IN'), ('WR-GAL-0009', 'IN'), ('WR-GAL-0010', 'IN'),
('WR-GAL-0011', 'IN'), ('WR-GAL-0012', 'IN'), ('WR-GAL-0013', 'IN'), ('WR-GAL-0014', 'IN'), ('WR-GAL-0015', 'IN'),
('WR-GAL-0016', 'IN'), ('WR-GAL-0017', 'IN'), ('WR-GAL-0018', 'IN'), ('WR-GAL-0019', 'IN'), ('WR-GAL-0020', 'IN'),
('WR-GAL-0021', 'IN'), ('WR-GAL-0022', 'IN'), ('WR-GAL-0023', 'IN'), ('WR-GAL-0024', 'IN'), ('WR-GAL-0025', 'IN'),
('WR-GAL-0026', 'IN'), ('WR-GAL-0027', 'IN'), ('WR-GAL-0028', 'IN'), ('WR-GAL-0029', 'IN'), ('WR-GAL-0030', 'IN'),
('WR-GAL-0031', 'IN'), ('WR-GAL-0032', 'IN'), ('WR-GAL-0033', 'IN'), ('WR-GAL-0034', 'IN'), ('WR-GAL-0035', 'IN'),
('WR-GAL-0036', 'IN'), ('WR-GAL-0037', 'IN'), ('WR-GAL-0038', 'IN'), ('WR-GAL-0039', 'IN'), ('WR-GAL-0040', 'IN'),
('WR-GAL-0041', 'IN'), ('WR-GAL-0042', 'IN'), ('WR-GAL-0043', 'IN'), ('WR-GAL-0044', 'IN'), ('WR-GAL-0045', 'IN'),
('WR-GAL-0046', 'IN'), ('WR-GAL-0047', 'IN'), ('WR-GAL-0048', 'IN'), ('WR-GAL-0049', 'IN'), ('WR-GAL-0050', 'IN');

-- ========================================
-- Insert Default Inventory Items
-- ========================================
INSERT INTO inventory (item_name, item_type, quantity, unit, low_stock_threshold) VALUES
('5-Gallon Containers', 'gallon', 50, 'pcs', 10),
('Drinking Water Stock', 'water', 1000, 'liters', 200),
('Bottle Caps', 'supplies', 100, 'pcs', 20),
('Cleaning Supplies', 'supplies', 15, 'pcs', 5);

-- ========================================
-- Views for Reporting
-- ========================================

-- Daily Sales Summary View
CREATE VIEW daily_sales_summary AS
SELECT 
    DATE(created_at) as sale_date,
    COUNT(*) as total_transactions,
    SUM(quantity) as total_gallons_sold,
    SUM(total_amount) as total_revenue,
    SUM(CASE WHEN transaction_type = 'walk-in' THEN total_amount ELSE 0 END) as walkin_revenue,
    SUM(CASE WHEN transaction_type = 'delivery' THEN total_amount ELSE 0 END) as delivery_revenue,
    SUM(CASE WHEN transaction_type = 'refill-only' THEN total_amount ELSE 0 END) as refill_revenue
FROM transactions
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

-- Gallon Status Summary View
CREATE VIEW gallon_status_summary AS
SELECT 
    status,
    COUNT(*) as count
FROM gallons
GROUP BY status;

-- Overdue Gallons View
CREATE VIEW overdue_gallons AS
SELECT 
    g.id,
    g.gallon_code,
    g.status,
    g.last_borrowed_date,
    g.overdue_days,
    t.customer_name,
    t.customer_phone,
    t.transaction_code
FROM gallons g
LEFT JOIN transactions t ON g.last_transaction_id = t.id
WHERE g.is_overdue = TRUE
ORDER BY g.overdue_days DESC;

-- ========================================
-- Stored Procedures
-- ========================================

DELIMITER //

-- Procedure to update overdue gallons
CREATE PROCEDURE update_overdue_gallons()
BEGIN
    DECLARE overdue_threshold INT;
    DECLARE missing_threshold INT;
    
    -- Get thresholds from settings
    SELECT CAST(setting_value AS UNSIGNED) INTO overdue_threshold 
    FROM system_settings WHERE setting_key = 'overdue_days_threshold';
    
    SELECT CAST(setting_value AS UNSIGNED) INTO missing_threshold 
    FROM system_settings WHERE setting_key = 'missing_days_threshold';
    
    -- Update overdue days for OUT gallons
    UPDATE gallons 
    SET overdue_days = DATEDIFF(NOW(), last_borrowed_date),
        is_overdue = CASE 
            WHEN DATEDIFF(NOW(), last_borrowed_date) >= overdue_threshold THEN TRUE 
            ELSE FALSE 
        END
    WHERE status = 'OUT' AND last_borrowed_date IS NOT NULL;
    
    -- Mark gallons as MISSING if they exceed missing threshold
    UPDATE gallons 
    SET status = 'MISSING'
    WHERE status = 'OUT' 
        AND last_borrowed_date IS NOT NULL 
        AND DATEDIFF(NOW(), last_borrowed_date) >= missing_threshold;
END //

DELIMITER ;

-- ========================================
-- Triggers
-- ========================================

DELIMITER //

-- Trigger to log gallon creation
CREATE TRIGGER after_gallon_insert
AFTER INSERT ON gallons
FOR EACH ROW
BEGIN
    INSERT INTO gallon_logs (gallon_id, action, new_status)
    VALUES (NEW.id, 'CREATED', NEW.status);
END //

-- Trigger to log gallon status changes
CREATE TRIGGER after_gallon_update
AFTER UPDATE ON gallons
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO gallon_logs (gallon_id, action, old_status, new_status, transaction_id)
        VALUES (NEW.id, 'STATUS_CHANGE', OLD.status, NEW.status, NEW.last_transaction_id);
    END IF;
END //

-- Trigger to check inventory low stock
CREATE TRIGGER after_inventory_update
AFTER UPDATE ON inventory
FOR EACH ROW
BEGIN
    UPDATE inventory 
    SET is_low_stock = CASE 
        WHEN quantity <= low_stock_threshold THEN TRUE 
        ELSE FALSE 
    END
    WHERE id = NEW.id;
END //

DELIMITER ;
