<?php

namespace App;

use App\Helpers\ClientFactory;
use GuzzleHttp\Exception\RequestException;

class ClickhouseExample
{
    private $client;

    public function __construct()
    {
        $this->client = ClientFactory::make('http://clickhouse:8123/');
    }

    public function testConnection()
    {
        try {
            $response = $this->client->post('', [
                'body' => 'SELECT version()',
                'headers' => [
                    'Content-Type' => 'text/plain'
                ]
            ]);
            
            $version = trim($response->getBody()->getContents());
            return "✅ ClickHouse: Version $version";
        } catch (RequestException $e) {
            return "❌ ClickHouse connection error: " . $e->getMessage();
        }
    }

    public function query($sql)
    {
        try {
            $response = $this->client->post('', [
                'body' => $sql,
                'headers' => [
                    'Content-Type' => 'text/plain'
                ]
            ]);

            return [
                'success' => true,
                'data' => $response->getBody()->getContents()
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => "❌ Query error: " . $e->getMessage()
            ];
        }
    }

    public function initializeDemoData()
    {
        // Create table for analytics
        $createTable = "
            CREATE TABLE IF NOT EXISTS product_analytics (
                product_id String,
                product_name String,
                category String,
                price Decimal(10,2),
                views Int32,
                purchases Int32,
                revenue Decimal(10,2),
                event_date Date
            ) ENGINE = MergeTree()
            PARTITION BY toYYYYMM(event_date)
            ORDER BY (category, event_date)
        ";

        $result = $this->query($createTable);
        if (!$result['success']) {
            return $result;
        }

        // Insert demo data
        $insertData = "
            INSERT INTO product_analytics VALUES
            ('PROD001', 'Смартфон Samsung Galaxy S23 Ultra', 'Электроника', 89999.99, 12500, 342, 30779966.58, '2024-01-15'),
            ('PROD002', 'Ноутбук Apple MacBook Pro 16', 'Электроника', 249999.00, 8900, 156, 38999844.00, '2024-01-15'),
            ('PROD003', 'Кофемашина DeLonghi Primadonna', 'Бытовая техника', 78999.50, 6700, 89, 7030955.50, '2024-01-15'),
            ('PROD004', 'Наушники Sony WH-1000XM5', 'Электроника', 34999.99, 23400, 1287, 45044987.13, '2024-01-16'),
            ('PROD005', 'Умные часы Apple Watch Ultra 2', 'Электроника', 59999.00, 15600, 678, 40679322.00, '2024-01-16'),
            ('PROD006', 'Пылесос Dyson V15 Detect Absolute', 'Бытовая техника', 74999.00, 9800, 324, 24299676.00, '2024-01-16'),
            ('PROD007', 'Планшет iPad Pro 12.9', 'Электроника', 129999.00, 11200, 456, 59279544.00, '2024-01-17'),
            ('PROD008', 'Холодильник Samsung Bespoke', 'Бытовая техника', 159999.00, 5400, 123, 19679877.00, '2024-01-17')
        ";

        return $this->query($insertData);
    }

    public function getAnalytics()
    {
        $sql = "
            SELECT 
                category,
                COUNT(*) as product_count,
                SUM(views) as total_views,
                SUM(purchases) as total_purchases,
                SUM(revenue) as total_revenue,
                ROUND(SUM(purchases) * 100.0 / SUM(views), 2) as conversion_rate,
                ROUND(AVG(price), 2) as avg_price,
                ROUND(MAX(price), 2) as max_price,
                ROUND(MIN(price), 2) as min_price
            FROM product_analytics 
            GROUP BY category
            ORDER BY total_revenue DESC
            FORMAT JSON
        ";

        $result = $this->query($sql);
        
        if ($result['success']) {
            $data = json_decode($result['data'], true);
            return [
                'success' => true,
                'analytics' => $data['data'] ?? []
            ];
        }

        return $result;
    }

    public function getSalesTrends()
    {
        $sql = "
            SELECT 
                event_date,
                category,
                SUM(purchases) as daily_purchases,
                SUM(revenue) as daily_revenue
            FROM product_analytics 
            GROUP BY event_date, category
            ORDER BY event_date, category
            FORMAT JSON
        ";

        $result = $this->query($sql);
        
        if ($result['success']) {
            $data = json_decode($result['data'], true);
            return [
                'success' => true,
                'trends' => $data['data'] ?? []
            ];
        }

        return $result;
    }
}