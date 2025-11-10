<?php
require __DIR__ . '/config.php';
require __DIR__ . '/rpc_client_ini.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "No query provided"]);
    exit;
}

if (USE_RABBITMQ){
    try{
        $rpc = new RmqRpcClientIni("testRabbitMQ.ini", 'sharedServer');
        $res = $rpc->call('SYMBOL_SEARCH', $q, RPC_TIMEOUT_MS);
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
} else {
    $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=$symbol&apikey=$api_key" . '?function=SYMBOL_SEARCH&keywords=' . urlencode($q) . '&apikey=' . X06XHO4GPPMMFGJJ;
    $data = @file_get_contents($url);
    echo $data ?: json_encode(["ok" => false, "error" => "Failed to fetch data from Alpha Vantage"]);
}
?>  
