CREATE TABLE bookings (
  id int(11) NOT NULL,
  client_name varchar(100) NOT NULL,
  client_email varchar(150) DEFAULT NULL,
  user_id int(11) DEFAULT NULL,
  client_phone varchar(20) NOT NULL,
  service_id int(11) NOT NULL,
  total_price decimal(10,2) NOT NULL,
  booking_date date NOT NULL,
  booking_time time NOT NULL,
  duration_minutes int(11) NOT NULL,
  extras_list text DEFAULT NULL,
  is_home_service tinyint(1) NOT NULL,
  persons tinyint(4) NOT NULL,
  notes text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE booking_sessions (
  session_id varchar(100) NOT NULL,
  selected_service longtext DEFAULT NULL,
  selected_extras longtext DEFAULT NULL,
  additional_persons int(11) DEFAULT 1,
  home_service tinyint(1) DEFAULT 0,
  booking_date date DEFAULT NULL,
  booking_time time DEFAULT NULL,
  booking_notes longtext DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  expires_at timestamp NULL DEFAULT NULL
);

CREATE TABLE services (
  service_id int(11) NOT NULL,
  type_id int(11) NOT NULL,
  label varchar(150) NOT NULL,
  description text DEFAULT NULL,
  price decimal(10,2) NOT NULL DEFAULT 0.00,
  duration_minutes int(11) NOT NULL DEFAULT 0,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE service_types (
  type_id int(11) NOT NULL,
  name varchar(100) NOT NULL,
  description text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE individual (
  id int(11) NOT NULL,
  name varchar(100) NOT NULL,
  email varchar(150) NOT NULL,
  password_hash varchar(255) NOT NULL,
  role enum('user','admin') NOT NULL DEFAULT 'user',
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
);

INSERT INTO service_types (type_id, name, description, created_at) VALUES
(1, 'lashes_full_set', 'Full set lash services', '2025-12-17 19:40:14'),
(2, 'refill', 'Refill services', '2025-12-17 19:40:14'),
(3, 'extra', 'Extras and add-ons', '2025-12-17 19:40:14'),
(4, 'brow', 'Brow services', '2025-12-17 19:40:14'),
(5, 'Home Service', 'Home Services', '2025-12-19 10:04:41');

INSERT INTO services (service_id, type_id, label, description, price, duration_minutes, is_active, created_at) VALUES
(1, 1, 'Classic Full Set', 'Classic lash full set', 120.00, 90, 1, '2025-12-17 19:40:14'),
(2, 1, 'Hybrid Full Set', 'Hybrid lash full set', 140.00, 100, 1, '2025-12-17 19:40:14'),
(3, 1, 'Volume Full Set', 'Volume lash full set', 160.00, 120, 1, '2025-12-17 19:40:14'),
(4, 2, '2-3 Week Refill', 'Refill for 2-3 weeks', 60.00, 45, 1, '2025-12-17 19:40:14'),
(5, 2, '4-6 Week Refill', 'Refill for 4-6 weeks', 80.00, 60, 1, '2025-12-17 19:40:14'),
(6, 4, 'Brow Shaping', 'Brow shaping and tint', 45.00, 30, 1, '2025-12-17 19:40:14'),
(7, 3, 'Lash Tint', 'Tinting for lashes', 25.00, 20, 1, '2025-12-17 19:40:14'),
(8, 1, 'Classic', NULL, 130.00, 60, 1, '2025-12-17 19:40:14'),
(9, 1, 'Classic Mix', NULL, 150.00, 60, 1, '2025-12-17 19:40:14'),
(10, 1, 'Hybrid', NULL, 180.00, 80, 1, '2025-12-17 19:40:14'),
(11, 1, 'Volume', NULL, 220.00, 90, 1, '2025-12-17 19:40:14'),
(12, 1, 'Wet Set', NULL, 200.00, 80, 1, '2025-12-17 19:40:14'),
(13, 1, 'Lash Lift', NULL, 100.00, 45, 1, '2025-12-17 19:40:14'),
(14, 2, 'Classic Refill', NULL, 90.00, 75, 1, '2025-12-17 19:40:14'),
(15, 2, 'Classic Mix Refill', NULL, 110.00, 75, 1, '2025-12-17 19:40:14'),
(16, 2, 'Hybrid Refill', NULL, 130.00, 75, 1, '2025-12-17 19:40:14'),
(17, 2, 'Volume Refill', NULL, 160.00, 75, 1, '2025-12-17 19:40:14'),
(18, 2, 'Wet Set Refill', NULL, 150.00, 75, 1, '2025-12-17 19:40:14'),
(19, 3, 'Wispy', NULL, 60.00, 20, 1, '2025-12-17 19:40:14'),
(20, 3, 'Bottom Lashes', NULL, 60.00, 30, 1, '2025-12-17 19:40:14'),
(21, 3, 'Removal', NULL, 40.00, 15, 1, '2025-12-17 19:40:14'),
(22, 5, 'Home Service', 'get your home services', 450.00, 3, 1, '2025-12-19 11:10:34');

ALTER TABLE bookings
  ADD PRIMARY KEY (id),
  ADD KEY service_id (service_id),
  ADD KEY idx_bookings_user (user_id);

ALTER TABLE booking_sessions
  ADD PRIMARY KEY (session_id),
  ADD KEY idx_updated (updated_at);

ALTER TABLE services
  ADD PRIMARY KEY (service_id),
  ADD KEY type_id (type_id);

ALTER TABLE service_types
  ADD PRIMARY KEY (type_id),
  ADD UNIQUE KEY name (name);

-- Assuming 'users' table exists for the foreign key below
ALTER TABLE users
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY email (email);

ALTER TABLE bookings
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE services
  MODIFY service_id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

ALTER TABLE service_types
  MODIFY type_id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE users
  MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- Foreign Keys
ALTER TABLE bookings
  ADD CONSTRAINT bookings_ibfk_1 FOREIGN KEY (service_id) REFERENCES services (service_id),
  ADD CONSTRAINT bookings_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id);

ALTER TABLE services
  ADD CONSTRAINT services_ibfk_1 FOREIGN KEY (type_id) REFERENCES service_types (type_id) ON UPDATE CASCADE;