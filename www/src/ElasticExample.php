<?php

namespace App;

use GuzzleHttp\Client;
use Exception;

class ElasticExample
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'http://elasticsearch:9200/',
            'timeout'  => 10.0,
        ]);
    }

    public function checkConnection()
    {
        try {
            $response = $this->client->get('', ['timeout' => 10]);
            $data = json_decode($response->getBody()->getContents(), true);
            return [
                'status' => 'connected',
                'version' => $data['version']['number'] ?? 'unknown'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function initializeData()
    {
        $results = [];
        
        try {
            try {
                $this->client->delete('products');
                $results[] = "Старый индекс удален";
                sleep(1);
            } catch (Exception $e) {
                $results[] = "Создаем новый индекс";
            }

            $indexConfig = [
                'mappings' => [
                    'properties' => [
                        'name' => ['type' => 'text'],
                        'description' => ['type' => 'text'],
                        'price' => ['type' => 'float'],
                        'category' => ['type' => 'keyword'],
                        'in_stock' => ['type' => 'boolean']
                    ]
                ]
            ];

            $this->client->put('products', ['json' => $indexConfig]);
            $results[] = "Индекс создан";
            sleep(1);

            $products = [
                [
                    'name' => 'Холодильник Samsung No Frost', 
                    'description' => 'Двухкамерный холодильник с системой No Frost', 
                    'price' => 59999.99, 
                    'category' => 'appliances', 
                    'in_stock' => true
                ],
                [
                    'name' => 'Смартфон iPhone 15 Pro', 
                    'description' => 'Смартфон от Apple с улучшенной камерой', 
                    'price' => 99999.99, 
                    'category' => 'electronics', 
                    'in_stock' => true
                ],
                [
                    'name' => 'Ноутбук Dell XPS 13', 
                    'description' => 'Мощный ноутбук для работы и игр', 
                    'price' => 129999.99, 
                    'category' => 'electronics', 
                    'in_stock' => true
                ],
                [
                    'name' => 'Кофеварка автоматическая', 
                    'description' => 'Автоматическая кофеварка для эспрессо и капучино', 
                    'price' => 29999.99, 
                    'category' => 'appliances', 
                    'in_stock' => true
                ]
            ];

            foreach ($products as $index => $product) {
                $this->client->put("products/_doc/" . ($index + 1), ['json' => $product]);
                $results[] = "Добавлен: " . $product['name'];
            }

            sleep(2);
            $results[] = "Все товары добавлены!";

        } catch (Exception $e) {
            $results[] = "Ошибка: " . $e->getMessage();
        }

        return $results;
    }

    public function searchProductsByDescription($searchText)
    {
        try {
            $query = [
                'query' => [
                    'match' => [
                        'description' => $searchText
                    ]
                ]
            ];

            $response = $this->client->post('products/_search', ['json' => $query]);
            $result = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'took' => $result['took'] ?? 0,
                'total' => $result['hits']['total']['value'] ?? 0,
                'hits' => $result['hits']['hits'] ?? []
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'took' => 0,
                'total' => 0,
                'hits' => []
            ];
        }
    }

    public function getIndexStats()
    {
        try {
            $response = $this->client->get('products/_stats');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'docs_count' => $data['indices']['products']['total']['docs']['count'] ?? 0,
                'size' => $data['indices']['products']['total']['store']['size_in_bytes'] ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'docs_count' => 0,
                'size' => 0
            ];
        }
    }
}