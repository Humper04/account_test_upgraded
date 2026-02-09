ALTER TABLE user_info ADD mfa_secret VARCHAR(255) DEFAULT NULL;
ALTER TABLE user_info ADD role ENUM('admin','user') NOT NULL DEFAULT 'user';
