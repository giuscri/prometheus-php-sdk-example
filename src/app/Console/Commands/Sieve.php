<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;

class Sieve extends Command
{
    protected $signature = 'sieve';

    public function handle(): void
    {
        $primes = [1, 2, 3, 5, 7, 11, 13];
        $prime = $primes[array_rand($primes)];

        $this->info('This is generating a prime number...');

        $start = microtime(true);
        usleep(random_int(100000, 500000));
        $duration = microtime(true) - $start;

        $this->info("Prime found: $prime. Shutting down!");

        $transport = (new OtlpHttpTransportFactory())->create(
            'http://otel-collector:4318/v1/metrics',
            'application/json',
        );
        $reader = new ExportingReader(new MetricExporter($transport));
        $meterProvider = MeterProvider::builder()
            ->addReader($reader)
            ->setResource(ResourceInfo::create(Attributes::create([
                'env' => 'pr',
                'device' => 'cpu',
                'sieve_type' => 'sleep',
                'anything_can_be' => 'here'
            ])))
            ->build();

        $meter = $meterProvider->getMeter('custom_prefix');
        $counter = $meter->createCounter(
            'prime_sieving_duration_seconds',
            's',
            'Total time spent sieving primes',
        );
        $counter->add($duration, ['head_tail' => random_int(0, 1) === 1 ? 'true' : 'false']);

        $meterProvider->shutdown();
    }
}
