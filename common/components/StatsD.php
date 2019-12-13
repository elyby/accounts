<?php
declare(strict_types=1);

namespace common\components;

use Domnikl\Statsd\Client;
use Domnikl\Statsd\Connection;
use yii\base\Component;

class StatsD extends Component {

    /**
     * @var string
     */
    public $host;

    /**
     * @var int
     */
    public $port = 8125;

    /**
     * @var string
     */
    public $namespace = '';

    private $client;

    public function inc(string $key): void {
        $this->getClient()->increment($key);
    }

    public function dec(string $key): void {
        $this->getClient()->decrement($key);
    }

    public function count(string $key, int $value): void {
        $this->getClient()->count($key, $value);
    }

    public function time(string $key, float $time): void {
        $this->getClient()->timing($key, floor($time));
    }

    public function startTiming(string $key): void {
        $this->getClient()->startTiming($key);
    }

    public function endTiming(string $key): void {
        $this->getClient()->endTiming($key);
    }

    public function peakMemoryUsage(string $key): void {
        $this->getClient()->memory($key);
    }

    /**
     * Pass delta values as a string.
     * Accepts both positive (+11) and negative (-4) delta values.
     * $statsd->gauge('foobar', 3);
     * $statsd->gauge('foobar', '+11');
     *
     * @param string $key
     * @param string|int $value
     */
    public function gauge(string $key, $value): void {
        $this->getClient()->gauge($key, $value);
    }

    public function set(string $key, int $value): void {
        $this->getClient()->set($key, $value);
    }

    public function getClient(): Client {
        if ($this->client === null) {
            $connection = $this->createConnection();
            $this->client = new Client($connection, $this->namespace);
        }

        return $this->client;
    }

    protected function createConnection(): Connection {
        if (!empty($this->host) && !empty($this->port)) {
            return new Connection\UdpSocket($this->host, (int)$this->port);
        }

        return new Connection\Blackhole();
    }

}
