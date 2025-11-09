<?php

namespace App;

use GuzzleHttp\Client;

class RedisExample
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'http://redis:6379/',
            'timeout'  => 5.0,
        ]);
    }

    public function checkConnection()
    {
        try {
            $redis = new \Redis();
            $connected = $redis->connect('redis', 6379, 2.0);
            
            if ($connected) {
                return [
                    'status' => 'connected',
                    'message' => 'Redis connected successfully'
                ];
            }
            
            return [
                'status' => 'error', 
                'message' => 'Could not connect to Redis'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function demoOperations()
    {
        return [
            "SET username 'John Doe'",
            "GET username => 'John Doe'", 
            "SET product:view:123 160",
            "INCR product:view:123 => 161"
        ];
    }

    public function getUseCases()
    {
        return [
            'Кэширование данных',
            'Хранение сессий', 
            'Счетчики и метрики',
            'Очереди сообщений'
        ];
    }
}