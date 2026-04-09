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
  vehicle_id INT NOT NULL,
  sensor_code VARCHAR(100) NOT NULL,
  pollution_value FLOAT NOT NULL,
  violation_count INT DEFAULT 0,
  violation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  violation_min_count INT DEFAULT 0,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  UNIQUE KEY uq_vehicle (vehicle_id)

);

CREATE TABLE IF NOT EXISTS challans (
 id INT AUTO_INCREMENT PRIMARY KEY,
 vehicle_id INT NOT NULL,
 amount DECIMAL(10, 2) NOT NULL,
 status VARCHAR(50) NOT NULL,
 violation_count INT NOT NULL DEFAULT 0,
 count INT NOT NULL DEFAULT 1,
 challan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);
CREATE TABLE IF NOT EXISTS alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  alert_type ENUM('warning', 'critical') NOT NULL,
  pollution_value FLOAT NOT NULL,
  alert_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active', 'resolved') NOT NULL DEFAULT 'active',
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Admin user
DELETE FROM gov_users WHERE username='admin';
INSERT INTO gov_users (username, password_hash)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
