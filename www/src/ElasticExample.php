<?php

namespace App;

use App\Helpers\ClientFactory;
use GuzzleHttp\Exception\RequestException;

class ElasticExample
{
    private $client;

    public function __construct()
    {
        $this->client = ClientFactory::make('http://elasticsearch:9200/');
    }

    public function testConnection()
    {
        try {
            $response = $this->client->get('');
            $data = json_decode($response->getBody()->getContents(), true);
            return "✅ Elasticsearch: Version " . ($data['version']['number'] ?? 'unknown');
        } catch (RequestException $e) {
            return "❌ Elasticsearch connection error: " . $e->getMessage();
        }
    }

    public function createProductsIndex()
    {
        try {
            $mapping = [
                'mappings' => [
                    'properties' => [
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'standard',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword'
                                ]
                            ]
                        ],
                        'description' => [
                            'type' => 'text',
                            'analyzer' => 'standard'
                        ],
                        'category' => [
                            'type' => 'keyword'
                        ],
                        'price' => [
                            'type' => 'float'
                        ],
                        'in_stock' => [
                            'type' => 'boolean'
                        ],
                        'tags' => [
                            'type' => 'text'
                        ],
                        'created_at' => [
                            'type' => 'date',
                            'format' => 'yyyy-MM-dd'
                        ]
                    ]
                ]
            ];

            $response = $this->client->put('products', [
                'json' => $mapping
            ]);

            return "✅ Products index created successfully";
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 400) {
                return "ℹ️ Products index already exists";
            }
            return "❌ Error creating index: " . $e->getMessage();
        }
    }

    public function indexDocument($index, $id, $data)
    {
        try {
            $response = $this->client->put("$index/_doc/$id", [
                'json' => $data
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            return [
                'success' => true,
                'message' => "✅ Document indexed in $index",
                'data' => $result
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => "❌ Error indexing document: " . $e->getMessage()
            ];
        }
    }

    public function search($index, $query)
    {
        try {
            $searchBody = [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['name^3', 'description^2', 'tags'],
                        'fuzziness' => 'AUTO'
                    ]
                ],
                'size' => 20
            ];

            $response = $this->client->get("$index/_search", [
                'json' => $searchBody
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            return [
                'success' => true,
                'total' => $result['hits']['total']['value'] ?? 0,
                'hits' => $result['hits']['hits'] ?? [],
                'took' => $result['took'] ?? 0
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => "❌ Search error: " . $e->getMessage(),
                'total' => 0,
                'hits' => []
            ];
        }
    }

    public function addSampleProducts()
    {
        $products = [
            [
                'name' => 'Смартфон Samsung Galaxy S23 Ultra',
                'description' => 'Флагманский смартфон с мощным процессором Snapdragon и продвинутой камерой 200MP. Идеален для фотосъемки и игр.',
                'category' => 'Электроника',
                'price' => 89999.99,
                'in_stock' => true,
                'tags' => 'смартфон, samsung, android, камера, флагман, 5g',
                'created_at' => '2024-01-15'
            ],
            [
                'name' => 'Ноутбук Apple MacBook Pro 16',
                'description' => 'Профессиональный ноутбук с процессором M3 Max. Отлично подходит для дизайна, программирования и видеообработки.',
                'category' => 'Электроника',
                'price' => 249999.00,
                'in_stock' => true,
                'tags' => 'ноутбук, apple, macbook, m3, профессиональный, дизайн',
                'created_at' => '2024-01-14'
            ],
            [
                'name' => 'Кофемашина DeLonghi Primadonna',
                'description' => 'Полностью автоматическая кофемашина с технологией капучино. Легка в использовании и чистке, подходит для дома и офиса.',
                'category' => 'Бытовая техника',
                'price' => 78999.50,
                'in_stock' => false,
                'tags' => 'кофемашина, delonghi, автоматическая, капучино, эспрессо',
                'created_at' => '2024-01-13'
            ],
            [
                'name' => 'Наушники Sony WH-1000XM5',
                'description' => 'Беспроводные наушники с улучшенным шумоподавлением. Превосходный звук и комфорт для длительного использования.',
                'category' => 'Электроника',
                'price' => 34999.99,
                'in_stock' => true,
                'tags' => 'наушники, sony, беспроводные, шумоподавление, bluetooth, аудио',
                'created_at' => '2024-01-16'
            ],
            [
                'name' => 'Умные часы Apple Watch Ultra 2',
                'description' => 'Прочные умные часы для спорта и активного отдыха. Водонепроницаемость, GPS и долгая батарея.',
                'category' => 'Электроника',
                'price' => 59999.00,
                'in_stock' => true,
                'tags' => 'часы, apple, watch, фитнес, спорт, смарт-часы',
                'created_at' => '2024-01-12'
            ],
            [
                'name' => 'Пылесос Dyson V15 Detect Absolute',
                'description' => 'Мощный беспроводной пылесос с лазерной подсветкой пыли. Технология циклонной очистки для эффективной уборки.',
                'category' => 'Бытовая техника',
                'price' => 74999.00,
                'in_stock' => true,
                'tags' => 'пылесос, dyson, беспроводной, лазер, уборка, техника',
                'created_at' => '2024-01-11'
            ],
            [
                'name' => 'Планшет iPad Pro 12.9',
                'description' => 'Мощный планшет с дисплеем Liquid Retina XDR. Идеален для творчества, работы и развлечений.',
                'category' => 'Электроника',
                'price' => 129999.00,
                'in_stock' => true,
                'tags' => 'планшет, ipad, apple, творчество, дисплей, мощный',
                'created_at' => '2024-01-10'
            ],
            [
                'name' => 'Холодильник Samsung Bespoke',
                'description' => 'Стильный холодильник с customizable панелями. Технология SpaceMax для максимального объема.',
                'category' => 'Бытовая техника',
                'price' => 159999.00,
                'in_stock' => true,
                'tags' => 'холодильник, samsung, кухня, бытовая техника, stylish',
                'created_at' => '2024-01-09'
            ]
        ];

        $results = [];
        foreach ($products as $index => $product) {
            $result = $this->indexDocument('products', $index + 1, $product);
            $results[] = $result['success'] ? 
                "✅ " . $product['name'] : 
                "❌ " . $product['name'] . " - " . $result['message'];
        }

        return $results;
    }

    public function getStats()
    {
        try {
            // Count total documents
            $countResponse = $this->client->get('products/_count');
            $countData = json_decode($countResponse->getBody()->getContents(), true);

            // Get category aggregation
            $aggResponse = $this->client->get('products/_search', [
                'json' => [
                    'size' => 0,
                    'aggs' => [
                        'categories' => [
                            'terms' => [
                                'field' => 'category',
                                'size' => 10
                            ]
                        ],
                        'price_stats' => [
                            'stats' => [
                                'field' => 'price'
                            ]
                        ]
                    ]
                ]
            ]);

            $aggData = json_decode($aggResponse->getBody()->getContents(), true);

            return [
                'success' => true,
                'total' => $countData['count'] ?? 0,
                'categories' => $aggData['aggregations']['categories']['buckets'] ?? [],
                'price_stats' => $aggData['aggregations']['price_stats'] ?? []
            ];

        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => "❌ Stats error: " . $e->getMessage()
            ];
        }
    }
}