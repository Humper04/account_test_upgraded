CREATE TABLE password_recovery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires DATETIME NOT NULL,
    FOREIGN KEY (email) REFERENCES user_info(email)
);
