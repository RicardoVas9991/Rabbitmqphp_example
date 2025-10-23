#!/usr/bin/php
<?php

require_once('/var/www/sample/mysqlconnect.php'); 

echo "Running alert checker...\n";

try {
    $prices_raw = $pdo->query("SELECT stock_id, current_price FROM stock_prices")->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $pdo->prepare("SELECT id, user_id, stock_id, target_price, `condition` FROM price_alerts WHERE is_triggered = 0");
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $triggered_ids = [];

    foreach ($alerts as $alert) {
        if (!isset($prices_raw[$alert['stock_id']])) {
            continue;
        }

        $current_price = $prices_raw[$alert['stock_id']];
        $is_triggered = false;

        if ($alert['condition'] === 'ABOVE' && $current_price >= $alert['target_price']) {
            $is_triggered = true;
            $message = "Alert! Stock {$alert['stock_id']} is now \${$current_price}, which is above your target of \${$alert['target_price']}.";
        } elseif ($alert['condition'] === 'BELOW' && $current_price <= $alert['target_price']) {
            $is_triggered = true;
            $message = "Alert! Stock {$alert['stock_id']} is now \${$current_price}, which is below your target of \${$alert['target_price']}.";
        }

        if ($is_triggered) {
            echo "Triggering alert ID {$alert['id']}\n";
            $triggered_ids[] = $alert['id'];
            
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notif_stmt->execute([$alert['user_id'], $message]);
        }
    }

    if (!empty($triggered_ids)) {
        $placeholders = implode(',', array_fill(0, count($triggered_ids), '?'));
        
        $update_stmt = $pdo->prepare("UPDATE price_alerts SET is_triggered = 1 WHERE id IN ($placeholders)");
        $update_stmt->execute($triggered_ids);
        
        echo "Marked " . count($triggered_ids) . " alerts as triggered.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Alert check complete.\n";

