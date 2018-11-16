<?php

namespace Knik\Flysystem\Gameap;

use League\Flysystem\Config;
use League\Flysystem\Util;
use InvalidArgumentException;
use League\Flysystem\AdapterInterface;

class GameapAdapter extends GameapAbstractAdapter
{
    /**
     * @var array
     */
    protected $configurable = [
        'host',
        'port',
        'username',
        'password',
        'privateKey',
        'privateKeyPass',
        'timeout',
        'root',
    ];

    /**
     * @return bool
     * @throws \Exception
     */
    public function isConnected()
    {
        return !empty($this->connection);
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, Config $config)
    {
        if (! $this->upload($path, $contents, $config)) {
            return false;
        }

        return compact('contents', 'path');
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        if (! $this->upload($path, $resource, $config)) {
            return false;
        }

        return compact( 'path');
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource, $config);
    }

    /**
     * Upload a file.
     *
     * @param string          $path
     * @param string|resource $contents
     * @param Config          $config
     * @return bool
     */
    public function upload($path, $contents, Config $config)
    {
        $path = $this->applyPathPrefix($path);

        $config = Util::ensureConfig($config);

        $visibility = $config->get('visibility') ?: 'public';
        $permissions = $this->{'perm' . ucfirst($visibility)};

        if (is_resource($contents)) {
            $result = $this->getConnection()->put($contents, $path, $permissions);
        } else if (is_string($contents)) {
            $fp = fopen('php://temp', 'w+b');
            fwrite($fp, $contents);
            rewind($fp);
            $result = $this->getConnection()->put($fp, $path, $permissions);
        } else {
            throw new InvalidArgumentException('Invalid contents');
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newpath)
    {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        $this->getConnection()->rename($path, $newpath);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function copy($path, $newpath)
    {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        return $this->getConnection()->copy($path, $newpath);
    }

    /**
     * @inheritdoc
     */
    public function delete($path)
    {
        $path = $this->applyPathPrefix($path);

        return $this->getConnection()->delete($path, true);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        return $this->delete($dirname);
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, Config $config)
    {
        $dirname = $this->applyPathPrefix($dirname);

        $config = Util::ensureConfig($config);

        $visibility = $config->get('visibility') ?: 'public';
        $permissions = $this->{'perm'.ucfirst($visibility)};

        $this->getConnection()->mkdir($dirname, $permissions);

        return ['path' => $dirname];
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        $location = $this->applyPathPrefix($path);

        $visibility = ucfirst($visibility);

        if (! isset($this->{'perm'.$visibility})) {
            throw new InvalidArgumentException('Unknown visibility: '.$visibility);
        }

        $permissions = $this->{'perm'.$visibility};

        if ($this->getConnection()->chmod($permissions, $location)) {
            return compact('path', 'visibility');
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        $fullPath = $this->applyPathPrefix($path);
        return $this->getConnection()->exist($fullPath);
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        $object = $this->readStream($path);
        $object['contents'] = stream_get_contents($object['stream']);
        fclose($object['stream']);
        unset($object['stream']);

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        $location = $this->applyPathPrefix($path);
        $fileHandle = fopen('php://temp', 'w+b');
        $stream = $this->getConnection()->get($location, $fileHandle);

        return compact('stream', 'path');
    }


    /**
     * @inheritdoc
     */
    public function listContents($directory = '', $recursive = false)
    {
        $fullPath = $this->applyPathPrefix($directory);
        $listing = $this->getConnection()->directoryContents($fullPath);

        $result = [];
        foreach ($listing as $file) {
            $path = empty($directory) ? $file['name'] : $directory . $this->pathSeparator . ltrim($file['name'], $this->pathSeparator);
            $result[] = $this->normalizeListingObject($path, $file);

            if ($recursive && isset($object['type']) && $file['type'] === 'dir') {
                $result = array_merge($result, $this->listContents($path));
            }
        }

        return $result;
    }


    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        $fullPath = $this->applyPathPrefix($path);

        $meta = $this->getConnection()->metadata($fullPath);

        return [
            'path' => $path,
            'size' => $meta['size'],
            'type' => $meta['type'],
            'timestamp' => $meta['mtime'],
            'visibility' => $meta['permissions'] & (0777 ^ $this->permPrivate) ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE,
            'mimetype' => $meta['mimetype'],
        ];
    }


    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }


    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        $fullPath = $this->applyPathPrefix($path);
        $meta = $this->getConnection()->metadata($fullPath);
        $mimetype = $meta['mimetype'];

        if (empty($mimetype)) {
            $data = $this->read($path);
            $mimetype = Util::guessMimeType($path, $data['contents']);
        }

        return compact('mimetype', 'path');
    }


    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }


    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }
}