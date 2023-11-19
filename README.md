## Flysystem Adapter for Qiniu Cloud Storage

:floppy_disk: Flysystem adapter for the Qiniu cloud storage.

# Requirement

-   PHP >= 8.0.2

# Installation

```shell
$ composer require "overtrue/flysystem-qiniu"
```

# Usage

```php
use League\Flysystem\Filesystem;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use Overtrue\Flysystem\Qiniu\Plugins\FetchFile;

$accessKey = 'xxxxxx';
$secretKey = 'xxxxxx';
$bucket = 'test-bucket-name';
$domain = 'xxxx.bkt.clouddn.com'; // or with protocol: https://xxxx.bkt.clouddn.com

$adapter = new QiniuAdapter($accessKey, $secretKey, $bucket, $domain);

$flysystem = new League\Flysystem\Filesystem($adapter);
```

## API

```php
bool $flysystem->write('file.md', 'contents');
bool $flysystem->write('file.md', 'http://httpbin.org/robots.txt', ['mime' => 'application/redirect302']);
bool $flysystem->writeStream('file.md', fopen('path/to/your/local/file.jpg', 'r'));
bool $flysystem->rename('foo.md', 'bar.md');
bool $flysystem->copy('foo.md', 'foo2.md');
bool $flysystem->delete('file.md');
bool $flysystem->has('file.md');
bool $flysystem->fileExists('file.md');
bool $flysystem->directoryExists('path/to/dir');
string|false $flysystem->read('file.md');
array $flysystem->listContents();
int $flysystem->fileSize('file.md');
string $flysystem->mimeType('file.md');
```

Adapter extended methods:

```php
string $adapter->getUrl('file.md');
bool|array $adapter->fetch(string $path, string $url);
array $adapter->refresh(string $path);
string $adapter->getTemporaryUrl($path, int|string|\DateTimeInterface $expiration);
string $adapter->privateDownloadUrl(string $path, int $expires = 3600);
string $adapter->getUploadToken(string $key = null, int $expires = 3600, string $policy = null, string $strictPolice = null)
```

# License

MIT
