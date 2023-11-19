<?php

/*
 * This file is part of the overtrue/flysystem-qiniu.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace GALIAIS\Flysystem\IPFS\Tests;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToSetVisibility;
use Cloutier\PhpIpfsApi\IPFS;
use GuzzleHttp\Client;
use Mockery;
use GALIAIS\Flysystem\IPFS\IPFSAdapter;
use PHPUnit\Framework\TestCase;


/**
 * Class IPFSAdapterTest.
 */
class IPFSAdapterTest extends TestCase
{
    public function IPFSProvider()
    {
        $adapter = Mockery::mock(IPFSAdapter::class, ['serviceName', 'operatorName', 'password', 'domain'])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        return [
            [$adapter],
        ];
    }

}
