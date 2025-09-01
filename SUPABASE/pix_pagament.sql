CREATE TABLE pix_payments (
    id SERIAL PRIMARY KEY,
    order_id TEXT NOT NULL,
    payload TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);