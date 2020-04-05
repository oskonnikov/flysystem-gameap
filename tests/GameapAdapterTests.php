<?php

use PHPUnit\Framework\TestCase;
use Knik\Flysystem\Gameap\GameapAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Config;

/**
 * @covers Knik\Flysystem\Gameap\GameapAdapter<extended>
 */
class GameapAdapterTests extends TestCase
{
    public function adapterProvider()
    {
        $adapter = new GameapAdapter([
            'host' => 'localhost',
            'port' => 31717,
            'username' => 'test',
            'password' => 'test',
            'privateKey' => '/private_key',
            'privateKeyPass' => '/private_key',
            'root' => __DIR__,
            'timeout' => 10,
        ]);

        $mock = Mockery::mock('Knik\Gameap\GdaemonFiles')->makePartial();

        $mock->shouldReceive('__construct');
        $mock->shouldReceive('__toString')->andReturn('GdaemonFiles');
        $mock->shouldReceive('isConnected')->andReturn(true);
        $mock->shouldReceive('connect');
        $mock->shouldReceive('disconnect');

        $adapter->setConnection($mock);

        $filesystem = new Filesystem($adapter);

        return [
            [$filesystem, $adapter, $mock],
        ];
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testHas($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $this->assertTrue($filesystem->has('file.txt'));
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testHasFail($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(false);
        $this->assertFalse($filesystem->has('invalid_file.txt'));
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testWrite($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(false);
        $mock->shouldReceive('put')->andReturn(true, false);

        $this->assertTrue($filesystem->write('file.txt', 'CONTENTS'));
        $this->assertFalse($filesystem->write('invalid_file.txt', 'CONTENTS'));
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testWriteStream($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(false);
        $mock->shouldReceive('put')->andReturn(true, false);

        $stream = tmpfile();
        $this->assertTrue($filesystem->writeStream('file.txt', $stream, ['visibility' => 'public']));
        $this->assertFalse($filesystem->writeStream('invalid_file.txt', $stream));
        fclose($stream);
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testUpdate(FilesystemInterface $filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('put')->andReturn(true, false);

        $this->assertTrue($filesystem->update('file.txt', 'CONTENTS'));
        $this->assertFalse($filesystem->update('invalid_file.txt', 'CONTENTS'));
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testUpdateStream(FilesystemInterface $filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('put')->andReturn(true, false);

        $stream = tmpfile();
        $this->assertTrue($filesystem->updateStream('file.txt', $stream));
        $this->assertFalse($filesystem->updateStream('invalid_file.txt', $stream));
        fclose($stream);
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     *
     * @expectedException InvalidArgumentException
     */
    public function testUploadInvalidArgument($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('put')->andReturn(true, false);

        $adapter->upload('file.txt', 0, new Config);
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testDelete($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('delete')->andReturn(true);

        $this->assertTrue($filesystem->delete('file.txt'));
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     *
     * @expectedException RuntimeException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testDeleteFail($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('delete')->andThrow(RuntimeException::class);

        $filesystem->delete('file.txt');
    }

    /**
     * @dataProvider adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testSetVisibility($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('chmod')->twice()->andReturn(['path' => 'file.txt'], false);

        $this->assertTrue($filesystem->setVisibility('file.txt', 'public'));
        $this->assertFalse($filesystem->setVisibility('file.txt', 'public'));
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     *
     * @expectedException InvalidArgumentException
     */
    public function testSetVisibilityInvalid($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $filesystem->setVisibility('file.txt', 'invalid');
    }

    /**
     * @dataProvider adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testRename($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true, false);
        $mock->shouldReceive('rename')->andReturn(true);

        $result = $filesystem->rename('old', 'new');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider adapterProvider
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testDeleteDir($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('delete')->andReturn(true);
        $result = $filesystem->deleteDir('some/dirname');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testListContents($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('directoryContents')->andReturn([
            [
                'name' => 'directory',
                'size' => 0,
                'mtime' => 1542013640,
                'type' => 'dir',
                'permissions' => 0755,
            ],
            [
                'name' => 'file.txt',
                'size' => 15654,
                'mtime' => 1542013150,
                'type' => 'file',
                'permissions' => 0644
            ]
        ]);

        $listing = $adapter->listContents('');
        $this->assertInternalType('array', $listing);
        $this->assertCount(2, $listing);
    }

    /**
     * @dataProvider adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testListContentsRecursive($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('directoryContents')->andReturn([
            [
                'name' => 'directory',
                'size' => 0,
                'mtime' => 1542013640,
                'type' => 'dir',
                'permissions' => 0755,
            ],
            [
                'name' => 'file.txt',
                'size' => 15654,
                'mtime' => 1542013150,
                'type' => 'file',
                'permissions' => 0644
            ]
        ], [
            [
                'name' => 'recursive_directory',
                'size' => 0,
                'mtime' => 1542016666,
                'type' => 'dir',
                'permissions' => 0755,
            ],
        ]);

        $listing = $adapter->listContents('', true);
        $this->assertInternalType('array', $listing);
        $this->assertCount(3, $listing);
    }

    /**
     * @dataProvider  adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testGetVisibility($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('metadata')->andReturn([
            'name' => 'file.txt',
            'size' => 20,
            'mtime' => time(),
            'type' => 'file',
            'permissions' => 0644,
            'mimetype' => 'text/plain'
        ]);

        $result = $adapter->getVisibility('file.txt');
        $this->assertInternalType('array', $result);
        $result = $result['visibility'];
        $this->assertInternalType('string', $result);
        $this->assertEquals('public', $result);
    }

    /**
     * @dataProvider  adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testGetVisibilityPrivate($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('metadata')->andReturn([
            'name' => 'file.txt',
            'size' => 20,
            'mtime' => time(),
            'type' => 'file',
            'permissions' => 0600,
            'mimetype' => 'text/plain'
        ]);

        $result = $adapter->getVisibility('file.txt');
        $this->assertInternalType('array', $result);
        $result = $result['visibility'];
        $this->assertInternalType('string', $result);
        $this->assertEquals('private', $result);
    }

    /**
     * @dataProvider  adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testGetTimestamp($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('metadata')->andReturn([
            'name' => 'file.txt',
            'size' => 20,
            'mtime' => $time = time(),
            'type' => 'file',
            'permissions' => 0600,
            'mimetype' => 'text/plain'
        ]);

        $result = $adapter->getTimestamp('object.ext');
        $this->assertInternalType('array', $result);
        $result = $result['timestamp'];
        $this->assertInternalType('integer', $result);
        $this->assertEquals($time, $result);
    }

    /**
     * @dataProvider  adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testGetSize($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('metadata')->andReturn([
            'name' => 'file.txt',
            'size' => 20,
            'mtime' => $time = time(),
            'type' => 'file',
            'permissions' => 0600,
            'mimetype' => 'text/plain'
        ]);

        $result = $adapter->getSize('file.txt');
        $this->assertInternalType('array', $result);
        $result = $result['size'];
        $this->assertInternalType('integer', $result);
        $this->assertEquals(20, $result);
    }

    /**
     * @dataProvider  adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testRead($filesystem, $adapter, $mock)
    {
        $fp = fopen('php://temp', 'w+');
        fwrite($fp, 'CONTENTS');
        rewind($fp);

        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('get')->andReturn($fp);

        $result = $filesystem->read('file.txt');
        $this->assertEquals('CONTENTS', $result);
    }

    /**
     * @dataProvider  adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testGetMimetype($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true);
        $mock->shouldReceive('metadata')->andReturn(
            [
                'name' => 'file.txt',
                'size' => 20,
                'mtime' => $time = time(),
                'type' => 'file',
                'permissions' => 0600,
                'mimetype' => 'text/plain'
            ],
            [
                'name' => 'file.txt',
                'size' => 20,
                'mtime' => $time = time(),
                'type' => 'file',
                'permissions' => 0600,
                'mimetype' => ''
            ]
        );

        $fp = fopen('php://temp', 'w+');
        fwrite($fp, 'CONTENTS');
        rewind($fp);
        $mock->shouldReceive('get')->andReturn($fp);

        $result = $filesystem->getMimetype('file.txt');
        $this->assertInternalType('string', $result);
        $this->assertEquals('text/plain', $result);

        $this->assertEquals('text/plain', $filesystem->getMimetype('file.txt'));
    }

    /**
     * @dataProvider  adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testCreateDir($filesystem, $adapter, $mock)
    {
        $directoryPerm = 0644;

        $mock->shouldReceive('mkdir')->andReturn(true, false);

        $this->assertTrue($filesystem->createDir('dirname'));
        // $this->assertFalse($filesystem->createDir('dirname_fails'));
    }

    /**
     * @dataProvider  adapterProvider
     *
     * @param League\Flysystem\FilesystemInterface $filesystem
     * @param Knik\Flysystem\Gameap\GameapAdapter $adapter
     * @param Mockery\MockInterface $mock
     */
    public function testCopy($filesystem, $adapter, $mock)
    {
        $mock->shouldReceive('exist')->andReturn(true, false, true, false);
        $mock->shouldReceive('copy')->andReturn(true, false);

        $this->assertTrue($filesystem->copy('file.txt', 'file2.txt'));
        $this->assertFalse($filesystem->copy('file.txt', 'file2.txt'));
    }
}