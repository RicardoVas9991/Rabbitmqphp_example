<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/rpc_client_ini.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(["ok" => false, "error" => "No query provided"]);
    exit;
}

$rpc = new RmqRpcClientIni(__DIR__ . '/testRabbitMQ.ini', 'sharedServer2');
$res = $rpc->call('SYMBOL_SEARCH', $q, 7000);
$rpc->close();
echo json_encode($res ?? ["ok" => false, "error" => "No response from server"]);
?>