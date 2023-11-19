<?php

namespace GALIAIS\Flysystem\IPFS;

use League\Flysystem\Config;
use JetBrains\PhpStorm\Pure;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Cloutier\PhpIpfsApi\IPFS;
use GuzzleHttp\Client;

class IPFSAdapter implements FilesystemAdapter
{
    protected string $client;

    public function __construct(
        protected string $gateway,
    )
    {
    }

    public function move($source, $destination, $config): void
    {
    }

    public function visibility($path)
    {
    }

    public function directoryExists($path)
    {
    }

    public function fileExists(string $path): bool
    {
    }

    public function has($path)
    {
    }

    public function mimeType(string $path): FileAttributes
    {
    }

    public function setVisibility(string $path, string $visibility): void
    {
    }

    public function listContents(string $path, bool $deep): iterable
    {
    }

    public function write(string $path, string $contents, Config $config)
    {

    }

    public function writeStream(string $path, $contents, $config): void
    {
    }

    public function fileSize($path): FileAttributes
    {
    }

    public function read($path): string
    {
        $response = $this->client->get('cat/' . $path);

        return (string)$response->getBody();
    }

    public function readStream($path)
    {
    }

    public function createDirectory($path, $config): void
    {
    }

    public function lastModified(string $path): FileAttributes
    {
    }

    public function copy($source, $destination, $config): void
    {
    }

    public function delete($path): void
    {
    }

    public function deleteDirectory($path): void
    {

    }
}