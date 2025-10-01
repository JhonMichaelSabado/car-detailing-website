USE car_detailing;

-- Add google_id column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) UNIQUE AFTER id;

-- Add first_name column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER email;

-- Add last_name column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER first_name;

-- Add role column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'user' AFTER last_name;

-- Add is_active column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER role;

-- Add reset_token column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) AFTER is_active;

-- Add reset_expires column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires DATETIME AFTER reset_token;
