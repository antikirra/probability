<?php

describe('probability function', function () {
    describe('edge cases', function () {
        it('always returns false for probability 0.0', function () {
            // Run multiple times to ensure consistency
            for ($i = 0; $i < 1000; $i++) {
                expect(Antikirra\probability(0.0))->toBeFalse();
            }
        });

        it('always returns true for probability 1.0', function () {
            // Run multiple times to ensure consistency
            for ($i = 0; $i < 1000; $i++) {
                expect(Antikirra\probability(1.0))->toBeTrue();
            }
        });

        it('returns false for probabilities below epsilon lower bound', function (float $probability) {
            expect(Antikirra\probability($probability))->toBeFalse();
        })->with([
            'zero' => 0.0,
            'very small positive' => 0.0000001,
            'exact epsilon lower' => Antikirra\EPSILON_LOWER,
        ]);

        it('returns true for probabilities above epsilon upper bound', function (float $probability) {
            expect(Antikirra\probability($probability))->toBeTrue();
        })->with([
            'one' => 1.0,
            'very close to one' => 0.9999999,
            'exact epsilon upper' => Antikirra\EPSILON_UPPER,
        ]);
    });

    describe('input validation', function () {
        it('throws exception for negative probabilities', function (float $probability) {
            expect(fn () => Antikirra\probability($probability))
                ->toThrow(InvalidArgumentException::class, 'Probability must be between 0.0 and 1.0');
        })->with([
            'negative small' => -0.1,
            'negative large' => -1.0,
            'very negative' => -100.0,
        ]);

        it('throws exception for probabilities greater than 1.0', function (float $probability) {
            expect(fn () => Antikirra\probability($probability))
                ->toThrow(InvalidArgumentException::class, 'Probability must be between 0.0 and 1.0');
        })->with([
            'slightly over' => 1.1,
            'double' => 2.0,
            'very large' => 100.0,
        ]);

        it('includes the invalid value in exception message', function () {
            expect(fn () => Antikirra\probability(1.5))
                ->toThrow(InvalidArgumentException::class, 'got: 1.5');
        });
    });

    describe('deterministic behavior with keys', function () {
        it('returns same result for same key', function () {
            $key = 'test-key-123';
            $probability = 0.5;

            $result1 = Antikirra\probability($probability, $key);
            $result2 = Antikirra\probability($probability, $key);
            $result3 = Antikirra\probability($probability, $key);

            expect($result1)
                ->toBe($result2)
                ->and($result2)->toBe($result3);
        });

        it('produces consistent results across multiple keys', function (string $key) {
            $probability = 0.5;

            // Same key should produce same result every time
            $results = [];
            for ($i = 0; $i < 10; $i++) {
                $results[] = Antikirra\probability($probability, $key);
            }

            // All results should be identical
            expect($results)->each->toBe($results[0]);
        })->with([
            'numeric key' => '12345',
            'alphabetic key' => 'abcdef',
            'mixed key' => 'user-id-42',
            'special chars' => 'key!@#$%',
        ]);

        it('may produce different results for different keys with same probability', function () {
            $probability = 0.5;
            $results = [];

            // Collect results for many different keys
            for ($i = 0; $i < 1000; $i++) {
                $results[] = Antikirra\probability($probability, "key-$i");
            }

            // We should have both true and false in results
            // (statistically almost certain with 100 keys at p=0.5)
            $uniqueResults = array_unique($results);
            expect($uniqueResults)->toHaveCount(2);
        });

        it('produces random results when key is empty', function () {
            $probability = 0.5;
            $results = [];

            // Collect results for empty key calls
            for ($i = 0; $i < 1000; $i++) {
                $results[] = Antikirra\probability($probability);
            }

            // Should have both true and false (statistically almost certain)
            $uniqueResults = array_unique($results);
            expect($uniqueResults)->toHaveCount(2);
        });
    });

    describe('statistical correctness', function () {
        it('approximates expected probability over many iterations', function (float $probability) {
            $iterations = 10000;
            $trueCount = 0;

            for ($i = 0; $i < $iterations; $i++) {
                if (Antikirra\probability($probability)) {
                    $trueCount++;
                }
            }

            $actualProbability = $trueCount / $iterations;
            $tolerance = 0.02; // 2% tolerance

            expect($actualProbability)
                ->toBeGreaterThanOrEqual($probability - $tolerance)
                ->toBeLessThanOrEqual($probability + $tolerance);
        })->with([
            'low probability' => 0.1,
            'medium low' => 0.25,
            'half' => 0.5,
            'medium high' => 0.75,
            'high probability' => 0.9,
        ]);

        it('distributes deterministic keys uniformly', function () {
            $probability = 0.5;
            $iterations = 10000;
            $trueCount = 0;

            for ($i = 0; $i < $iterations; $i++) {
                if (Antikirra\probability($probability, "unique-key-$i")) {
                    $trueCount++;
                }
            }

            $actualProbability = $trueCount / $iterations;
            $tolerance = 0.05; // 5% tolerance for deterministic distribution

            expect($actualProbability)
                ->toBeGreaterThanOrEqual($probability - $tolerance)
                ->toBeLessThanOrEqual($probability + $tolerance);
        });
    });

    describe('probability ranges', function () {
        it('handles various probability values correctly', function (float $probability, ?bool $shouldAlwaysMatch) {
            if ($shouldAlwaysMatch !== null) {
                // For extreme values, check they always return expected result
                for ($i = 0; $i < 100; $i++) {
                    expect(Antikirra\probability($probability))->toBe($shouldAlwaysMatch);
                }
            } else {
                // For middle values, just verify no exceptions are thrown
                expect(fn () => Antikirra\probability($probability))->not->toThrow(Exception::class);
            }
        })->with([
            'minimum' => [0.0, false],
            'very low' => [0.001, null],
            'low' => [0.1, null],
            'quarter' => [0.25, null],
            'third' => [0.33, null],
            'half' => [0.5, null],
            'two thirds' => [0.66, null],
            'three quarters' => [0.75, null],
            'high' => [0.9, null],
            'very high' => [0.999, null],
            'maximum' => [1.0, true],
        ]);
    });

    describe('hash collision handling', function () {
        it('handles keys that may produce crc32 collisions gracefully', function () {
            // These are different keys, but crc32 may produce similar hashes
            $keys = [
                'key1',
                'key2',
                'different',
                'another',
                'test',
            ];

            $probability = 0.5;

            // Each key should produce a consistent result
            foreach ($keys as $key) {
                $result1 = Antikirra\probability($probability, $key);
                $result2 = Antikirra\probability($probability, $key);

                expect($result1)->toBe($result2);
            }
        });
    });

    describe('type coercion', function () {
        it('accepts integer probabilities and coerces to float', function () {
            // Integer 0 should work like 0.0
            expect(Antikirra\probability(0))->toBeFalse();

            // Integer 1 should work like 1.0
            expect(Antikirra\probability(1))->toBeTrue();
        });
    });
});
