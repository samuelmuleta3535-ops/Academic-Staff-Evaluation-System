CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value VARCHAR(255) NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
INSERT INTO system_settings(setting_key, setting_value) VALUES ('evaluation_deadline', NULL) ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
