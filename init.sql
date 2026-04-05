CREATE TABLE IF NOT EXISTS gov_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_name VARCHAR(255) NOT NULL,
  vehicle_number VARCHAR(50) UNIQUE NOT NULL,
  vehicle_type VARCHAR(100) NOT NULL,
  sensor_code VARCHAR(100) UNIQUE NOT NULL,
  contact_details VARCHAR(20) NOT NULL,
  owner_email VARCHAR(255) NOT NULL,
  violation_count INT DEFAULT 0,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS violations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT,
  sensor_code VARCHAR(100) NOT NULL,
  pollution_value FLOAT NOT NULL,
  violation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

DELETE FROM gov_users WHERE username='admin';

INSERT INTO gov_users (username, password_hash)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
