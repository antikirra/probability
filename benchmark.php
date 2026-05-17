<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use function Antikirra\probability;

/**
 * Comprehensive Performance Benchmark for probability() function
 * Tests both Random (no key) and Deterministic (with key) modes
 */

const ITERATIONS = 2_000_000; // 2 million operations for accuracy
const WARMUP_ITERATIONS = 100_000; // 100k warmup to stabilize JIT

echo "PHP Probability Function Benchmark\n";
echo "===================================\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Platform: " . PHP_OS . "\n";
echo "Iterations: " . number_format(ITERATIONS) . "\n";
echo "Warmup: " . number_format(WARMUP_ITERATIONS) . "\n\n";

// Warmup phase - stabilize JIT compiler and CPU cache
echo "Warming up JIT compiler...\n";
for ($i = 0; $i < WARMUP_ITERATIONS; $i++) {
    probability(0.5); // Random
    probability(0.5, "warmup_key_$i"); // Deterministic
}
echo "Warmup complete.\n\n";

// Benchmark 1: Random (no key)
echo "Benchmarking Random mode (no key)...\n";
$startMemory = memory_get_usage();
$startTime = hrtime(true);

for ($i = 0; $i < ITERATIONS; $i++) {
    probability(0.5); // 50% probability
}

$endTime = hrtime(true);
$endMemory = memory_get_usage();

$durationNs = $endTime - $startTime;
$durationSeconds = $durationNs / 1_000_000_000;
$opsPerSecond = ITERATIONS / $durationSeconds;
$timePerOpUs = ($durationNs / ITERATIONS) / 1_000; // Convert ns to μs
$memoryUsed = $endMemory - $startMemory;

echo "  Duration: " . number_format($durationSeconds, 6) . " seconds\n";
echo "  Operations/sec: " . number_format($opsPerSecond, 0) . "\n";
echo "  Time per operation: " . number_format($timePerOpUs, 3) . " μs\n";
echo "  Memory used: " . number_format($memoryUsed) . " bytes\n\n";

$randomOpsPerSec = $opsPerSecond;
$randomTimePerOp = $timePerOpUs;

// Benchmark 2: Deterministic (with key)
echo "Benchmarking Deterministic mode (with key)...\n";
$startMemory = memory_get_usage();
$startTime = hrtime(true);

for ($i = 0; $i < ITERATIONS; $i++) {
    // Use varying keys to prevent CPU cache optimization
    probability(0.5, "user_feature_" . ($i % 10000));
}

$endTime = hrtime(true);
$endMemory = memory_get_usage();

$durationNs = $endTime - $startTime;
$durationSeconds = $durationNs / 1_000_000_000;
$opsPerSecond = ITERATIONS / $durationSeconds;
$timePerOpUs = ($durationNs / ITERATIONS) / 1_000; // Convert ns to μs
$memoryUsed = $endMemory - $startMemory;

echo "  Duration: " . number_format($durationSeconds, 6) . " seconds\n";
echo "  Operations/sec: " . number_format($opsPerSecond, 0) . "\n";
echo "  Time per operation: " . number_format($timePerOpUs, 3) . " μs\n";
echo "  Memory used: " . number_format($memoryUsed) . " bytes\n\n";

$deterministicOpsPerSec = $opsPerSecond;
$deterministicTimePerOp = $timePerOpUs;

// Summary table
echo "Summary\n";
echo "=======\n\n";
echo "| Operation | Time per call | Ops/sec |\n";
echo "|-----------|---------------|---------|\n";
echo sprintf(
    "| Random (no key) | ~%.2f μs | ~%sM |\n",
    $randomTimePerOp,
    number_format($randomOpsPerSec / 1_000_000, 1)
);
echo sprintf(
    "| Deterministic (with key) | ~%.2f μs | ~%sM |\n",
    $deterministicTimePerOp,
    number_format($deterministicOpsPerSec / 1_000_000, 1)
);

echo "\nBenchmark completed successfully!\n";
