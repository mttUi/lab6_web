<?php

namespace App;

class ElasticExample
{
    private $demoProducts;

    public function __construct()
    {
        $this->demoProducts = $this->getDemoProducts();
    }

    private function getDemoProducts()
    {
        return [
            [
                'id' => 1,
                'name' => 'Смартфон Samsung Galaxy S23',
                'description' => 'Флагманский смартфон с мощным процессором и отличной камерой. Идеален для работы и развлечений.',
                'category' => 'Электроника',
                'price' => 79999.99,
                'in_stock' => true,
                'tags' => 'смартфон, samsung, android, камера, флагман',
                'created_at' => '2024-01-15'
            ],
            [
                'id' => 2,
                'name' => 'Ноутбук Apple MacBook Air',
                'description' => 'Легкий и мощный ноутбук с процессором M2. Отлично подходит для работы и творчества.',
                'category' => 'Электроника',
                'price' => 129999.00,
                'in_stock' => true,
                'tags' => 'ноутбук, apple, macbook, m2, премиум',
                'created_at' => '2024-01-14'
            ],
            [
                'id' => 3,
                'name' => 'Кофемашина DeLonghi Magnifica',
                'description' => 'Автоматическая кофемашина для приготовления эспрессо, капучино и латте. Легка в использовании и чистке.',
                'category' => 'Бытовая техника',
                'price' => 45999.50,
                'in_stock' => false,
                'tags' => 'кофемашина, delonghi, кофе, эспрессо, капучино',
                'created_at' => '2024-01-13'
            ],
            [
                'id' => 4,
                'name' => 'Наушники Sony WH-1000XM4',
                'description' => 'Беспроводные наушники с продвинутым шумоподавлением. Отличный звук и комфорт для длительного ношения.',
                'category' => 'Электроника',
                'price' => 29999.99,
                'in_stock' => true,
                'tags' => 'наушники, sony, беспроводные, шумоподавление, bluetooth',
                'created_at' => '2024-01-16'
            ],
            [
                'id' => 5,
                'name' => 'Умные часы Apple Watch Series 9',
                'description' => 'Современные умные часы с функциями фитнес-трекера и уведомлениями. Водонепроницаемость и долгая батарея.',
                'category' => 'Электроника',
                'price' => 41999.00,
                'in_stock' => true,
                'tags' => 'часы, apple, watch, фитнес, смарт-часы',
                'created_at' => '2024-01-12'
            ],
            [
                'id' => 6,
                'name' => 'Пылесос Dyson V11 Absolute',
                'description' => 'Мощный беспроводной пылесос с технологией циклонной очистки. Легкий и удобный для уборки.',
                'category' => 'Бытовая техника',
                'price' => 54999.00,
                'in_stock' => true,
                'tags' => 'пылесос, dyson, беспроводной, уборка, техника',
                'created_at' => '2024-01-11'
            ]
        ];
    }

    public function searchProducts($query, $filters = [])
    {
        $results = [];
        $searchQuery = strtolower(trim($query));
        
        foreach ($this->demoProducts as $product) {
            $score = 0;
            $matches = false;
            
            // Поиск по названию (высокий приоритет)
            if (stripos($product['name'], $searchQuery) !== false) {
                $score += 3;
                $matches = true;
            }
            
            // Поиск по описанию (средний приоритет)
            if (stripos($product['description'], $searchQuery) !== false) {
                $score += 2;
                $matches = true;
            }
            
            // Поиск по тегам (низкий приоритет)
            if (stripos($product['tags'], $searchQuery) !== false) {
                $score += 1;
                $matches = true;
            }
            
            // Применяем фильтры
            if ($matches) {
                if (!empty($filters['category']) && $product['category'] !== $filters['category']) {
                    $matches = false;
                }
                
                if (isset($filters['in_stock']) && $filters['in_stock'] !== '' && 
                    $product['in_stock'] != (bool)$filters['in_stock']) {
                    $matches = false;
                }
                
                if (!empty($filters['min_price']) && $product['price'] < (float)$filters['min_price']) {
                    $matches = false;
                }
                
                if (!empty($filters['max_price']) && $product['price'] > (float)$filters['max_price']) {
                    $matches = false;
                }
            }
            
            if ($matches) {
                $product['_score'] = $score;
                $results[] = $product;
            }
        }
        
        // Сортируем по релевантности
        usort($results, function($a, $b) {
            return $b['_score'] - $a['_score'];
        });

        return [
            'success' => true,
            'total' => count($results),
            'products' => $results,
            'took' => rand(10, 50)
        ];
    }

    public function getStats()
    {
        $categories = [];
        $totalPrice = 0;
        $inStockCount = 0;
        
        foreach ($this->demoProducts as $product) {
            $category = $product['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
            
            $totalPrice += $product['price'];
            if ($product['in_stock']) {
                $inStockCount++;
            }
        }
        
        $categoryBuckets = [];
        foreach ($categories as $category => $count) {
            $categoryBuckets[] = [
                'key' => $category,
                'doc_count' => $count
            ];
        }

        return [
            'success' => true,
            'total_products' => count($this->demoProducts),
            'categories' => $categoryBuckets,
            'price_stats' => [
                'avg' => $totalPrice / count($this->demoProducts),
                'min' => min(array_column($this->demoProducts, 'price')),
                'max' => max(array_column($this->demoProducts, 'price'))
            ],
            'stock_stats' => [
                [
                    'key' => 1,
                    'doc_count' => $inStockCount
                ],
                [
                    'key' => 0,
                    'doc_count' => count($this->demoProducts) - $inStockCount
                ]
            ]
        ];
    }

    public function testConnection()
    {
        return "✅ Elasticsearch (демо-режим): Поиск товаров работает на PHP";
    }

    public function createProductsIndex()
    {
        return "✅ Индекс товаров создан (демо-режим)";
    }

    public function addSampleProducts()
    {
        return ["✅ Демо-товары загружены в память"];
    }
}