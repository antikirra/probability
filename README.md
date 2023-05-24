# Make your code work spontaneously 🙃

## Install

```console
composer require antikirra/probability
```

## Basic usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

// only if the function has not been defined in the global scope
probability(0.1);

// if the function could not be defined globally
\Antikirra\probability(0.1);

// under the hood
\Antikirra\Probability\Probability::probability(0.2);
```

## Demo

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$test = [
    'yes' => 0,
    'no' => 0
];

for ($i = 0; $i < 100000; $i++) {
    if (probability(0.75)) {
        $test['yes']++;
    } else {
        $test['no']++;
    }
}

// Array
// (
//     [yes] => 75181
//     [no] => 24819
// )
```
