--TEST--
swoole_server: send big pipe message
--SKIPIF--
<?php
require __DIR__ . '/../include/skipif.inc';
skip_if_extension_not_exist('sockets');
?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';
$port = get_one_free_port();

const N = 1024 * 1024 * 1;

$pm = new SwooleTest\ProcessManager;

$pm->parentFunc = function ($pid) use ($port)
{
    $client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC); //同步阻塞
    if (!$client->connect('127.0.0.1', $port))
    {
        exit("connect failed\n");
    }

    $socket = $client->getSocket();
    socket_set_option($socket, SOL_SOCKET, SO_SNDBUF, 65536);
    socket_set_option($socket, SOL_SOCKET, SO_RCVBUF, 65536);

    $bytes = 0;
    while($bytes < N)
    {
        $n = rand(8192, 65536);
        $r = $client->recv($n);
        if (!$r)
        {
            break;
        }
        usleep(10000);
        $bytes += strlen($r);
    }
    Assert::same($bytes, N);
    Swoole\Process::kill($pid);
};

$pm->childFunc = function () use ($pm, $port)
{
    $serv = new Swoole\Server('127.0.0.1', $port, SWOOLE_PROCESS);
    $serv->set([
        'worker_num' => 1,
        'log_file' => '/dev/null',
        'kernel_socket_send_buffer_size' => 65536,
    ]);
    $serv->on("workerStart", function ($serv) use ($pm)
    {
        $pm->wakeup();
    });
    $serv->on('connect', function (Swoole\Server $serv, $fd)
    {
        $_send_data = str_repeat("A", N);
        $serv->send($fd, $_send_data);
    });
    $serv->on('receive', function ($serv, $fd, $reactor_id, $data)
    {

    });
    $serv->start();
};

$pm->childFirst();
$pm->run();
?>
--EXPECT--
