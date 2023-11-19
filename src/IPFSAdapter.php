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
use chenjia404\PhpIpfsApi\IPFS;
use GuzzleHttp\Client;

class IPFSAdapter implements FilesystemAdapter
{
    protected ?IPFS $client = null;

    public function __construct(
        protected string $gatewayHost,
        protected string $ApiHost,
    ){
    }

    public function setClient(IPFS $client): void
    {
        $this->client = $client;
    }

    /**
     * @throws Exception
     */
    protected function client(): IPFS
    {
        if (is_null($this->client)) {
            $config = new IPFS($this->gatewayHost, $this->ApiHost);
            $this->client = new IPFS($config);
        }

        return $this->client;
    }


    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $sourceHash = $source; // use hash as source path
            $destinationHash = $destination; // use hash as destination path
            $this->client()->pinRm($sourceHash); // unpin source file from IPFS network
            // delete source file from your local or cloud storage
            $this->client()->pinAdd($destinationHash); // pin destination file to IPFS network
            // save destination file to your local or cloud storage
        } catch (Exception $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }
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
            $hash = $this->client()->add($contents); // upload file to IPFS and get hash
            $path = $hash; // use hash as path
            // save path and other metadata to your local or cloud storage
            return;
        } catch (Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
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
        try {
            $hash = $path; // 使用hash值作为路径
            return $this->client()->cat($hash); // get file content from IPFS by hash
        } catch (Exception $e) {
            throw UnableToReadFile::fromLocation($path);
        }
    }

    public function readStream(string $path)
    {
        try {
            $hash = $path; // use hash as path
            $stream = tmpfile();
            fwrite($stream, $this->client()->cat($hash)); // get file content from IPFS by hash
            rewind($stream);
            return $stream;
        } catch (Exception $e) {
            throw UnableToReadFile::fromLocation($path);
        }
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