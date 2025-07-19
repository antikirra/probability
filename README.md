# Make your code work spontaneously 🙃

![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/antikirra/probability/php)
![Packagist Version](https://img.shields.io/packagist/v/antikirra/probability)

A lightweight PHP library for probabilistic code execution and deterministic feature distribution. Perfect for A/B testing, gradual feature rollouts, performance sampling, and controlled chaos engineering.

## Install

```console
composer require antikirra/probability:^2
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
When called without a key, uses PHP's random number generator for true randomness:

```php
probability(0.25); // 25% chance, different result each time
```

### 2. Deterministic (With Key)
When provided with a key, uses a hash-based approach for consistent results:

```php
probability(0.25, 'unique_key'); // Same result for same key
```

The deterministic approach ensures:
- Same input always produces same output
- Uniform distribution across large datasets
- No need for external storage or coordination

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
