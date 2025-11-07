<?php

use App\RedisExample;
use App\ElasticExample;
use App\ClickhouseExample;

// Инициализация классов
$redis = new RedisExample();
$elastic = new ElasticExample();
$clickhouse = new ClickhouseExample();

// Обработка поисковых запросов
$searchResults = null;
$searchQuery = '';
$filters = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchQuery = trim($_POST['search_query'] ?? '');
    $filters = [
        'category' => $_POST['category'] ?? '',
        'in_stock' => $_POST['in_stock'] ?? '',
        'min_price' => $_POST['min_price'] ?? '',
        'max_price' => $_POST['max_price'] ?? ''
    ];

    if (!empty($searchQuery)) {
        $searchResults = $elastic->searchProducts($searchQuery, $filters);
    }
}

// Инициализация данных при первой загрузке
$initializationMessage = '';
$stats = $elastic->getStats();
$categoryAnalytics = $clickhouse->getCategoryAnalytics();

if ($stats['success'] && $stats['total_products'] === 0) {
    // Создаем индекс и добавляем товары
    $elastic->createProductsIndex();
    $initializationResults = $elastic->addSampleProducts();
    $initializationMessage = "Добавлено " . count($initializationResults) . " тестовых товаров";
    
    // Инициализируем аналитику в ClickHouse
    $clickhouse->initializeAnalytics();
}

