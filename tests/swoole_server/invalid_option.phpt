--TEST--
swoole_server: invalid option
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';

use Swoole\Server;

$serv = new Server('127.0.0.1', 0, SWOOLE_BASE);
$options = [
    'worker_num' => 1,
    'backlog' => 128,
    'invalid_option' => true,
];

try {
    $serv->set($options);
} catch (\Swoole\Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECTF--
Warning: unsupported option [invalid_option] in @swoole/library/core/Server/Helper.php on line %d
%A
%A
%A
