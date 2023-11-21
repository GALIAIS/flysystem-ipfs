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

            // Get the file's MIME type from IPFS
            return $this->client()->stat($cid);
        } catch (Exception $e) {
            // Handle the exception
            return 'Unknown';
        }
    }

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
            // 获取文件或目录的 hash 值
            $hash = $this->client()->add($path);
            $cid = $hash['Hash'];
            // 列出文件或目录的内容
            $result = $this->client()->ls($cid);
            foreach ($result as $files) {
                // 将文件或目录的信息添加到列表中
                $list[] = $this->normalizeFileInfo($files, $path);
                // 如果是目录并且需要递归地列出内容，获取子目录的 hash 值并重复上述操作
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
        // 获取文件或目录的路径
        $filePath = ltrim($directory . '/' . $stats['name'], '/');
        // 获取文件或目录的 hash 值
        $hash = $stats['hash'];
        // 获取文件或目录的大小
        $size = $stats['size'];
        // 获取文件或目录的类型
        $type = $stats['type'];
        // 获取文件或目录的时间戳
        $timestamp = $stats['mtime']['secs'];
        // 获取文件或目录的 MIME 类型
        $mimetype = $this->getMimetype($filePath);

        // 创建一个 FileAttributes 对象
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
            $hash = $this->client()->add($path); // upload file to IPFS and get hash
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
            // 获取文件内容
            $contents = stream_get_contents($contents);

            // 上传文件到 IPFS 并获取响应
            $response = $this->client()->add($contents);

            // 检查响应中是否存在 Hash 键
            if (isset($response['Hash'])) {
                // 获取 CID
                $this->fileCid = $response['Hash'];
                // 使用 CID 作为路径
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

    public function fileSize(string $path): FileAttributes
    {
        try {
            // 获取文件或目录的 hash 值
            $hash = $this->client()->add($path);
            $cid = $hash['Hash'];
            // 获取文件或目录的元数据
            $result = $this->client()->stat($cid);
            // 获取文件或目录的大小
            return $result['size'];
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
        try {
            $result = $this->client()->addFromPath($path);
            // 获取 hash 值
            $hash = $result['Hash'];
            // 将 hash 值固定到 ipfs 节点上
            $this->client()->pinAdd($hash);
        } catch (Exception $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage());
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            // 获取文件或目录的 hash 值
            $hash = $this->client()->add($path);
            $cid = $hash['Hash'];
            // 获取文件或目录的元数据
            $result = $this->client()->stat($cid);
            // 获取文件或目录的最后修改时间
            $lastModified = $result['mtime']['secs'];
            // 创建一个 FileAttributes 对象
            return new FileAttributes($path, lastModified: $lastModified);
        } catch (Exception $e) {
            throw UnableToRetrieveMetadata::lastModified($path);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $sourceHash = $source; // use hash as source path
            $destinationHash = $destination; // use hash as destination path
            $this->client()->pinAdd($sourceHash); // pin source file to IPFS network
            // save source file to destination path in your local or cloud storage
        } catch (Exception $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination);
        }
    }

    public function delete(string $path): void
    {
        try {
            $hash = $path; // use hash as path
            $this->client()->pinRm($hash); // unpin file from IPFS network
            // delete file from your local or cloud storage
        } catch (Exception $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage());
        }
    }

    public function deleteDirectory(string $path): void
    {
        $this->delete($path);
    }

    public function cid_file($content): string
    {
        // 创建一个Base58对象，使用GMP字符集和Base32编码
        $base58 = new Base58([
            "characters" => Base58::GMP,
            "encoding" => Base32::RFC4648
        ]);

        // 使用BLAKE2b-256哈希算法计算文件内容的哈希值
        try {
            $hash = sodium_crypto_generichash($content, '', 32);
        } catch (SodiumException $e) {
        } // 32表示哈希值长度为32字节

        // 将哈希值转换为CID格式
        $cidVersion = 1; // CID版本为1
        $multibasePrefix = 'b'; // Base32编码前缀为b
        $multicodecPrefix = '70'; // 哈希值前缀为70（32个字节的哈希值长度）

        // 使用Base58对象的encode()方法对哈希值进行编码
        return $multibasePrefix . $base58->encode(hex2bin($multicodecPrefix . bin2hex($hash)));
    }

}