# Make your code work spontaneously 🙃

![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/antikirra/probability/php)
![Packagist Version](https://img.shields.io/packagist/v/antikirra/probability)

A lightweight PHP library for probabilistic code execution and deterministic feature distribution. Perfect for A/B testing, gradual feature rollouts, performance sampling, and controlled chaos engineering.

## Quick Start

```php
use function Antikirra\probability;

// Random execution - 30% chance to log debug info
if (probability(0.3)) {
    error_log("Debug: processing request #{$requestId}");
}

// Deterministic execution - same user always gets same experience
if (probability(0.5, "new_checkout_user_{$userId}")) {
    return renderNewCheckout();
}

// Gradual rollout - increase from 10% to 100% over time
if (probability(0.1, "feature_ai_search_user_{$userId}")) {
    enableAISearch();
}
```

## Install

```console
composer require antikirra/probability:^3.0
```

## 🚀 Key Features

- **Zero Dependencies** - Pure PHP implementation
- **Deterministic Distribution** - Consistent results for the same input keys
- **High Performance** - Minimal overhead, suitable for high-traffic applications
- **Simple API** - Just one function with intuitive parameters
- **Battle-tested** - Production-ready with predictable behavior at scale

## 💡 Use Cases

- **Performance Sampling** - Log only a fraction of requests to reduce storage costs while maintaining system visibility. Sample database queries, API calls, or user interactions for performance monitoring without overwhelming your logging infrastructure.

- **A/B Testing** - Run controlled experiments with consistent user experience. Test new features, UI changes, or algorithms on a specific percentage of users while ensuring each user always sees the same variant throughout their session.

- **Feature Flags** - Gradually roll out new features with fine-grained control. Start with a small percentage of users and increase over time, or enable features for specific user segments based on subscription tiers or other criteria.

- **Chaos Engineering** - Test system resilience by introducing controlled failures. Simulate random delays, service outages, or cache misses to ensure your application handles edge cases gracefully in production.

- **Rate Limiting** - Implement soft rate limits without additional infrastructure. Control access to expensive operations or API endpoints based on user tiers, preventing abuse while maintaining a smooth experience for legitimate users.

- **Load Balancing** - Distribute traffic across different backend services or database replicas probabilistically, achieving simple load distribution without complex routing rules.

- **Canary Deployments** - Route a small percentage of traffic to new application versions or infrastructure, monitoring for issues before full rollout.

- **Analytics Sampling** - Reduce analytics data volume and costs by tracking only a representative sample of events while maintaining statistical significance.

- **Content Variation** - Test different content strategies, email templates, or notification messages to optimize engagement metrics.

- **Resource Optimization** - Selectively enable resource-intensive features like real-time updates, advanced search, or AI-powered suggestions based on server load or user priority.

## 🔬 How It Works

The library uses two strategies for probability calculation:

### 1. Pure Random (No Key)
When called without a key, uses PHP's `mt_rand()` for true randomness:

```php
probability(0.25); // 25% chance, different result each time
```

### 2. Deterministic (With Key)
When provided with a key, uses CRC32 hashing for consistent results:

```php
probability(0.25, 'unique_key'); // Same result for same key
```

**Technical Details:**
- Uses `crc32()` to hash the key into a 32-bit unsigned integer (0 to 4,294,967,295)
- Normalizes the hash by dividing by `MAX_UINT32` (4294967295) to get a value between 0.0 and 1.0
- Compares normalized value against the probability threshold
- Same key → same hash → same normalized value → deterministic result

The deterministic approach ensures:
- Same input always produces same output
- Uniform distribution across large datasets
- No need for external storage or coordination
- Fast performance (CRC32 is optimized in PHP)

## 📖 API Reference

```php
function probability(float $probability, string $key = ''): bool
```

### Parameters

- **`$probability`** *(float)*: A value between 0.0 and 1.0
    - `0.0` = Never returns true (0% chance)
    - `0.5` = Returns true half the time (50% chance)
    - `1.0` = Always returns true (100% chance)

- **`$key`** *(string|null)*: Optional. When provided, ensures deterministic behavior
    - Same key always produces same result
    - Different keys distribute uniformly

### Returns

- **`bool`**: `true` if the event should occur, `false` otherwise

### Examples

```php
// 15% random chance
probability(0.15);

// Deterministic 30% for user with id 123
probability(0.30, "user_123");

// Combining feature and user for unique distribution
probability(0.25, "feature_checkout_user_123");
```

## 🎯 Best Practices

### 1. Use Meaningful Keys

```php
// ❌ Bad - too generic
probability(0.5, "test");

// ✅ Good - specific and unique
probability(0.5, "homepage_redesign_user_$userId");
```

### 2. Separate Features

```php
// ❌ Bad - same users get all features
if (probability(0.2, $userId)) { /* feature A */ }
if (probability(0.2, $userId)) { /* feature B */ }

// ✅ Good - different user groups per feature
if (probability(0.2, "feature_a_$userId")) { /* feature A */ }
if (probability(0.2, "feature_b_$userId")) { /* feature B */ }
```

### 3. Consider Scale

```php
// For high-frequency operations, use very small probabilities
if (probability(0.001)) { // 0.1% - suitable for millions of requests
    $metrics->record($data);
}
```

## 📊 When to Use: Random vs Deterministic

| Scenario | Use Random (no key) | Use Deterministic (with key) |
|----------|-------------------|---------------------------|
| **Performance sampling** | ✅ Sample random requests | ❌ Would sample same requests |
| **Logging/Debugging** | ✅ Random sampling | ❌ Not needed for logs |
| **A/B Testing** | ❌ Inconsistent UX | ✅ User sees same variant |
| **Feature Rollout** | ❌ Unpredictable access | ✅ Stable feature access |
| **Chaos Engineering** | ✅ Random failures | ⚠️ Depends on use case |
| **Load Testing** | ✅ Random distribution | ❌ Predictable patterns |
| **Canary Deployment** | ❌ Unstable routing | ✅ Consistent routing |
| **User Segmentation** | ❌ Segments change | ✅ Stable segments |

## 💻 Real-World Examples

### Laravel: Feature Flag Middleware

```php
namespace App\Http\Middleware;

use Closure;
use function Antikirra\probability;

class FeatureFlag
{
    public function handle($request, Closure $next, $feature, $percentage)
    {
        $userId = $request->user()?->id ?? $request->ip();
        $key = "{$feature}_user_{$userId}";

        if (!probability((float)$percentage, $key)) {
            abort(404); // Feature not enabled for this user
        }

        return $next($request);
    }
}

// Usage in routes:
// Route::get('/beta', ...)->middleware('feature:beta_dashboard,0.1');
```

### Symfony: Performance Monitoring

```php
use function Antikirra\probability;
use Psr\Log\LoggerInterface;

class DatabaseQueryLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private float $samplingRate = 0.01 // 1% of queries
    ) {}

    public function logQuery(string $sql, float $duration): void
    {
        // Random sampling - no need for deterministic behavior
        if (!probability($this->samplingRate)) {
            return;
        }

        $this->logger->info('Query executed', [
            'sql' => $sql,
            'duration' => $duration,
            'sampled' => true
        ]);
    }
}
```

### WordPress: A/B Testing

```php
use function Antikirra\probability;

function show_homepage_variant() {
    $user_id = get_current_user_id() ?: $_SERVER['REMOTE_ADDR'];
    $key = "homepage_redesign_user_{$user_id}";

    // 50% of users see new design, consistently
    if (probability(0.5, $key)) {
        get_template_part('homepage', 'new');
    } else {
        get_template_part('homepage', 'classic');
    }
}
```

### API Rate Limiting by Tier

```php
use function Antikirra\probability;

class ApiRateLimiter
{
    public function allowRequest(User $user, string $endpoint): bool
    {
        $limits = [
            'free' => 0.1,    // 10% of requests allowed
            'basic' => 0.5,   // 50% of requests allowed
            'premium' => 1.0  // 100% of requests allowed
        ];

        $probability = $limits[$user->tier] ?? 0;
        $key = "api_{$endpoint}_{$user->id}_" . date('YmdH'); // Hourly bucket

        return probability($probability, $key);
    }
}
```

## 🧪 Testing

The library includes a comprehensive Pest test suite covering edge cases, statistical correctness, and deterministic behavior.

```bash
# Install dev dependencies
composer install

# Run tests
composer test
# or
./vendor/bin/pest

# Run with coverage (requires Xdebug or PCOV)
./vendor/bin/pest --coverage
```

Test coverage includes:
- Edge cases (0.0, 1.0, epsilon boundaries)
- Input validation and error handling
- Deterministic key behavior
- Statistical correctness over large sample sizes
- Hash collision handling
- Type coercion

## ⚡ Performance

Benchmarks on PHP 8.4 (Apple M4):

| Operation | Time per call | Ops/sec |
|-----------|--------------|---------|
| Random (no key) | ~0.14 μs | ~7.0M |
| Deterministic (with key) | ~0.16 μs | ~6.2M |

**Memory usage:** 0 bytes (no allocations)

The library is optimized for high-throughput scenarios:
- Fast-path optimization for edge cases (0.0, 1.0)
- Minimal function calls
- No object instantiation
- CRC32 is faster than other hash functions

Run `php benchmark.php` to test performance on your hardware.

## ❓ FAQ / Troubleshooting

### Why do I get different results in different environments?

**Q:** Same key returns different results on different servers.

**A:** This is expected! CRC32 implementation is consistent, but you might be using different keys. Ensure you're using the exact same key string across environments.

```php
// ❌ This will differ between users
probability(0.5, $userId); // If $userId is different

// ✅ This will be consistent for same user
probability(0.5, "feature_x_user_{$userId}");
```

### Why is my A/B test showing 52% instead of 50%?

**Q:** I'm using `probability(0.5, $userId)` but getting uneven distribution.

**A:** With small sample sizes, variance is normal. The distribution converges to 50% with larger samples (law of large numbers). For 100 users, expect 45-55%. For 10,000 users, expect 49-51%.

### Can I use this for cryptographic purposes?

**Q:** Is this secure for generating random tokens?

**A:** **No!** This library is NOT cryptographically secure. CRC32 is predictable and `mt_rand()` is not suitable for security. Use `random_bytes()` or `random_int()` for security purposes.

### How do I gradually increase rollout percentage?

**Q:** I want to go from 10% to 50% to 100%.

**A:** Just change the probability value in your code/config. Users in the 0-10% hash range stay enabled, users in 10-50% get added, etc.

```php
// Week 1: 10% rollout
if (probability(0.1, "feature_x_user_{$userId}")) { ... }

// Week 2: 50% rollout (includes original 10%)
if (probability(0.5, "feature_x_user_{$userId}")) { ... }

// Week 3: 100% rollout
if (probability(1.0, "feature_x_user_{$userId}")) { ... }
```

### What about hash collisions?

**Q:** Can different keys produce the same result?

**A:** Yes, CRC32 has only 2³² (~4.3 billion) possible values. With many keys, collisions are possible but rare for typical use cases. For most applications this is acceptable. If you need collision-resistant hashing, fork and replace CRC32 with MD5 or SHA256.

### Why not use a database for feature flags?

**Q:** Isn't a feature flag service better?

**A:** Depends on your needs:

- **Use this library:** Simple rollouts, performance sampling, no persistence needed, minimal dependencies
- **Use feature flag service:** Complex targeting, runtime changes, analytics, team collaboration

This library excels at simplicity and performance, not flexibility.

