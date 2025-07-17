-- 1. Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS jampack_db;

-- 2. Use the newly created (or existing) database
USE jampack_db;

-- 3. Create the table if it doesn't exist
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_build VARCHAR(255) NOT NULL,
    game_description VARCHAR(255) NOT NULL,
    preview_video VARCHAR(255) NOT NULL,
    credits VARCHAR(255) NOT NULL,
    controls VARCHAR(255) NOT NULL,
    game_logo VARCHAR(255) NOT NULL,
    game_icon VARCHAR(255) NOT NULL,
    submission_date DATETIME DEFAULT (UTC_TIMESTAMP)
);

-- 4. Create a user for the database if it doesn't exist
-- Note: Replace 'jampack_user' and 'jampack_pass' with your desired username and password
-- Change the host '%' to a specific IP or hostname if needed. For example, 'localhost'.
CREATE USER IF NOT EXISTS 'jampack_user'@'%' IDENTIFIED BY 'jampack_pass';

-- 5. Grant privileges to the user on the database
-- Change the host '%' to a specific IP or hostname if needed. For example, 'localhost'.
GRANT ALL PRIVILEGES ON jampack_db.* TO 'jampack_user'@'%';
FLUSH PRIVILEGES;