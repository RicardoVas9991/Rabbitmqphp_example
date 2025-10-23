SELECT
    h.quantity,
    s.symbol,
    s.name,
    sp.current_price,
    (h.quantity * sp.current_price) AS total_value
FROM holdings h
JOIN stocks s ON h.stock_id = s.id
JOIN stock_prices sp ON h.stock_id = sp.stock_id
JOIN portfolios p ON h.portfolio_id = p.id
WHERE p.user_id = ?; 

