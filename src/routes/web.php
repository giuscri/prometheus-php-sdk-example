<?php

use Illuminate\Support\Facades\Route;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;

// registry is the storage used for our metrics
function getRegistry(): CollectorRegistry
{
    $adapter = new Redis(['host' => 'redis']);
    return new CollectorRegistry($adapter, false);
}

Route::post('/v1/cook/{food}', function (string $food) {
    $quantity = (int) request()->input('quantity', 1);
    $additions = (array) request()->input('additions', []);

    $registry = getRegistry();
    $counter = $registry->getOrRegisterCounter('kitchen', 'dishes_cooked_total', 'Number of individual dishes cooked', ['food']);
    $gauge = $registry->getOrRegisterGauge('kitchen', 'last_order_timestamp_seconds', 'Unix timestamp of the most recent order per food type', ['food']);
    $histogram = $registry->getOrRegisterHistogram('kitchen', 'dish_preparation_duration_seconds', 'Time spent preparing a single dish', ['food'], [0.1, 0.25, 0.5, 1, 5]);

    for ($i = 0; $i < $quantity; $i++) {
        $start = microtime(true);

        // 100ms base + 250ms per addition
        usleep(100000 + count($additions) * 250000);

        $counter->inc([$food]);
        $gauge->set(time(), [$food]);
        $histogram->observe(microtime(true) - $start, [$food]);
    }

    return "Hey, requesting `$food` using HTTP";
});

Route::get('/metrics', function () {
    $registry = getRegistry();
    $renderer = new RenderTextFormat();
    $result = $renderer->render($registry->getMetricFamilySamples());

    return response($result)->header('Content-Type', RenderTextFormat::MIME_TYPE);
});
