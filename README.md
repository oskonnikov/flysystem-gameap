# GameAP Daemon Flysystem

GameAP Daemon Files Adapter 

## Installation

```bash
composer require knik/flysystem-gameap
```

## Usage

```php
use Knik\Flysystem\Gameap\GameapAdapter;
use League\Flysystem\Filesystem;

$adapter = new GameapAdapter([
    'host' => 'localhost',
    'port' => 31717,
    'username' => 'username',
    'password' => 'password',
    'privateKey' => '/path/to/private_key',
    'privateKeyPass' => 'pr1vateKeyPa$$',
    'root' => '/home/gameap',
    'timeout' => 10,
]);

$filesystem = new Filesystem($adapter);
```