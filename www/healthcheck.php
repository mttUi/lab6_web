<?php
$status = 'OK';
try {
    $redis = new Redis();
    if (!$redis->connect('redis', 6379, 2.0)) {
        $status = 'Redis connection failed';
    }
} catch (Exception $e) {
    $status = 'Redis error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode(['status' => $status, 'timestamp' => date('c')]);