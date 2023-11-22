<?php

namespace GALIAIS\Flysystem\IPFS;

use Exception;
use Generator;
use SodiumException;
use Tuupola\Base58;
use Tuupola\Base32;
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
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;

class IPFSAdapter implements FilesystemAdapter
{
    protected ?IPFS $client = null;
    public string $cid;
    public string $fileCid;

    public function __construct(
        protected string $gatewayHost,
        protected string $apiHost,
    ){
    }

    public function setClient(IPFS $client): void
    {
        $this->client = $client;
    }

    protected function client(): IPFS
    {
        if (is_null($this->client)) {
            $config = new IPFS($this->gatewayHost, $this->apiHost);
            $this->client = new IPFS($config);
        }
        return $this->client;
    }


    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $sourceHash = $source;
            $destinationHash = $destination;
            $this->client()->pinRm($sourceHash);
            $this->client()->pinAdd($destinationHash);
        } catch (Exception $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path);
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

    public function mimeType(string $path): FileAttributes
    {
        $mimetype = $this->getMimetype($path);

        if (! $mimetype) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return new FileAttributes($path, mimeType: $mimetype);
    }

    public function getMimetype(string $path): string
    {
        try {
            // Add the file to IPFS and get its CID
            $result = $this->client()->add($path);
            $cid = $result['Hash'];

            return $this->client()->stat($cid);
        } catch (Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * @throws Exception
     */
    public function getTimestamp(string $path): string
    {
        try {
            // 获取文件或目录的 hash 值
            $hash = $this->client()->add($path);
            $cid = $hash['Hash'];
            // 获取文件或目录的元数据
            $result = $this->client()->stat($cid);

            // 获取文件或目录的时间戳
            return $result['mtime']['secs'];
        } catch (Exception $e) {
            // 记录异常信息
            $errorMessage = $e->getMessage();
            $stackTrace = $e->getTraceAsString();

            // 在日志中记录异常
            error_log("Exception occurred in getTimestamp: $errorMessage\n$stackTrace");

            // 重新抛出异常
            throw $e;
        }
    }



    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $list = [];

        try {
            $hash = $this->client()->add($path);
            $cid = $hash['Hash'];
            $result = $this->client()->ls($cid);
            foreach ($result as $files) {
                $list[] = $this->normalizeFileInfo($files, $path);
                if ($files['type'] == 'directory' && $deep) {
                    $subhash = $files['hash'];
                    $list = array_merge($list, (array)$this->listContents($subhash, true));
                }
            }
        } catch (Exception $e) {
            return [];
        }

        return $list;
    }

    #[Pure]
    protected function normalizeFileInfo(array $stats, string $directory): FileAttributes
    {
        $filePath = ltrim($directory . '/' . $stats['name'], '/');
        $hash = $stats['hash'];
        $size = $stats['size'];
        $type = $stats['type'];
        $timestamp = $stats['mtime']['secs'];
        $mimetype = $this->getMimetype($filePath);

        return new FileAttributes(
            $filePath,
            $size,
            $type,
            $timestamp,
            $mimetype,
            $hash
        );
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $hash = $this->client()->add($path);
            $path = $hash;
            return;
        } catch (Exception $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $contents = stream_get_contents($contents);

            $response = $this->client()->add($contents);

            if (isset($response['Hash'])) {
                $this->fileCid = $response['Hash'];
                $fileCid = $this->fileCid;
                $path = $fileCid;
            } else {
                throw new Exception('Unable to retrieve CID from IPFS response.');
            }
        } catch (Exception $e) {
            throw new UnableToWriteFile($path, $e->getMessage());
        }
    }

    public function getCid(): ?string
    {
        return $this->cid;
    }

    /**
     * @throws Exception
     */
    public function fileSize(string $path): FileAttributes
    {
        try {
            $hash = $this->client()->add($path);
            $cid = $hash['Hash'];
            $result = $this->client()->stat($cid);
            return $result['size'];
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $stackTrace = $e->getTraceAsString();

            error_log("Exception occurred in getTimestamp: $errorMessage\n$stackTrace");

            throw $e;
        }
    }

    public function read(string $path): string
    {
        try {
            $hash = $path;
            return $this->client()->cat($hash);
        } catch (Exception $e) {
            throw UnableToReadFile::fromLocation($path);
        }
    }

    public function readStream(string $path)
    {
        try {
            $hash = $path;
            $stream = tmpfile();
            fwrite($stream, $this->client()->cat($hash));
            rewind($stream);
            return $stream;
        } catch (Exception $e) {
            throw UnableToReadFile::fromLocation($path);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $result = $this->client()->addFromPath($path);
            $hash = $result['Hash'];
            $this->client()->pinAdd($hash);
        } catch (Exception $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage());
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $hash = $this->client()->add($path);
            $cid = $hash['Hash'];
            $result = $this->client()->stat($cid);
            $lastModified = $result['mtime']['secs'];
            return new FileAttributes($path, lastModified: $lastModified);
        } catch (Exception $e) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $sourceHash = $source;
            $destinationHash = $destination;
            $this->client()->pinAdd($sourceHash);
        } catch (Exception $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination);
        }
    }

    public function delete(string $path): void
    {
        try {
            $hash = $path;
            $this->client()->pinRm($hash);
        } catch (Exception $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage());
        }
    }

    public function deleteDirectory(string $path): void
    {
        $this->delete($path);
    }

    public function cid_file($filePath): string
    {
        $base58 = new Base58([
            "characters" => Base58::GMP,
            "encoding" => Base32::RFC4648
        ]);

        $content = file_get_contents($filePath);

        try {
            $hash = sodium_crypto_generichash($content, '', 32);
        } catch (SodiumException $e) {
        }

        $cidVersion = 1;
        $multibasePrefix = 'b';
        $multicodecPrefix = '70';

        return $multibasePrefix . $base58->encode(hex2bin($multicodecPrefix . bin2hex($hash)));
    }


}