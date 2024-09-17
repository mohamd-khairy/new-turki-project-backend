CREATE VIEW orders_dashboard_view AS
SELECT
    orders.id AS id,
    orders.ref_no AS ref_no,
    orders.delivery_date AS order_delivery_date,
    orders.user_id AS user_id,
    orders.sales_representative_id AS sales_representative_id,
    orders.wallet_amount_used AS wallet_amount_used,
    customers.id AS customer_id,
    customers.name AS customer_name,
    customers.mobile AS customer_mobile,
    orders.order_state_id AS order_state_id,
    order_states.state_ar AS order_state_ar,
    order_states.state_en AS order_state_en,
    shalwatas.id AS shalwata_id,
    shalwatas.name_ar AS shalwata_name,
    shalwatas.price AS shalwata_price,
    payment_types.id AS payment_type_id,
    payment_types.name_ar AS payment_type_name,
    payment_types.code AS payment_type_code,
    delivery_periods.id AS delivery_period_id,
    delivery_periods.name_ar AS delivery_period_name,
    delivery_periods.time_hhmm AS delivery_period_time,
    payments.id AS payment_id,
    payments.price AS payment_price,
    payments.status AS payment_status,
    addresses.id AS address_id,
    addresses.address AS address_address,
    addresses.lat AS address_lat,
    addresses.long AS address_long,
    addresses.country_id AS country_id,
    addresses.country_id AS address_country_id,
    addresses.city_id AS city_id,
    cities.name_ar AS city_name,
    users.username AS sales_officer_name,
    u.username AS driver_name,
    u.id AS driver_id,
    orders.total_amount_after_discount AS total_amount_after_discount
    orders.printed_at As printed_at
FROM
    orders
LEFT JOIN customers ON customers.id = orders.customer_id
LEFT JOIN users AS u ON u.id = orders.user_id
LEFT JOIN users ON users.id = orders.sales_representative_id
LEFT JOIN order_states ON order_states.code = orders.order_state_id
LEFT JOIN shalwatas ON shalwatas.id = orders.shalwata_id
LEFT JOIN payment_types ON payment_types.id = orders.payment_type_id
LEFT JOIN delivery_periods ON delivery_periods.id = orders.delivery_period_id
LEFT JOIN payments ON payments.id = orders.payment_id
LEFT JOIN addresses ON addresses.id = orders.address_id
LEFT JOIN cities ON cities.id = addresses.city_id;
