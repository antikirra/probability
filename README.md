# Make your code work spontaneously 🙃

![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/antikirra/probability/php)
![Packagist Version](https://img.shields.io/packagist/v/antikirra/probability)

## Install

```console
composer require antikirra/probability:^2
```

## Basic usage

There are two main strategies for using this mechanism. You can use `probability` function call to predictably
influence the probability of activating certain events. For example, when dealing with a high number of requests per
second, logging all of them can be costly. Instead, you can choose to collect only 15% (selected randomly) of the
incoming traffic to monitor the system's state.

```php
<?php

use function Antikirra\probability;

require __DIR__ . '/vendor/autoload.php';

if (probability(0.15)) {
    $logger->info($payload);
}
```

That's amazing! However, with this usage, we don't have guarantees of absolute stability. In other words, with a small
number of iterations, it may appear that the algorithm is not working correctly. In reality, by running synthetic tests,
you can see that for 1000 iterations, you may get 142, 153, or 165 positive activations. But there's no need to worry
because with a truly large number of repetitions, the uniform distribution will do its job. This fact should always be
kept in mind!

To achieve a balance between stable operation and a desired level of probability distribution, such as in the case of
conducting A/B testing for a new feature on your website, you can use a distinguishing user attribute, such as an
identifier or IP address, to set the required level of distribution and bind it to that parameter. On average, only 15%
of your website's audience will see the changes. When you make subsequent function calls for the same user, it will
return the same value in each subsequent iteration. This is very convenient because it allows you to avoid the need for
storing and rebalancing feature rollout lists.

```php
<?php

use function Antikirra\probability;

require __DIR__ . '/vendor/autoload.php';

if (probability(0.15, $_SERVER['REMOTE_ADDR'])) { // or probability(0.15, (string)$userId)
    $app->feature('#1337')->enable();
}
```

This example vividly illustrates the working principle of the mechanism, but it has a flaw in practice. The issue is
that the function's parameter combinations will always return the same value, and different features will be enabled for
the same users. To overcome this behavior, you will need to customize the key parameter, making it unique for each
specific feature. This way, each experiment will be randomly conducted on a different group of users.

```php
<?php

use function Antikirra\probability;

require __DIR__ . '/vendor/autoload.php';

if (probability(0.15, 'feature_#1337_for' . $_SERVER['REMOTE_ADDR'])) {
    $app->feature('#1337')->enable();
}

if (probability(0.15, 'feature_#2517_for' . $_SERVER['REMOTE_ADDR'])) {
    $app->feature('#2517')->enable();
}
```

Remember that significant results can only be observed with a truly large amount of data or over a prolonged period of
time. Use this approach wisely and assess the risks associated with its use. If you still have doubts, conduct simple
synthetic testing of this functionality to understand its mechanics.

Good Luck!
