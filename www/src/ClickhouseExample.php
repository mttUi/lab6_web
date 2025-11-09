<?php

namespace App;

use GuzzleHttp\Client;
use Exception;

class ClickhouseExample
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'http://clickhouse:8123/',
            'timeout'  => 5.0,
        ]);
    }

    public function checkConnection()
    {
        try {
            $response = $this->client->get('', ['timeout' => 5]);
            return [
                'status' => 'connected',
                'message' => 'ClickHouse connected successfully'
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
            $sql = "CREATE TABLE IF NOT EXISTS products_stats (id UInt32, name String, views UInt32, purchases UInt32, date Date) ENGINE = Memory";
            $this->client->post('', ['body' => $sql]);
            $results[] = "Таблица создана";

            $this->client->post('', ['body' => "TRUNCATE TABLE products_stats"]);
            $results[] = "Данные очищены";

            $insertSQL = "INSERT INTO products_stats VALUES 
                (1, 'Холодильник Samsung', 1500, 45, '2024-01-15'), 
                (2, 'iPhone 15', 3000, 120, '2024-01-15'), 
                (3, 'Ноутбук Dell', 800, 23, '2024-01-15'), 
                (4, 'Кофеварка', 1200, 67, '2024-01-15')";
            $this->client->post('', ['body' => $insertSQL]);
            $results[] = "Данные добавлены";

        } catch (Exception $e) {
            $results[] = "Ошибка: " . $e->getMessage();
        }

        return $results;
    }

    public function getProductsStats()
    {
        try {
            $sql = "SELECT name, views, purchases, round((purchases * 100.0) / views, 2) as conversion_rate FROM products_stats ORDER BY conversion_rate DESC";
            $response = $this->client->post('', ['body' => $sql]);
            
            $data = $response->getBody()->getContents();
            $lines = explode("\n", trim($data));
            
            $stats = [];
            foreach ($lines as $line) {
                if (!empty(trim($line))) {
                    $parts = explode("\t", $line);
                    if (count($parts) >= 4) {
                        $stats[] = [
                            'name' => $parts[0],
                            'views' => $parts[1],
                            'purchases' => $parts[2],
                            'conversion_rate' => $parts[3]
                        ];
                    }
                }
            }
            
            return [
                'success' => true,
                'data' => $stats,
                'rows' => count($stats)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
                'rows' => 0
            ];
        }
    }
}