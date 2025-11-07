<?php
require 'vendor/autoload.php';

use App\RedisExample;
use App\ElasticExample;
use App\ClickhouseExample;

// Initialize classes
$redis = new RedisExample();
$elastic = new ElasticExample();
$clickhouse = new ClickhouseExample();

// Handle form submissions
$searchResults = null;
$searchQuery = '';
$redisResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_action'])) {
        $searchQuery = trim($_POST['search_query'] ?? '');
        if (!empty($searchQuery)) {
            $searchResults = $elastic->search('products', $searchQuery);
        }
    }
    
    if (isset($_POST['redis_action'])) {
        $redisKey = $_POST['redis_key'] ?? 'demo:key';
        $redisValue = $_POST['redis_value'] ?? 'demo value';
        
        $redisResults = [
            'set' => $redis->setValue($redisKey, $redisValue),
            'get' => $redis->getValue($redisKey)
        ];
    }
}

// Initialize data on first load
$initializationMessage = '';
$elasticStats = $elastic->getStats();
$clickhouseAnalytics = $clickhouse->getAnalytics();

// Create index and add sample data if needed
if ($elasticStats['success'] && $elasticStats['total'] === 0) {
    $elastic->createProductsIndex();
    $sampleResults = $elastic->addSampleProducts();
    $initializationMessage = "Initialized with " . count($sampleResults) . " sample products";
    
    // Initialize ClickHouse data
    $clickhouse->initializeDemoData();
}

// Test connections
$elasticStatus = $elastic->testConnection();
$clickhouseStatus = $clickhouse->testConnection();
$redisStatus = $redis->testConnection();
$redisDemoData = $redis->getDemoData();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа №6 - Нереляционные БД</title>
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #28a745;
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
        }
        
        .search-form button {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .redis-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .redis-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .redis-form button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
        }
        
        .product-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
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
        
        .analytics-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
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
        }
        
        .json-output {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin-top: 10px;
        }
        
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .search-form { grid-template-columns: 1fr; }
            .redis-form { grid-template-columns: 1fr; }
            .products-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Лабораторная работа №6</h1>
        <p class="subtitle">Нереляционные базы данных: Redis, Elasticsearch, ClickHouse</p>

        <?php if ($initializationMessage): ?>
            <div class="section">
                <div class="info"><?php echo $initializationMessage; ?></div>
            </div>
        <?php endif; ?>

        <!-- Status Bar -->
        <div class="status-bar">
            <div class="status-card">
                <h3>🔴 Redis</h3>
                <p><?php echo $redisStatus; ?></p>
            </div>
            <div class="status-card">
                <h3>🔍 Elasticsearch</h3>
                <p><?php echo $elasticStatus; ?></p>
            </div>
            <div class="status-card">
                <h3>⚡ ClickHouse</h3>
                <p><?php echo $clickhouseStatus; ?></p>
            </div>
        </div>

        <!-- Elasticsearch Search -->
        <div class="section">
            <h2>🔍 Поиск товаров в Elasticsearch</h2>
            
            <form method="POST" class="search-form">
                <input type="hidden" name="search_action" value="1">
                <input type="text" name="search_query" 
                       placeholder="Введите название или описание товара..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       required>
                <button type="submit">Найти товары</button>
            </form>

            <?php if ($searchResults): ?>
                <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>Результаты поиска:</strong> <?php echo $searchResults['total']; ?> товаров найдено
                    <?php if ($searchResults['took']): ?>
                        (за <?php echo $searchResults['took']; ?>мс)
                    <?php endif; ?>
                </div>

                <?php if ($searchResults['success'] && $searchResults['total'] > 0): ?>
                    <div class="products-grid">
                        <?php foreach ($searchResults['hits'] as $hit): ?>
                            <?php $product = $hit['_source']; ?>
                            <div class="product-card">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                                <div class="product-price"><?php echo number_format($product['price'], 0, '.', ' '); ?> ₽</div>
                                <p><strong>Наличие:</strong> <?php echo $product['in_stock'] ? '✅ В наличии' : '❌ Нет в наличии'; ?></p>
                                <p style="margin-top: 10px; color: #666;"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div style="margin-top: 10px; font-size: 0.8rem; color: #999;">
                                    Теги: <?php echo htmlspecialchars($product['tags']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="error">Товары не найдены</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Redis Demo -->
        <div class="section">
            <h2>🔴 Redis операции</h2>
            
            <form method="POST" class="redis-form">
                <input type="hidden" name="redis_action" value="1">
                <input type="text" name="redis_key" placeholder="Ключ" value="user:name">
                <input type="text" name="redis_value" placeholder="Значение" value="John Doe">
                <button type="submit">Выполнить SET/GET</button>
            </form>

            <?php if ($redisResults): ?>
                <div class="json-output">
                    <strong>SET результат:</strong> <?php echo $redisResults['set']; ?><br>
                    <strong>GET результат:</strong> <?php echo $redisResults['get']; ?>
                </div>
            <?php endif; ?>

            <h3 style="margin-top: 20px;">Redis информация:</h3>
            <div class="json-output">
                <?php echo json_encode($redisDemoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="section">
            <h2>📊 Статистика Elasticsearch</h2>
            <?php if ($elasticStats['success']): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $elasticStats['total']; ?></div>
                        <div>Всего товаров</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($elasticStats['categories']); ?></div>
                        <div>Категорий</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php echo number_format($elasticStats['price_stats']['avg'] ?? 0, 0, '.', ' '); ?> ₽
                        </div>
                        <div>Средняя цена</div>
                    </div>
                </div>

                <h3 style="margin-top: 20px;">Категории:</h3>
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>Категория</th>
                            <th>Количество товаров</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($elasticStats['categories'] as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['key']); ?></td>
                                <td><?php echo $category['doc_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="error">Ошибка загрузки статистики</p>
            <?php endif; ?>
        </div>

        <!-- ClickHouse Analytics -->
        <div class="section">
            <h2>📈 Аналитика ClickHouse</h2>
            <?php if ($clickhouseAnalytics['success'] && !empty($clickhouseAnalytics['analytics'])): ?>
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>Категория</th>
                            <th>Товаров</th>
                            <th>Просмотры</th>
                            <th>Продажи</th>
                            <th>Выручка</th>
                            <th>Конверсия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clickhouseAnalytics['analytics'] as $analytic): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($analytic['category']); ?></strong></td>
                                <td><?php echo $analytic['product_count']; ?></td>
                                <td><?php echo number_format($analytic['total_views']); ?></td>
                                <td><?php echo number_format($analytic['total_purchases']); ?></td>
                                <td><?php echo number_format($analytic['total_revenue'], 0, '.', ' '); ?> ₽</td>
                                <td><?php echo $analytic['conversion_rate']; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="error">Аналитика недоступна</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>