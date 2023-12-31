--TEST--
swoole_http_server: delete cookie (same behavior with php-fpm)
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';
$pm = new ProcessManager;
$pm->parentFunc = function () use ($pm) {
    go(function () use ($pm) {
        $cli = new Swoole\Coroutine\Http\Client('127.0.0.1', $pm->getFreePort());
        $cookie = '123_,; abc';
        Assert::assert($cli->get('/?cookie=' . urlencode($cookie)));
        Assert::same($cli->statusCode, 200);
        Assert::assert($cli->set_cookie_headers ===
            [
                'cookie1=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0',
                'cookie2=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0',
                'cookie3=cookie3',
                'cookie4=cookie4',
                'cookie5=cookie5; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0',
                'cookie6=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0',
                'cookie7=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0',
                'cookie8=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0',
            ]
        );
    });
    Swoole\Event::wait();
    echo "SUCCESS\n";
    $pm->kill();
};
$pm->childFunc = function () use ($pm) {
    $http = new Swoole\Http\Server('0.0.0.0', $pm->getFreePort(), SWOOLE_BASE);
    $http->set(['worker_num' => 1, 'log_file' => '/dev/null']);
    $http->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->cookie('cookie1', null);
        $response->cookie('cookie2', '');
        $response->cookie('cookie3', 'cookie3', 0); // must be > 0
        $response->cookie('cookie4', 'cookie4', -1); // must be > 0
        $response->cookie('cookie5', 'cookie5', 1);
        $response->cookie('cookie6', null, 0);
        $response->cookie('cookie7', null, -1);
        $response->cookie('cookie8', null, 1);
        $response->end();
    });
    $http->start();
};
$pm->childFirst();
$pm->run();
?>
--EXPECT--
SUCCESS
