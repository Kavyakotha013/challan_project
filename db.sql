-- Violations table fix
ALTER TABLE violations 
DROP INDEX uq_vehicle,
ADD UNIQUE KEY uq_vehicle_sensor (vehicle_id, sensor_code),
MODIFY violation_count INT DEFAULT 1,
MODIFY violation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Challans table fix
ALTER TABLE challans 
ADD COLUMN count INT DEFAULT 1,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD UNIQUE KEY uq_vehicle_violation_status (vehicle_id, violation_count, status);
