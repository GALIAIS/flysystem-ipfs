<?php

namespace GALIAIS\Flysystem\IPFS;

use League\Flysystem\Config;
use League\Flysystem\Adapter\AbstractAdapter;
use Cloutier\PhpIpfsApi\IPFS;
use GuzzleHttp\Client;

class IPFSAdapter extends AbstractAdapter
{
    protected string $client;

    public function __construct(
        protected string $gateway,
    ){
    }

    public function write($path, $contents, Config $config)
    {
        $response = $this->client->post('add', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $contents,
                    'filename' => $path
                ]
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        return $body['Hash'];
    }

    public function read($path)
    {
        $response = $this->client->get('cat/' . $path);

        return (string) $response->getBody();
    }

    public function delete($path)
    {
        $response = $this->client->get('pin/rm/' . $path);

        return $response->getStatusCode() === 200;
    }

    public function has($path)
    {
        $response = $this->client->get('pin/ls');

        $body = json_decode($response->getBody(), true);

        foreach ($body['Keys'] as $key) {
            if ($key['Name'] === $path) {
                return true;
            }
        }

        return false;
    }
}