## Flysystem Adapter for IPFS

:floppy_disk: Flysystem adapter for the IPFS.

# Requirement

-   PHP >= 8.0.2

# Installation

```shell
$ composer require "GALIAIS/flysystem-ipfs"
```

# Usage

```php
use League\Flysystem\Filesystem;
use GALIAIS\Flysystem\IPFS\IPFSAdapter1;

$gatewayHost = 'http://localhost:8080';
$ApiHost = 'http://localhost:5001';

$adapter = new IPFS($this->gatewayHost, $this->apiHost);

$flysystem = new League\Flysystem\Filesystem($adapter);
```

## API

```php
待补充
```

Adapter extended methods:

```php
待补充
```

# License

MIT
