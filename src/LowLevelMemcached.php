<?php namespace Vacuum;

/**
 * Самостоятельное общение с сервером memcached
 */
class LowLevelMemcached
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var resource
     */
    protected $connection;

    public function __construct(string $host = '127.0.0.1', int $port = 11211, int $timeout = 2)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;

        $this->connect();
    }

    public function __destruct()
    {
        if (is_resource($this->connection)) {
            socket_close($this->connection);
        }
    }

    public function delete(string $key): bool
    {
        // delete <key> [noreply]\r\n
        $request = "delete {$key}\r\n";

        return trim($this->request($request)) === 'DELETED';
    }

    public function fetch()
    {
        $read = [$this->connection];
        $write = null;
        $except = null;
        $running = true;
        $response = '';

        while ($running) {
            if (socket_select($read, $write, $except, $this->timeout) < 1) {
                continue;
            }

            foreach ($read as $socket) {
                $response = $this->response($socket, function () use (&$running) {
                    $running = false;
                });
            }
        }

        return $this->parseReadResponse($response);
    }

    public function get(string $key)
    {
        // get <key>\r\n
        $request = "get {$key}\r\n";

        return $this->parseReadResponse($this->request($request));
    }

    public function getLater(string $key): int
    {
        // get <key>\r\n
        $request = "get {$key}\r\n";

        return socket_write($this->connection, $request);
    }

    public function set(string $key, $data, int $exptime = 0): bool
    {
        // set <key> <flags> <exptime> <bytes> [noreply]\r\n<data block>\r\n
        $bytes = strlen($data);
        $request = "set {$key} 0 {$exptime} {$bytes}\r\n{$data}\r\n";

        return trim($this->request($request)) === 'STORED';
    }

    protected function connect(): void
    {
        if (false === $this->connection = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            throw new \Exception('Ошибка создания сокета. ' . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->connection, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->timeout, 'usec' => 0]);

        if (false === socket_connect($this->connection, $this->host, $this->port)) {
            throw new \Exception('Ошибка подключения к серверу memcached. ' . socket_strerror(socket_last_error()));
        }
    }

    protected function parseReadResponse(string $response): ?string
    {
        return preg_match("/^VALUE [^\s]+ \d+ \d+\\r\\n(.+)\\r\\nEND\\r\\n$/ms", $response, $matches)
            ? $matches[1]
            : null;
    }

    protected function response($socket, callable $callback_on_break = null): string
    {
        $response = '';

        while ($buffer = socket_read($socket, 2048)) {
            $response .= $buffer;

            if (preg_match('/^DELETED|END|NOT_FOUND|STORED\\r\\n$/', $response)) {
                if (is_callable($callback_on_break)) {
                    $callback_on_break();
                }

                break;
            }
        }

        return $response;
    }

    protected function request(string $data = ''): string
    {
        socket_write($this->connection, $data);

        return $this->response($this->connection);
    }
}
