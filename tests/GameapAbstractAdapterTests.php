<?php

use Longriders\Flysystem\Gameap\GameapAbstractAdapter;
use Knik\Gameap\GdaemonFiles;
use League\Flysystem\SafeStorage;
use PHPUnit\Framework\TestCase;
use Longriders\Flysystem\Gameap\GameapAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Config;

/**
 * @covers Longriders\Flysystem\Gameap\GameapAdapter<extended>
 */
class GameapAbstractAdapterTests extends TestCase
{
    public function adapterProvider()
    {
        $adapter = new GameapAdapter([
            'host' => 'localhost',
            'port' => 31717,
            'username' => 'test',
            'password' => 'test',
            'serverCertificate' => '/server.crt',
            'localCertificate' => '/client.crt',
            'privateKey' => '/private.key',
            'privateKeyPass' => 'pr1vate_Key_pa$$',
            'root' => __DIR__,
            'timeout' => 10,
        ]);

        $mock = Mockery::mock('Knik\Gameap\GdaemonFiles')->makePartial();

        $mock->shouldReceive('__construct');
        $mock->shouldReceive('__toString')->andReturn('GdaemonFiles');
        
        $adapter->setConnection($mock);

        $filesystem = new Filesystem($adapter);

        return [
            [$filesystem, $adapter, $mock],
        ];
    }

    public function testAdapter()
    {
        $adapter = new GameapTestAdapter([
            'host' => '127.0.0.1',
            'port' => 31718,
            'username' => 'knik',
            'password' => 'pa$$w0rd',
            'privateKey' => 'private_key',
            'privateKeyPass' => 'pr1vate_keyPa$$',
            'root' => '/home/gameap/',
            'nonexist' => 'blabla',

            // Invalid options
            'permPublic' => 0600,
            'permPrivate' => 0600,
        ]);

        $this->assertEquals('127.0.0.1', $adapter->host);
        $this->assertEquals(31718, $adapter->port);
        $this->assertEquals('knik', $adapter->getUsername());
        $this->assertEquals('pa$$w0rd', $adapter->getPassword());
        $this->assertEquals('private_key', $adapter->getPrivateKey());
        $this->assertEquals('pr1vate_keyPa$$', $adapter->getPrivateKeyPass());
        $this->assertEquals('/home/gameap/', $adapter->getRoot());

        $this->assertEquals(0744, $adapter->permPublic);
        $this->assertEquals(0700, $adapter->permPrivate);
    }

    public function setGetMethodProvider()
    {
        $resources = $this->adapterProvider();
        list($filesystem, $adapter, $mock) = reset($resources);

        return [
            [$filesystem, $adapter, $mock, 'host', 'localhost', 'string'],
            [$filesystem, $adapter, $mock, 'port', 31717, 'integer'],
            [$filesystem, $adapter, $mock, 'username', 'username', 'string'],
            [$filesystem, $adapter, $mock, 'password', 'pa$$w0rd', 'string'],
            [$filesystem, $adapter, $mock, 'serverCertificate', '/server.crt', 'string'],
            [$filesystem, $adapter, $mock, 'localCertificate', '/client.crt', 'string'],
            [$filesystem, $adapter, $mock, 'privateKey', '/private.key', 'string'],
            [$filesystem, $adapter, $mock, 'privateKeyPass', 'privateKeyPass', 'string'],
            [$filesystem, $adapter, $mock, 'timeout', 10, 'integer'],
        ];
    }

    /**
     * @dataProvider  setGetMethodProvider
     */
    public function testSetGetMethods($filesystem, $adapter, $mock, $param, $value, $type)
    {
        $setMethod = 'set' . ucfirst($param);
        $getMethod = 'get' . ucfirst($param);
        
        $adapter->{$setMethod}($value);
        
        $this->assertInternalType($type, $adapter->{$getMethod}());
        $this->assertEquals($value, $adapter->{$getMethod}());
    }

