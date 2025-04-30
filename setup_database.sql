-- Create the database
CREATE DATABASE IF NOT EXISTS sentimentanalyser;

-- Use the database
USE sentimentanalyser;

-- Create the calls table
CREATE TABLE IF NOT EXISTS calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caller VARCHAR(255),
    transcribed_text TEXT,
    sentiment VARCHAR(50),
    timestamp DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 