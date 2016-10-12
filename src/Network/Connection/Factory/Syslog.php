<?php
/**
 * Created by IntelliJ IDEA.
 * User: winglechen
 * Date: 16/4/5
 * Time: 13:03
 */

namespace Zan\Framework\Network\Connection\Factory;

use swoole_client as SwooleClient;
use Zan\Framework\Contract\Network\ConnectionFactory;
use Zan\Framework\Network\Connection\Driver\Syslog as SyslogDriver;
use Zan\Framework\Network\Server\Timer\Timer;

class Syslog implements ConnectionFactory
{
    const CONNECT_TIMEOUT = 3000;

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function create()
    {
        $socket = new SwooleClient(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $connection = new SyslogDriver();
        $connection->setSocket($socket);
        $connection->setConfig($this->config);
        $connection->init();

        //call connect
        $socket->connect($this->config['host'], $this->config['port']);

        $connectTimeout = isset($this->config['connect_timeout']) ? $this->config['connect_timeout'] : self::CONNECT_TIMEOUT;
        Timer::after($connectTimeout, $this->getConnectTimeoutCallback($connection), $connection->getConnectTimeoutJobId());

        return $connection;
    }

    public function getConnectTimeoutCallback(SyslogDriver $connection)
    {
        return function() use ($connection) {
            $connection->close();
            $connection->onConnectTimeout();
        };
    }

    public function close()
    {

    }

}
