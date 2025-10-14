<?php

function my_cookie_params(): array {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = preg_replace('/:\d+$/', '', $host);
    $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ($_SERVER['REQUEST_SCHEME'] ?? (isset($_SERVER['HTTPS']) ? 'HTTPS' : 'http')));
    $is_https = $proto === 'https';

    $my_params = [
        'path' => '/',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    if ($host && !filter_var($host, FILTER_VALIDATE_IP) && $host !== 'localhost') {
        $my_params['domain'] = $host;
    }
    return $my_params;
}
function create_my_session_cookie(array $my_session): void{
    $expires = strtotime($my_session['expires'] ?? '+1 hour');
    $cookieParams = my_cookie_params();
    $cookieParams['expires'] = $expires;

    if (!empty($my_session['session_id'])) {
        setcookie('session_id', $my_session['session_id'], $cookieParams);
    }   

    if(!empty($my_session['auth_token'])) {
        setcookie('auth_token', $my_session['auth_token'], $cookieParams);
    }
}

function erase_my_session_cookies(): void {
    $my_cookie_base = my_cookie_params();

    $base['expires'] = time() - 3600;

    foreach (['session_id', 'auth_token'] as $cookie_name) {
        setcookie($cookie_name, '', $base);
    }
}
?>
