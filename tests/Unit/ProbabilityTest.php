<?php

// No declare(strict_types=1) on purpose: the "numeric coercion" suite below
// exercises how probability() behaves for coercive callers (int, bool and
// numeric-string arguments), which strict types would reject outright.

use function Antikirra\probability;

/**
 * Re-implements the documented hash-to-probability contract independently
 * of the library, so tests can assert exact agreement instead of trusting it.
 */
function expectedDeterministic(string $key): float
{
    $hash = crc32($key);

    if ($hash < 0) {
        $hash += 4294967296;
    }

    return $hash / 4294967296;
}

describe('probability function', function () {
    describe('exact boundary values', function () {
        it('always returns false for 0.0', function () {
            for ($i = 0; $i < 2000; $i++) {
                expect(probability(0.0))->toBeFalse();
            }
        });

        it('always returns true for 1.0', function () {
            for ($i = 0; $i < 2000; $i++) {
                expect(probability(1.0))->toBeTrue();
            }
        });

        it('returns false for 0.0 regardless of the key', function (string $key) {
            expect(probability(0.0, $key))->toBeFalse();
        })->with([
            'empty' => '',
            'zero-like' => '0',
            'word' => 'whatever',
            'binary' => "\x00\xff",
        ]);

        it('returns true for 1.0 regardless of the key', function (string $key) {
            expect(probability(1.0, $key))->toBeTrue();
        })->with([
            'empty' => '',
            'zero-like' => '0',
            'word' => 'whatever',
            'binary' => "\x00\xff",
        ]);

        it('treats negative zero as 0.0', function () {
            expect(probability(-0.0))->toBeFalse();
        });

        it('always returns a strict boolean', function () {
            expect(probability(0.0))->toBeBool()
                ->and(probability(1.0))->toBeBool()
                ->and(probability(0.5))->toBeBool()
                ->and(probability(0.5, 'key'))->toBeBool()
                ->and(probability(0.000001, 'key'))->toBeBool();
        });
    });

    describe('numeric coercion', function () {
        it('coerces integers', function () {
            expect(probability(0))->toBeFalse()
                ->and(probability(1))->toBeTrue();
        });

        it('coerces well-formed numeric strings', function () {
            expect(probability('0'))->toBeFalse()
                ->and(probability('1'))->toBeTrue()
                ->and(probability('0.5', 'k'))->toBe(probability(0.5, 'k'))
                ->and(probability('1e-3', 'k'))->toBe(probability(0.001, 'k'));
        });

        it('coerces booleans through the float type declaration', function () {
            expect(probability(true))->toBeTrue()
                ->and(probability(false))->toBeFalse();
        });
    });

    describe('rejects out-of-range probabilities', function () {
        it('throws for anything below 0.0', function (float $probability) {
            expect(fn () => probability($probability))
                ->toThrow(InvalidArgumentException::class, 'Probability must be between 0.0 and 1.0');
        })->with([
            'minus epsilon' => -PHP_FLOAT_EPSILON,
            'tiny negative' => -1.0E-12,
            'small negative' => -0.1,
            'minus one' => -1.0,
            'far negative' => -100000.0,
        ]);

        it('throws for anything above 1.0', function (float $probability) {
            expect(fn () => probability($probability))
                ->toThrow(InvalidArgumentException::class, 'Probability must be between 0.0 and 1.0');
        })->with([
            'next after one' => 1.0 + PHP_FLOAT_EPSILON,
            'slightly over' => 1.0000001,
            'over one' => 1.1,
            'far over' => 100000.0,
        ]);

        it('includes the offending value in the message', function () {
            expect(fn () => probability(1.5))
                ->toThrow(InvalidArgumentException::class, 'got: 1.5')
                ->and(fn () => probability(-0.25))
                ->toThrow(InvalidArgumentException::class, 'got: -0.25');
        });

        it('rejects out-of-range values even when a key is supplied', function (float $probability) {
            expect(fn () => probability($probability, 'feature-key'))
                ->toThrow(InvalidArgumentException::class, 'Probability must be between 0.0 and 1.0');
        })->with([
            'negative' => -0.5,
            'above one' => 1.5,
            'positive infinity' => INF,
            'negative infinity' => -INF,
        ]);
    });

    describe('rejects NAN and infinities', function () {
        it('throws for every form of NAN', function (float $nan) {
            expect(fn () => probability($nan))
                ->toThrow(InvalidArgumentException::class, 'NAN');
        })->with([
            'constant' => NAN,
            'inf minus inf' => INF - INF,
            'inf times zero' => INF * 0.0,
            'negated nan' => -NAN,
        ]);

        it('throws for infinities', function (float $infinity) {
            expect(fn () => probability($infinity))
                ->toThrow(InvalidArgumentException::class, 'Probability must be between 0.0 and 1.0');
        })->with([
            'positive' => INF,
            'negative' => -INF,
            'float overflow' => 1.0E309,
        ]);

        it('throws for NAN even when a key is supplied', function () {
            expect(fn () => probability(NAN, 'feature-key'))
                ->toThrow(InvalidArgumentException::class, 'got: NAN');
        });
    });

    describe('rejects non-numeric and structural types', function () {
        it('throws a TypeError', function ($value) {
            expect(fn () => probability($value))->toThrow(TypeError::class);
        })->with([
            'plain word' => 'abc',
            'sentence' => 'hello world',
            'hex string' => '0x1A',
            'empty string' => '',
            'nan word' => 'NaN',
            'null' => null,
            'list' => [[1, 2, 3]],
            'object' => new stdClass(),
        ]);
    });

    describe('valid extreme probabilities', function () {
        it('accepts the smallest representable positive values without throwing', function () {
            expect(probability(PHP_FLOAT_MIN))->toBeBool()
                ->and(probability(4.9E-324))->toBeBool()
                ->and(probability(PHP_FLOAT_MIN, 'tiny-key'))->toBeBool()
                ->and(probability(4.9E-324, 'tiny-key'))->toBeBool();
        });

        it('keeps a deterministic key off below the hash resolution', function () {
            // hash / 2^32 < PHP_FLOAT_MIN can hold only for a hash of 0, so any
            // key that does not collide to 0 must stay off at such a tiny p.
            for ($i = 0; $i < 250; $i++) {
                $key = "sub-resolution-key-$i";

                if (crc32($key) === 0) {
                    continue; // astronomically unlikely — keep the assertion exact
                }

                expect(probability(PHP_FLOAT_MIN, $key))->toBeFalse()
                    ->and(probability(4.9E-324, $key))->toBeFalse();
            }
        });

        it('always fires just below 1.0, with or without a key', function () {
            // The largest normalized values — mt_rand()/2^31 (1 - 2^-31) and
            // crc32/2^32 (1 - 2^-32) — both sit below 1 - PHP_FLOAT_EPSILON
            // (1 - 2^-52), so the event is certain at that threshold.
            $threshold = 1.0 - PHP_FLOAT_EPSILON;

            for ($i = 0; $i < 500; $i++) {
                expect(probability($threshold))->toBeTrue()
                    ->and(probability($threshold, "near-one-key-$i"))->toBeTrue();
            }
        });

        it('evaluates a sub-epsilon probability deterministically per key', function () {
            $key = 'extreme-low-key';
            $first = probability(1.0E-9, $key);

            for ($i = 0; $i < 100; $i++) {
                expect(probability(1.0E-9, $key))->toBe($first);
            }
        });

        it('produces both outcomes for a sub-permille probability across keys', function () {
            $seen = [];

            for ($i = 0; $i < 300000; $i++) {
                $seen[probability(0.001, "rare-key-$i") ? 1 : 0] = true;
            }

            expect($seen)->toHaveKey(0)->toHaveKey(1);
        });
    });

    describe('deterministic contract', function () {
        it('exactly matches the documented hash-to-probability formula', function () {
            $thresholds = [0.001, 0.137, 0.5, 0.500001, 0.863, 0.999];

            for ($i = 0; $i < 5000; $i++) {
                $key = "contract-key-$i";
                $normalized = expectedDeterministic($key);

                foreach ($thresholds as $probability) {
                    expect(probability($probability, $key))->toBe($normalized < $probability);
                }
            }
        });

        it('normalizes the hash against 2^32, not the largest hash', function () {
            // The divisor must be the bucket count (2^32), never the maximum
            // hash (2^32 - 1). Those two normalized values differ by roughly
            // hash / 2^64 — thousands of ULPs — so a threshold a hair above
            // hash / 2^32 flips the result only for the correct divisor.
            for ($i = 0; $i < 500; $i++) {
                $key = "divisor-key-$i";
                $normalized = crc32($key) / 4294967296;

                if ($normalized < 0.001 || $normalized > 0.999) {
                    continue;
                }

                $justAbove = $normalized * (1 + 1.0E-12);

                expect($justAbove)->toBeGreaterThan($normalized)
                    ->and(probability($justAbove, $key))->toBeTrue();
            }
        });

        it('uses a strict comparison: a key is off exactly at its own normalized value', function () {
            for ($i = 0; $i < 300; $i++) {
                $key = "flip-key-$i";
                $threshold = expectedDeterministic($key);

                if ($threshold > 0.0 && $threshold < 1.0) {
                    expect(probability($threshold, $key))->toBeFalse();
                }

                if ($threshold > 0.0 && $threshold < 0.999999) {
                    expect(probability($threshold + 1.0E-6, $key))->toBeTrue();
                }
            }
        });

        it('stays stable when interleaved with random and failing calls', function () {
            $key = 'interleaved-key';
            $baseline = probability(0.5, $key);

            for ($i = 0; $i < 250; $i++) {
                probability(0.5);
                probability(0.31, "noise-key-$i");

                try {
                    probability(2.0);
                } catch (InvalidArgumentException $e) {
                    // expected
                }

                expect(probability(0.5, $key))->toBe($baseline);
            }
        });

        it('ignores the global mt_rand seed', function () {
            mt_srand(1);
            $a = probability(0.5, 'seed-independent-key');
            mt_srand(987654321);
            $b = probability(0.5, 'seed-independent-key');
            mt_srand(42);
            $c = probability(0.5, 'seed-independent-key');

            expect($a)->toBe($b)->and($b)->toBe($c);

            mt_srand();
        });

        it('stays consistent for hostile keys', function (string $key) {
            $first = probability(0.5, $key);

            for ($i = 0; $i < 25; $i++) {
                expect(probability(0.5, $key))->toBe($first);
            }
        })->with([
            'one megabyte' => str_repeat('x', 1000000),
            'all byte values' => implode('', array_map('chr', range(0, 255))),
            'null bytes' => "\x00\x00\x00",
            'unicode' => 'ключ-測試-🎲',
            'whitespace only' => "   \t\n  ",
            'zero string' => '0',
            'zero float string' => '0.0',
            'single char' => 'x',
            'newline' => "\n",
        ]);

        it('coerces an integer key exactly like its string form', function () {
            expect(probability(0.5, 99999))->toBe(probability(0.5, '99999'))
                ->and(probability(0.5, 0))->toBe(probability(0.5, '0'));
        });

        it('produces both outcomes across many keys', function () {
            $results = [];

            for ($i = 0; $i < 1000; $i++) {
                $results[] = probability(0.5, "spread-key-$i");
            }

            expect(array_unique($results))->toHaveCount(2);
        });
    });

    describe('random behavior without a key', function () {
        it('produces both outcomes for an empty key', function () {
            $results = [];

            for ($i = 0; $i < 2000; $i++) {
                $results[] = probability(0.5);
            }

            expect(array_unique($results))->toHaveCount(2);
        });

        it('treats an explicit empty string exactly like an omitted key', function () {
            $results = [];

            for ($i = 0; $i < 2000; $i++) {
                $results[] = probability(0.5, '');
            }

            expect(array_unique($results))->toHaveCount(2);
        });

        it('is driven by mt_rand and reproducible under a fixed seed', function () {
            mt_srand(20260517);
            $first = [];
            for ($i = 0; $i < 1000; $i++) {
                $first[] = probability(0.5);
            }

            mt_srand(20260517);
            $second = [];
            for ($i = 0; $i < 1000; $i++) {
                $second[] = probability(0.5);
            }

            expect($second)->toBe($first)
                ->and(array_unique($first))->toHaveCount(2);

            mt_srand();
        });
    });

    describe('distribution correctness', function () {
        it('tracks the threshold across the whole range for random calls', function () {
            $samples = 25000;

            for ($percent = 5; $percent <= 95; $percent += 5) {
                $threshold = $percent / 100;
                $hits = 0;

                for ($i = 0; $i < $samples; $i++) {
                    if (probability($threshold)) {
                        $hits++;
                    }
                }

                expect(abs($hits / $samples - $threshold))->toBeLessThan(0.02);
            }
        });

        it('tracks the threshold across the whole range for deterministic keys', function () {
            $samples = 25000;

            for ($percent = 5; $percent <= 95; $percent += 5) {
                $threshold = $percent / 100;
                $hits = 0;

                for ($i = 0; $i < $samples; $i++) {
                    if (probability($threshold, "dist-key-$i")) {
                        $hits++;
                    }
                }

                expect(abs($hits / $samples - $threshold))->toBeLessThan(0.01);
            }
        });

        it('spreads deterministic keys uniformly across hash buckets', function () {
            $buckets = array_fill(0, 20, 0);

            for ($i = 0; $i < 100000; $i++) {
                $bucket = (int)(expectedDeterministic("bucket-key-$i") * 20);
                $buckets[min($bucket, 19)]++;
            }

            foreach ($buckets as $count) {
                // Each of 20 buckets expects ~5000; crc32 keeps every bucket
                // within ~30 of that, so a broken hash collapses the test hard.
                expect($count)->toBeGreaterThan(4850)->toBeLessThan(5150);
            }
        });
    });

    describe('monotonic gradual rollout', function () {
        it('never disables a key once a lower probability enabled it', function () {
            for ($i = 0; $i < 400; $i++) {
                $key = "rollout-key-$i";
                $enabled = false;

                for ($step = 1; $step < 1000; $step++) {
                    $on = probability($step / 1000, $key);

                    if ($enabled) {
                        expect($on)->toBeTrue();
                    }

                    $enabled = $enabled || $on;
                }
            }
        });
    });
});
