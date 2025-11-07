<?php

namespace App\Helpers;

use GuzzleHttp\Client;

class ClientFactory
{
    public static function make(string $baseUri): Client
    {
        return new Client([
            'base_uri' => $baseUri,
            'timeout'  => 10.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }
}