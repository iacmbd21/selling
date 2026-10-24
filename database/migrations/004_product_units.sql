ALTER TABLE products
  ADD COLUMN unit VARCHAR(16) NOT NULL DEFAULT 'pc' AFTER stock;

ALTER TABLE order_items
  ADD COLUMN unit VARCHAR(16) NOT NULL DEFAULT 'pc' AFTER quantity;

UPDATE products SET unit = 'kg' WHERE sku = 'RICE-5KG';

UPDATE products SET unit = 'l' WHERE sku IN ('OIL-5L', 'MILK-1L');
