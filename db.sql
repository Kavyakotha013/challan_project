CREATE DATABASE railway_pollution_db;
USE railway_pollution_db;

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_name VARCHAR(100) NOT NULL,
    vehicle_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    sensor_code VARCHAR(50) UNIQUE NOT NULL,
    contact_details VARCHAR(100) NOT NULL,
    registration_date DATE NOT NULL,
    violation_count INT DEFAULT 0
);

CREATE TABLE violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    sensor_code VARCHAR(50) NOT NULL,
    violation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pollution_value FLOAT,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE challans (

sql
CREATE DATABASE railway_pollution_db;
USE railway_pollution_db;

-- Vehicles table
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_name VARCHAR(100) NOT NULL,
    vehicle_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    sensor_code VARCHAR(50) UNIQUE NOT NULL,
    contact_details VARCHAR(100) NOT NULL,
    registration_date DATE NOT NULL,
    violation_count INT DEFAULT 0
);

-- Violations log
CREATE TABLE violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    sensor_code VARCHAR(50) NOT NULL,
    violation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pollution_value FLOAT,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Challans table
CREATE TABLE challans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    challan_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Paid') DEFAULT 'Pending',
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);