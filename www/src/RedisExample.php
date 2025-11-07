<?php

namespace App;

class RedisExample
{
    public function testConnection()
    {
        return "✅ Redis (демо-режим): Готов к работе";
    }

    public function getDemoData()
    {
        return [
            'database' => 'Redis',
            'status' => 'Демо-режим',
            'purpose' => 'Кэширование результатов поиска',
            'example_use_case' => 'Кэширование популярных поисковых запросов',
            'cache_duration' => '300 секунд (5 минут)',
            'features' => [
                'Быстрый доступ к данным',
                'Хранение сессий',
                'Кэширование API запросов'
            ]
        ];
    }
}