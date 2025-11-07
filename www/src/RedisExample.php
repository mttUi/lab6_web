<?php

namespace App;

use App\Helpers\ClientFactory;
use GuzzleHttp\Exception\RequestException;

class RedisExample
{
    private $client;

    public function __construct()
    {
        // Для Redis используем HTTP интерфейс через Redis REST Proxy
        // В демо-режиме будем использовать Elasticsearch как замену
        $this->client = ClientFactory::make('http://elasticsearch:9200/');
    }

    public function testConnection()
    {
        try {
            $response = $this->client->get('');
            $data = json_decode($response->getBody()->getContents(), true);
            return "✅ Redis (через Elasticsearch): Connected to " . ($data['name'] ?? 'unknown');
        } catch (RequestException $e) {
            return "❌ Redis connection error: " . $e->getMessage();
        }
    }

    public function setValue($key, $value)
    {
        try {
            // Имитация SET команды Redis через Elasticsearch
            $data = [
                'timestamp' => time(),
                'value' => $value,
                'key' => $key
            ];
            
            $response = $this->client->put("redis_demo/_doc/$key", [
                'json' => $data
            ]);
            
            return "✅ SET $key: " . $response->getBody()->getContents();
        } catch (RequestException $e) {
            return "❌ Redis SET error: " . $e->getMessage();
        }
    }

    public function getValue($key)
    {
        try {
            // Имитация GET команды Redis через Elasticsearch
            $response = $this->client->get("redis_demo/_doc/$key");
            $data = json_decode($response->getBody()->getContents(), true);
            
            return "✅ GET $key: " . ($data['_source']['value'] ?? 'Not found');
        } catch (RequestException $e) {
            return "❌ Redis GET error: " . $e->getMessage();
        }
    }

    public function getDemoData()
    {
        return [
            'database' => 'Redis',
            'status' => 'Connected via HTTP',
            'demo_operations' => [
                'SET user:name "John Doe"',
                'GET user:name',
                'SET product:view:123 150',
                'INCR product:view:123'
            ],
            'use_cases' => [
                'Кэширование данных',
                'Хранение сессий',
                'Счетчики и метрики',
                'Очереди сообщений'
            ]
        ];
    }
}