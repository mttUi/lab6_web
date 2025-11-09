<?php
require 'vendor/autoload.php';

use App\RedisExample;
use App\ElasticExample;
use App\ClickhouseExample;

$redis = new RedisExample();
$elastic = new ElasticExample();
$clickhouse = new ClickhouseExample();

$redisStatus = $redis->checkConnection();
$elasticStatus = $elastic->checkConnection();
$clickhouseStatus = $clickhouse->checkConnection();

$action = $_POST['action'] ?? '';
$searchTerm = $_POST['search'] ?? '';

$initResults = [];
$searchResults = null;

if ($action === 'init_data') {
    $initResults = array_merge(
        $elastic->initializeData(),
        $clickhouse->initializeData()
    );
}

if ($searchTerm) {
    $searchResults = $elastic->searchProductsByDescription($searchTerm);
}

$elasticStats = $elastic->getIndexStats();
$clickhouseStats = $clickhouse->getProductsStats();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Лабораторная работа №6</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .status { padding: 8px; border-radius: 4px; margin: 5px 0; }
        .connected { background: #d4ffd4; color: #006400; }
        .error { background: #ffd4d4; color: #8b0000; }
        .product { border: 1px solid #ccc; margin: 10px 0; padding: 10px; border-radius: 5px; }
        .search-form { margin: 10px 0; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-primary { background: #2196F3; }
        .btn-success { background: #4CAF50; }
        .init-results { background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Лабораторная работа №6</h1>

        <?php if (!empty($initResults)): ?>
            <div class="init-results">
                <h3>Результаты инициализации:</h3>
                <?php foreach ($initResults as $result): ?>
                    <div><?= htmlspecialchars($result) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>Redis</h2>
            <div class="status <?= $redisStatus['status'] ?>">
                <?= $redisStatus['status'] === 'connected' ? '✓ ' . $redisStatus['message'] : '✗ ' . $redisStatus['message'] ?>
            </div>
            <h4>Операции:</h4>
            <?php foreach ($redis->demoOperations() as $op): ?>
                <div style="font-family: monospace; background: #f0f0f0; padding: 5px; margin: 2px;"><?= $op ?></div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h2>Elasticsearch</h2>
            <div class="status <?= $elasticStatus['status'] ?>">
                <?= $elasticStatus['status'] === 'connected' ? '✓ Version ' . $elasticStatus['version'] : '✗ ' . $elasticStatus['message'] ?>
            </div>

            <div class="search-form">
                <form method="post">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Поиск товаров..." style="padding: 8px; width: 300px;">
                    <button type="submit" class="btn btn-primary">Найти</button>
                </form>
                
                <form method="post" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="init_data">
                    <button type="submit" class="btn btn-success">Инициализировать тестовые данные</button>
                </form>
            </div>

            <?php if ($searchResults): ?>
                <h3>Результаты поиска: "<?= htmlspecialchars($searchTerm) ?>"</h3>
                <p>Найдено: <?= $searchResults['total'] ?> товаров (<?= $searchResults['took'] ?>мс)</p>
                
                <?php if ($searchResults['success'] && $searchResults['total'] > 0): ?>
                    <?php foreach ($searchResults['hits'] as $hit): ?>
                        <?php $product = $hit['_source']; ?>
                        <div class="product">
                            <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                            <?= htmlspecialchars($product['description']) ?><br>
                            Цена: <?= number_format($product['price'], 0) ?> руб.<br>
                            Категория: <?= htmlspecialchars($product['category']) ?> | 
                            В наличии: <?= $product['in_stock'] ? 'Да' : 'Нет' ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Товары не найдены</p>
                <?php endif; ?>
            <?php endif; ?>

            <h3>Статистика:</h3>
            <p>Товаров в индексе: <?= $elasticStats['docs_count'] ?? 0 ?></p>
        </div>

        <div class="section">
            <h2>ClickHouse</h2>
            <div class="status <?= $clickhouseStatus['status'] ?>">
                <?= $clickhouseStatus['status'] === 'connected' ? '✓ ' . $clickhouseStatus['message'] : '✗ ' . $clickhouseStatus['message'] ?>
            </div>

            <?php if ($clickhouseStats['success'] && !empty($clickhouseStats['data'])): ?>
                <h3>Аналитика товаров:</h3>
                <?php foreach ($clickhouseStats['data'] as $stat): ?>
                    <div class="product">
                        <strong><?= htmlspecialchars($stat['name']) ?></strong><br>
                        Просмотры: <?= $stat['views'] ?> | 
                        Покупки: <?= $stat['purchases'] ?> | 
                        Конверсия: <?= $stat['conversion_rate'] ?>%
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Данные не найдены. Инициализируйте тестовые данные.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>