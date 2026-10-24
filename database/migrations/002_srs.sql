ALTER TABLE orders MODIFY status ENUM('pending','confirmed','completed','cancelled','returned','processing','shipped','delivered') NOT NULL DEFAULT 'pending';

UPDATE orders SET status = 'delivered' WHERE status = 'completed';

ALTER TABLE orders MODIFY status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','returned') NOT NULL DEFAULT 'pending';

ALTER TABLE orders MODIFY payment_status ENUM('unpaid','pending_verification','paid','rejected','refunded','pending','submitted','verified','failed','partially_refunded') NOT NULL DEFAULT 'pending';

UPDATE orders SET payment_status = 'pending' WHERE payment_status = 'unpaid';

UPDATE orders SET payment_status = 'submitted' WHERE payment_status = 'pending_verification';

UPDATE orders SET payment_status = 'verified' WHERE payment_status = 'paid';

UPDATE orders SET payment_status = 'failed' WHERE payment_status = 'rejected';

ALTER TABLE orders MODIFY payment_status ENUM('pending','submitted','verified','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending';

ALTER TABLE payments MODIFY status ENUM('unpaid','pending_verification','paid','rejected','pending','submitted','verified','failed','refunded','partially_refunded') NOT NULL;

UPDATE payments SET status = 'pending' WHERE status = 'unpaid';

UPDATE payments SET status = 'submitted' WHERE status = 'pending_verification';

UPDATE payments SET status = 'verified' WHERE status = 'paid';

UPDATE payments SET status = 'failed' WHERE status = 'rejected';

ALTER TABLE payments MODIFY status ENUM('pending','submitted','verified','failed','refunded','partially_refunded') NOT NULL;

ALTER TABLE orders
  ADD COLUMN customer_email VARCHAR(190) NULL AFTER mobile,
  ADD COLUMN city VARCHAR(120) NULL AFTER address,
  ADD COLUMN postal_code VARCHAR(20) NULL AFTER city,
  ADD COLUMN country_code CHAR(2) NOT NULL DEFAULT 'BD' AFTER postal_code,
  ADD COLUMN delivery_note VARCHAR(255) NULL AFTER country_code,
  ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'BDT' AFTER payment_status,
  ADD COLUMN idempotency_key VARCHAR(64) NULL AFTER coupon_code,
  ADD UNIQUE KEY uniq_orders_idempotency (idempotency_key);

ALTER TABLE coupons
  ADD COLUMN maximum_discount DECIMAL(12,2) NULL AFTER value,
  ADD COLUMN per_customer_limit INT UNSIGNED NULL AFTER max_uses;

ALTER TABLE products
  ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN image_path VARCHAR(255) NULL AFTER featured;

ALTER TABLE categories
  ADD COLUMN parent_id INT UNSIGNED NULL AFTER id,
  ADD COLUMN description VARCHAR(255) NULL AFTER slug,
  ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER description;

CREATE TABLE shipping_zones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  country_code VARCHAR(8) NOT NULL,
  region_code VARCHAR(40) NOT NULL,
  payment_methods VARCHAR(80) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipping_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  zone_id INT UNSIGNED NOT NULL,
  rate_type ENUM('flat') NOT NULL DEFAULT 'flat',
  base_fee DECIMAL(12,2) NOT NULL,
  free_shipping_threshold DECIMAL(12,2) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  CONSTRAINT fk_rates_zone FOREIGN KEY (zone_id) REFERENCES shipping_zones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  courier_name VARCHAR(120) NULL,
  tracking_number VARCHAR(120) NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'shipped',
  shipped_at DATETIME NULL,
  delivered_at DATETIME NULL,
  CONSTRAINT fk_shipments_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  old_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  note VARCHAR(255) NULL,
  changed_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupon_usages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  customer_mobile VARCHAR(20) NULL,
  discount_amount DECIMAL(12,2) NOT NULL,
  used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_coupon_usage_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  CONSTRAINT fk_coupon_usage_order FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(1000) NULL,
  status ENUM('pending','published','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_review_user_product (user_id, product_id),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_addresses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  recipient_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address_line VARCHAR(500) NOT NULL,
  city VARCHAR(120) NOT NULL,
  postal_code VARCHAR(20) NULL,
  country_code CHAR(2) NOT NULL DEFAULT 'BD',
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE banners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  body VARCHAR(255) NULL,
  link_url VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  title VARCHAR(160) NOT NULL,
  body TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message VARCHAR(2000) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT UNSIGNED NULL,
  metadata_json TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO shipping_zones (name, country_code, region_code, payment_methods) VALUES
('Dhaka', 'BD', 'DHAKA', 'cod,bkash,nagad'),
('Rest of Bangladesh', 'BD', 'OTHER', 'cod,bkash,nagad'),
('International', 'INT', 'ALL', 'bkash,nagad');

INSERT INTO shipping_rates (zone_id, rate_type, base_fee, free_shipping_threshold) VALUES
(1, 'flat', 60.00, 1500.00),
(2, 'flat', 120.00, 1500.00),
(3, 'flat', 900.00, NULL);

INSERT INTO banners (title, body, link_url, status) VALUES
('Order with a name, mobile number, and address', 'Cash on delivery inside Bangladesh. bKash and Nagad are checked by staff before an order is marked verified.', '/order', 'active');

INSERT INTO pages (slug, title, body) VALUES
('delivery', 'Delivery policy', 'Inside Dhaka the delivery fee is 60 BDT. Outside Dhaka it is 120 BDT. Delivery is free when the product subtotal is at least 1500 BDT. International delivery is 900 BDT and cash on delivery is available only in Bangladesh.'),
('returns', 'Return policy', 'A delivered order is cancelled only through the return workflow. Stock is restored when an eligible cancellation or an approved return is recorded. Damaged returns are not restocked automatically.'),
('privacy', 'Privacy policy', 'The shop stores the name, mobile number, and address needed to deliver an order. Staff can see customer details only when their role allows it. Passwords are stored as hashes.'),
('terms', 'Terms', 'Prices, delivery fees, and totals are calculated on the server. Placing an order confirms the product, customer name, mobile number, and delivery address.');
