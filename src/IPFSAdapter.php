<?php

namespace GALIAIS\Flysystem\IPFS;

use Exception;
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
    protected ?IPFS $client = null;
    private $ipfsClient;

    public function __construct(
        protected string $gateway,
        protected string $http_api,
    ){
    }

    public function setClient(IPFS $client): void
    {
        $this->client = $client;
    }

    /**
     * @throws Exception
     */
    public function client(): IPFS
    {
        if ($this->client === null) {
            throw new Exception("IPFS client is not set.");
        }

        return $this->client;
    }


    public function move(string $source, string $destination, Config $config): void
    {
    }

    public function visibility(string $path): FileAttributes
    {
    }

    public function directoryExists(string $path): bool
    {
        return $this->fileExists($path);
    }

    public function fileExists(string $path): bool
    {
        try {
            $ipfsClient = $this->client();
            $ipfsClient->cat($path);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function has(string $path)
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

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client()->add($contents);
        } catch (Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $ipfsClient = $this->client();
        } catch (Exception $e) {
        }
        $tempFile = tempnam(sys_get_temp_dir(), 'ipfs');

        // 将流式数据写入临时文件
        $stream = fopen($tempFile, 'w');
        while (!feof($contents)) {
            fwrite($stream, fread($contents, 1024));
        }
        fclose($stream);

        try {
            // 使用 IPFS 客户端将临时文件添加到 IPFS
            $result = $this->client()->add($tempFile);
            $ipfsHash = $result['Hash'];

            // 删除临时文件
            unlink($tempFile);

            // 将 IPFS 哈希写入指定路径
            $this->write($path, $ipfsHash, $config);
        } catch (Exception $e) {
            // 删除临时文件（如果添加到 IPFS 失败）
            unlink($tempFile);

            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    public function fileSize(string $path): FileAttributes
    {
    }

    public function read(string $path): string
    {
    }

    public function readStream(string $path)
    {
    }

    public function createDirectory(string $path, Config $config): void
    {
    }

    public function lastModified(string $path): FileAttributes
    {
    }

    public function copy(string $source, string $destination, Config $config): void
    {
    }

    public function delete(string $path): void
    {
    }

    public function deleteDirectory(string $path): void
    {

    }
}