    /**
     * @dataProvider adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testSetConfig($filesystem, $adapter, $mock)
    {
        $defaultTimeout = $adapter->getTimeout();
        
        $adapter->setConfig([
            'host' => '127.0.0.1',
            'port' => 31718,
            'username' => 'knik',
            'password' => 'pa$$w0rd',
            'privateKey' => 'private_key',
            'privateKeyPass' => 'pr1vate_keyPa$$',
            'root' => '/home/gameap/',
            //'timeout' => 99,
            'nonexist' => 'blabla',
        ]);
        
        $this->assertEquals('127.0.0.1', $adapter->getHost());
        $this->assertEquals(31718, $adapter->getPort());
        $this->assertEquals('knik', $adapter->getUsername());
        $this->assertEquals('pa$$w0rd', $adapter->getPassword());
        $this->assertEquals('private_key', $adapter->getPrivateKey());
        $this->assertEquals('pr1vate_keyPa$$', $adapter->getPrivateKeyPass());
        $this->assertEquals('/home/gameap/', $adapter->getRoot());
        $this->assertEquals($defaultTimeout, $adapter->getTimeout());
        
        $this->assertFalse(method_exists($adapter, 'nonexist'));
    }

    /**
     * @dataProvider adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testGetConnection($filesystem, $adapter, $mock)
    {
        $mock = Mockery::mock('Knik\Gameap\GdaemonFiles')->makePartial();
        $adapter->setConnection($mock);

        $this->assertEquals($mock, $adapter->getConnection());
    }

    public function testGetConnectionFail()
    {
        /** @var GameapAbstractAdapter|Mockery\MockInterface $mock */
        $mock = Mockery::mock(GameapAbstractAdapter::class)->makePartial();

        $mock->shouldReceive('isConnected')->atLeast()->andReturn(false);
        $mock->shouldReceive('disconnect')->atLeast()->andReturnNull();
        $mock->shouldReceive('connect')->atLeast()->andReturnNull();

        $this->assertEquals(null, $mock->getConnection());
    }

    /**
     * @dataProvider adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testDisconnect($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('disconnect');
        $this->assertNull($adapter->disconnect());
    }

    /**
     * @dataProvider adapterProvider
     * 
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testConnect($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('__construct')->andReturn(null);

        $result = $adapter->connect();
        $this->assertNull($result);
    }
    
}

class GameapTestAdapter extends GameapAbstractAdapter
{
    /**
     * @var GdaemonFiles
     */
    public $connection;

    /**
     * @var string
     */
    public $host;

    /**
     * @var int
     */
    public $port = 31717;

    /**
     * @var int
     */
    public $timeout = 10;

    /**
     * @var SafeStorage
     */
    public $safeStorage;

    /**
     * @var string
     */
    public $privateKey;

    /**
     * @var string
     */
    public $serverCertificate;

    /**
     * @var string
     */
    public $localCertificate;

    /**
     * @var int
     */
    public $permPublic = 0744;

    /**
     * @var int
     */
    public $permPrivate = 0700;

    /**
     * @var array
     */
    public $configurable = [
        'host',
        'port',
        'username',
        'password',
        'serverCertificate',
        'localCertificate',
        'privateKey',
        'privateKeyPass',
        'timeout',
        'root',
    ];

    /**
     * @inheritDoc
     */
    public function write($path, $contents, Config $config){}

    /**
     * @inheritDoc
     */
    public function writeStream($path, $resource, Config $config){}

    /**
     * @inheritDoc
     */
    public function update($path, $contents, Config $config){}

    /**
     * @inheritDoc
     */
    public function updateStream($path, $resource, Config $config){}

    /**
     * @inheritDoc
     */
    public function rename($path, $newpath){}

    /**
     * @inheritDoc
     */
    public function copy($path, $newpath){}

    /**
     * @inheritDoc
     */
    public function delete($path){}

    /**
     * @inheritDoc
     */
    public function deleteDir($dirname){}

    /**
     * @inheritDoc
     */
    public function createDir($dirname, Config $config){}

    /**
     * @inheritDoc
     */
    public function setVisibility($path, $visibility){}

    /**
     * @inheritDoc
     */
    public function isConnected(){}

    /**
     * @inheritDoc
     */
    public function has($path){}

    /**
     * @inheritDoc
     */
    public function read($path){}

    /**
     * @inheritDoc
     */
    public function readStream($path){}

    /**
     * @inheritDoc
     */
    public function listContents($directory = '', $recursive = false){}

    /**
     * @inheritDoc
     */
    public function getMetadata($path){}

    /**
     * @inheritDoc
     */
    public function getSize($path){}

    /**
     * @inheritDoc
     */
    public function getMimetype($path){}

    /**
     * @inheritDoc
     */
    public function getTimestamp($path){}

    /**
     * @inheritDoc
     */
    public function getVisibility($path){}
}