<?php

namespace App;

class ClickhouseExample
{
    private $demoAnalytics;

    public function __construct()
    {
        $this->demoAnalytics = $this->getDemoAnalytics();
    }

    private function getDemoAnalytics()
    {
        return [
            [
                'category' => 'Электроника',
                'product_count' => 4,
                'total_views' => 6290,
                'total_purchases' => 263,
                'total_revenue' => 13239906.55,
                'conversion_rate' => 4.18,
                'avg_price' => 70499.50,
                'max_price' => 129999.00,
                'min_price' => 29999.99
            ],
            [
                'category' => 'Бытовая техника',
                'product_count' => 2,
                'total_views' => 1650,
                'total_purchases' => 47,
                'total_revenue' => 2449960.50,
                'conversion_rate' => 2.85,
                'avg_price' => 50499.25,
                'max_price' => 54999.00,
                'min_price' => 45999.50
            ]
        ];
    }

    public function getCategoryAnalytics()
    {
        return [
            'success' => true,
            'analytics' => $this->demoAnalytics
        ];
    }

    public function testConnection()
    {
        return "✅ ClickHouse (демо-режим): Аналитика работает на PHP";
    }

    public function initializeAnalytics()
    {
        return [
            'success' => true,
            'message' => 'Демо-аналитика инициализирована'
        ];
    }
}