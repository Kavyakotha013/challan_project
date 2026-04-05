CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_name VARCHAR(100),
  vehicle_number VARCHAR(50) UNIQUE,
  vehicle_type VARCHAR(50),
  sensor_code VARCHAR(50) UNIQUE,
  contact_details VARCHAR(100),
  owner_email VARCHAR(100),
  violation_count INT DEFAULT 0,
  registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE violations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT,
  sensor_code VARCHAR(50),
  pollution_value FLOAT,
  violation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE gov_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password_hash VARCHAR(255)
);

-- Insert constant government user (password = 'secure123')
INSERT INTO gov_users (username, password_hash)
VALUES ('gov_officer', '$2y$10$HASHGENERATEDWITHPASSWORD_HASH');