// Получаем информацию о подключении
$elasticStatus = $elastic->testConnection();
$clickhouseStatus = $clickhouse->testConnection();
$redisInfo = $redis->getDemoData();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа №6 - Поиск товаров в Elasticsearch</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .status-bar {
            display: flex;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .search-form input {
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-form button {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        
        .search-form button:hover {
            background: #5a6fd8;
        }
        
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filters input, .filters select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .product-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            line-height: 1.3;
        }
        
        .product-category {
            background: #e7f3ff;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #667eea;
            display: inline-block;
            margin-bottom: 12px;
        }
        
        .product-price {
            font-size: 1.4rem;
            font-weight: bold;
            color: #28a745;
            margin: 12px 0;
        }
        
        .product-stock {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .in-stock {
            color: #28a745;
        }
        
        .out-of-stock {
            color: #dc3545;
        }
        
        .product-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .product-tags {
            font-size: 0.8rem;
            color: #999;
            margin-top: 10px;
        }
        
        .search-meta {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .analytics-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .analytics-table th,
        .analytics-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .analytics-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        .analytics-table tr:hover {
            background: #f8f9fa;
        }
        
        .json-output {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin-top: 15px;
            line-height: 1.4;
        }
        
        .success {
            color: #28a745;
            font-weight: 600;
        }
        
        .error {
            color: #dc3545;
            font-weight: 600;
        }
        
        .info {
            color: #17a2b8;
            font-weight: 600;
        }
        
        .highlight {
            background: linear-gradient(120deg, #a8edea 0%, #fed6e3 100%);
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .status-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Лабораторная работа №6</h1>
        <p class="subtitle">Поиск товаров по описанию в Elasticsearch + Аналитика в ClickHouse</p>
        
        <!-- Статус подключения -->
        <div class="status-bar">
            <div class="status-item">
                <span class="success">●</span>
                <span><strong>Elasticsearch:</strong> <?php echo $elasticStatus; ?></span>
            </div>
            <div class="status-item">
                <span class="success">●</span>
                <span><strong>ClickHouse:</strong> <?php echo $clickhouseStatus; ?></span>
            </div>
            <div class="status-item">
                <span class="success">●</span>
                <span><strong>Redis:</strong> Демо-режим (кэширование)</span>
            </div>
        </div>

        <?php if ($initializationMessage): ?>
            <div class="search-meta">
                <span class="info"><?php echo $initializationMessage; ?></span>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="section">
            <h2>📊 Статистика базы товаров</h2>
            <?php if ($stats['success']): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                        <div class="stat-label">Всего товаров</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($stats['categories']); ?></div>
                        <div class="stat-label">Категорий</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php echo number_format($stats['price_stats']['avg'] ?? 0, 0, '.', ' '); ?> ₽
                        </div>
                        <div class="stat-label">Средняя цена</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                            $inStock = array_filter($stats['stock_stats'] ?? [], function($item) {
                                return $item['key'] == 1;
                            });
                            echo $inStock ? current($inStock)['doc_count'] : 0;
                            ?>
                        </div>
                        <div class="stat-label">Товаров в наличии</div>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <h3 style="margin-bottom: 15px;">📈 Распределение по категориям</h3>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Категория</th>
                                <th>Количество товаров</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['categories'] as $category): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($category['key']); ?></strong></td>
                                    <td><?php echo $category['doc_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="error">Ошибка загрузки статистики: <?php echo $stats['error']; ?></p>
            <?php endif; ?>
        </div>

        <!-- Поиск товаров -->
        <div class="section">
            <h2>🔍 Поиск товаров по описанию</h2>
            
            <form method="POST" class="search-form">
                <input type="text" name="search_query" 
                       placeholder="Введите название, описание или характеристики товара..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       required>
                <button type="submit">Найти товары</button>
            </form>

            <div class="filters">
                <select name="category">
                    <option value="">Все категории</option>
                    <option value="Электроника" <?php echo $filters['category'] === 'Электроника' ? 'selected' : ''; ?>>Электроника</option>
                    <option value="Бытовая техника" <?php echo $filters['category'] === 'Бытовая техника' ? 'selected' : ''; ?>>Бытовая техника</option>
                </select>
                
                <select name="in_stock">
                    <option value="">Любое наличие</option>
                    <option value="1" <?php echo $filters['in_stock'] === '1' ? 'selected' : ''; ?>>Только в наличии</option>
                    <option value="0" <?php echo $filters['in_stock'] === '0' ? 'selected' : ''; ?>>Нет в наличии</option>
                </select>
                
                <input type="number" name="min_price" placeholder="Мин. цена" 
                       value="<?php echo htmlspecialchars($filters['min_price']); ?>">
                <input type="number" name="max_price" placeholder="Макс. цена" 
                       value="<?php echo htmlspecialchars($filters['max_price']); ?>">
            </div>

            <?php if ($searchResults): ?>
                <div class="search-meta">
                    <div>
                        <strong>Найдено товаров:</strong> <?php echo $searchResults['total']; ?>
                        <?php if ($searchResults['took']): ?>
                            <span style="margin-left: 15px; color: #666;">
                                (за <?php echo $searchResults['took']; ?>мс)
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>Запрос:</strong> "<span class="highlight"><?php echo htmlspecialchars($searchQuery); ?></span>"
                    </div>
                </div>

                <?php if ($searchResults['success'] && $searchResults['total'] > 0): ?>
                    <div class="products-grid">
                        <?php foreach ($searchResults['products'] as $product): ?>
                            <div class="product-card">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                                <div class="product-price"><?php echo number_format($product['price'], 0, '.', ' '); ?> ₽</div>
                                <div class="product-stock <?php echo $product['in_stock'] ? 'in-stock' : 'out-of-stock'; ?>">
                                    <?php echo $product['in_stock'] ? '✅ В наличии' : '❌ Нет в наличии'; ?>
                                </div>
                                <div class="product-description">
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </div>
                                <div class="product-tags">
                                    <strong>Теги:</strong> <?php echo htmlspecialchars($product['tags']); ?>
                                </div>
                                <?php if (isset($product['_score'])): ?>
                                    <div style="margin-top: 10px; font-size: 0.8rem; color: #999;">
                                        Релевантность: <?php echo round($product['_score'], 2); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <p style="font-size: 1.2rem; margin-bottom: 10px;">😔 Товары не найдены</p>
                        <p>Попробуйте изменить поисковый запрос или фильтры</p>
                    </div>
                <?php endif; ?>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($searchQuery)): ?>
                <div style="text-align: center; padding: 20px; color: #dc3545;">
                    <p>⚠️ Пожалуйста, введите поисковый запрос</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Аналитика ClickHouse -->
        <div class="section">
            <h2>📈 Аналитика продаж (ClickHouse)</h2>
            <?php if ($categoryAnalytics['success'] && !empty($categoryAnalytics['analytics'])): ?>
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>Категория</th>
                            <th>Товаров</th>
                            <th>Просмотры</th>
                            <th>Продажи</th>
                            <th>Выручка</th>
                            <th>Конверсия</th>
                            <th>Ср. цена</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryAnalytics['analytics'] as $analytic): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($analytic['category']); ?></strong></td>
                                <td><?php echo $analytic['product_count']; ?></td>
                                <td><?php echo number_format($analytic['total_views']); ?></td>
                                <td><?php echo number_format($analytic['total_purchases']); ?></td>
                                <td><?php echo number_format($analytic['total_revenue'], 0, '.', ' '); ?> ₽</td>
                                <td><?php echo $analytic['conversion_rate']; ?>%</td>
                                <td><?php echo number_format($analytic['avg_price'], 0, '.', ' '); ?> ₽</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="error">Аналитика недоступна: <?php echo $categoryAnalytics['error'] ?? 'Ошибка загрузки данных'; ?></p>
            <?php endif; ?>
        </div>

        <!-- Redis информация -->
        <div class="section">
            <h2>🔴 Redis демо-данные</h2>
            <div class="json-output">
                <?php echo json_encode($redisInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
            </div>
        </div>
    </div>

    <script>
        // Автофокус на поле поиска
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search_query"]');
            if (searchInput) {
                searchInput.focus();
            }
        });

        // Плавная прокрутка к результатам поиска
        document.querySelector('form').addEventListener('submit', function() {
            setTimeout(() => {
                const results = document.querySelector('.search-meta');
                if (results) {
                    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        });
    </script>
</body>
</html>