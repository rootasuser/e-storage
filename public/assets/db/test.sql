CREATE DATABASE IF NOT EXISTS e_storage_db;
USE e_storage_db;


CREATE TABLE IF NOT EXISTS users_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    role ENUM('Admin', 'Student', 'Faculty') NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_pic LONGBLOB DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users_tbl
    ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending';


INSERT INTO users_tbl (first_name, middle_name, last_name, gender, role, username, email, password) VALUES
('John', 'A', 'Doe', 'Male', 'Admin', 'admin123', 'admin@example.com', '$2y$10$fjYR7A0jo9QBmjQBTYWPF.DdcR3bDRkaungr5KjqIAOQzkmrq4l2y');


CREATE TABLE IF NOT EXISTS research_titles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_year VARCHAR(10) NOT NULL,
    members_name TEXT NOT NULL, -- JSON format for multiple members
    title_of_study VARCHAR(255) NOT NULL,
    adviser TEXT NOT NULL, -- JSON format for multiple advisers
    manuscript VARCHAR(255) NOT NULL, -- File path instead of BLOB
    abstract VARCHAR(255) NOT NULL, -- File path instead of BLOB
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE research_titles
    ADD COLUMN specialization VARCHAR(100) NOT NULL;
ALTER TABLE research_titles
    ADD COLUMN special_order VARCHAR(255) NOT NULL;



CREATE TABLE IF NOT EXISTS access_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    research_id INT NOT NULL,
    file_type ENUM('Manuscript', 'Abstract') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users_tbl(id) ON DELETE CASCADE,
    FOREIGN KEY (research_id) REFERENCES research_titles(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users_tbl(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS research_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title_of_study VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users_tbl(id) ON DELETE CASCADE
);