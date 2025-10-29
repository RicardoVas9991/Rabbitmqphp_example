<?php
require __DIR__ . '/config.php';
require __DIR__ . '/rpc_client_ini.php';

$symbol = strtoupper(trim($_GET['symbol'] ?? ''));

if ($symbol === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "No symbol provided"]);
    exit;
}

if (USE_RABBITMQ){
    try{
        $rpc = new RmqRpcClientIni(RABBITMQ_INI_FILE, 'sharedServer');
        $res = $rpc->call('GLOBAL_QUOTE', $symbol, RPC_TIMEOUT_MS);
        $rpc->close();
        if($res === null) {
            http_response_code(504);
            echo json_encode(["ok" => false, "error" => "No response from server"]);
        } else {
            echo json_encode($res);
        }
    } catch (Throwable $e){
        http_response_code(502);
        echo json_encode(["ok" => false, "error" => "Internal server error", "details" => $e->getMessage()]);
    }
} else{
    $url = ALPHAVANTAGE_API_URL . '?function=GLOBAL_QUOTE&symbol=' . urlencode($symbol) . '&apikey=' . ALPHAVANTAGE_API_KEY;
    $data = @file_get_contents($url);
    echo $data ?: json_encode(["ok" => false, "error" => "Failed to fetch data from Alpha Vantage"]);
}
?>