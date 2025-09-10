<?php

namespace Longriders\Flysystem\Gameap;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\SafeStorage;
use League\Flysystem\AdapterInterface;

use Knik\Gameap\GdaemonFiles;

abstract class GameapAbstractAdapter extends AbstractAdapter
{
    /**
     * @var GdaemonFiles
     */
    protected $connection;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port = 31717;

    /**
     * @var int
     */
    protected $timeout = 10;

    /**
     * @var SafeStorage
     */
    protected $safeStorage;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * @var string
     */
    protected $serverCertificate;

    /**
     * @var string
     */
    protected $localCertificate;

    /**
     * @var int
     */
    protected $permPublic = 0744;

    /**
     * @var int
     */
    protected $permPrivate = 0700;

    /**
     * @var array
     */
    protected $configurable = [];

    /**
     * Connect to the server.
     */
    public function connect()
    {
        $this->connection = new GdaemonFiles([
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'localCertificate' => $this->getLocalCertificate(),
            'serverCertificate' => $this->getServerCertificate(),
            'privateKey' => $this->getPrivateKey(),
            'privateKeyPass' => $this->getPrivateKeyPass(),
            'timeout' => $this->getTimeout(),
        ]);
    }

    /**
     * Disconnect
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->connection->disconnect();
        }
    }

    /**
     * @return GDaemonFiles
     */
    public function getConnection()
    {
        $tries = 0;

        while ( ! $this->isConnected() && $tries < 3) {
            $tries++;
            $this->disconnect();
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * @param GdaemonFiles $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->safeStorage = new SafeStorage();
        $this->setConfig($config);
    }

    /**
     * Set the config.
     *
     * @param array $config
     *
     * @return $this
     */
    public function setConfig(array $config)
    {
        foreach ($this->configurable as $setting) {
            if ( ! isset($config[$setting])) {
                continue;
            }

            $method = 'set' . ucfirst($setting);

            if (method_exists($this, $method)) {
                $this->$method($config[$setting]);
            }
        }

        return $this;
    }

    /**
     * Disconnect on destruction.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Returns the username.
     *
     * @return string username
     */
    public function getUsername()
    {
        $username = $this->safeStorage->retrieveSafely('username');

        return $username !== null ? $username : 'anonymous';
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->safeStorage->storeSafely('username', $username);

        return $this;
    }

    /**
     * Returns the password.
     *
     * @return string password
     */
    public function getPassword()
    {
        return $this->safeStorage->retrieveSafely('password');
    }

    /**
     * Set the password.
     *
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->safeStorage->storeSafely('password', $password);

        return $this;
    }

    /**
     * Returns the password.
     *
     * @return string password
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Set the private key (string or path to local file).
     *
     * @param string $key
     *
     * @return $this
     */
    public function setPrivateKey($key)
    {
        $this->privateKey = $key;

        return $this;
    }

    /**
     * Returns the password.
     *
     * @return string password
     */
    public function getPrivateKeyPass()
    {
        return $this->safeStorage->retrieveSafely('privateKeyPass');
    }

    /**
     * Set the password.
     *
     * @param string $password
     *
     * @return $this
     */
    public function setPrivateKeyPass($privateKeyPass)
    {
        $this->safeStorage->storeSafely('privateKeyPass', $privateKeyPass);

        return $this;
    }

    /**
     * Returns the host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the host.
     *
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Returns the port.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the port.
     *
     * @param int|string $port
     *
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = (int) $port;

        return $this;
    }

    /**
     * Returns the amount of seconds before the connection will timeout.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set the amount of seconds before the connection should timeout.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;

        return $this;
    }

    /**
     * Returns the root folder to work from.
     *
     * @return string
     */
    public function getRoot()
    {
        return $this->getPathPrefix();
    }

    /**
     * Set the root folder to work from.
     *
     * @param string $root
     *
     * @return $this
     */
    public function setRoot($root)
    {
        $this->setPathPrefix($root);
        return $this;
    }

    /**
     * @return string
     */
    public function getLocalCertificate()
    {
        return $this->localCertificate;
    }

    /**
     * @param $localCertificate
     * @return $this
     */
    public function setLocalCertificate($localCertificate)
    {
        $this->localCertificate = $localCertificate;
        return $this;
    }

    /**
     * @return string
     */
    public function getServerCertificate()
    {
        return $this->serverCertificate;
    }

    /**
     * @param $serverCertificate
     * @return $this
     */
    public function setServerCertificate($serverCertificate)
    {
        $this->serverCertificate = $serverCertificate;
        return $this;
    }

    /**
     * Normalize a listing response.
     *
     * @param string $path
     * @param array  $object
     *
     * @return array
     */
    protected function normalizeListingObject($path, array $object)
    {
        $type = $object['type'];
        $timestamp = $object['mtime'];

        if ($type === 'dir') {
            return compact('path', 'timestamp', 'type');
        }

        $visibility = $object['permissions'] & (0777 ^ $this->permPrivate)
            ? AdapterInterface::VISIBILITY_PUBLIC
            : AdapterInterface::VISIBILITY_PRIVATE;

        $size = (int) $object['size'];

        return compact('path', 'timestamp', 'type', 'visibility', 'size');
    }

    /**
     * Check if a connection is active.
     *
     * @return bool
     */
    abstract public function isConnected();
}