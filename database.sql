-- Create the calls table
CREATE TABLE IF NOT EXISTS calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caller VARCHAR(255),
    transcribed_text TEXT,
    sentiment VARCHAR(50),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add indexes for better query performance
CREATE INDEX idx_sentiment ON calls(sentiment);
CREATE INDEX idx_timestamp ON calls(timestamp);
CREATE INDEX idx_caller ON calls(caller); 