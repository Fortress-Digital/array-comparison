<?php

declare(strict_types=1);

namespace Fortress\ArrayComparison;

class ArrayComparison
{
    public static function init(): self
    {
        return new static();
    }

    public function getDiff(string|array $expected, string|array $actual): array
    {
        $expected = $this->jsonToArray($expected);
        $actual = $this->jsonToArray($actual);

        return $this->getArrayDiff($expected, $actual);
    }

    /**
     * If JSON is provided, convert it to an array
     */
    private function jsonToArray(string|array $array): array
    {
        if (is_string($array)) {
            $array = json_decode($array, true, 512, JSON_THROW_ON_ERROR);
        }

        return $array;
    }

    private function getArrayDiff(array $expected, array $actual): array
    {
        $diff = [];

        $added = $this->getAdded($expected, $actual);

        if (!empty($added)) {
            $diff['added'] = $added;
        }

        $removed = $this->getRemoved($expected, $actual);

        if (!empty($removed)) {
            $diff['removed'] = $removed;
        }

        $changed = $this->getChanged($expected, $actual);

        if (!empty($changed)) {
            $diff['changed'] = $changed;
        }

        return $diff;
    }

    /**
     * Compare the actual data to the expected to find any keys that have been added.
     *
     * Recursively run the same check on nested associative arrays.
     */
    private function getAdded(array $expected, array $actual): array
    {
        $diff = [];

        foreach ($actual as $actualKey => $actualValue) {
            if (!array_key_exists($actualKey, $expected)) {
                $diff[$actualKey] = $actualValue;
                continue;
            }

            # If this is a nested associative array, run the check over that array to get nested added fields
            if ($this->isNestedObjects($actualValue)) {
                $added = $this->getAdded($expected[$actualKey], $actualValue);

                if (!empty($added)) {
                    $diff[$actualKey] = $added;
                }
            }
        }

        return $diff;
    }

    /**
     * Compare the expected data to the actual to find any keys that have been removed.
     *
     * Recursively run the same check on nested associative arrays.
     */
    private function getRemoved(array $expected, array $actual): array
    {
        $diff = [];

        foreach ($expected as $expectedKey => $expectedValue) {
            if (!array_key_exists($expectedKey, $actual)) {
                $diff[$expectedKey] = $expectedValue;
                continue;
            }

            # If this is a nested associative array, run the check over that array to get nested removed fields
            if ($this->isNestedObjects($expectedValue)) {
                $removed = $this->getRemoved($expectedValue, $actual[$expectedKey]);

                if (!empty($removed)) {
                    $diff[$expectedKey] = $removed;
                }
            }
        }

        return $diff;
    }

    /**
     * Compare all existing keys for any difference in their values.
     *
     * Recursively run the same check on nested associative arrays.
     */
    private function getChanged(array $expected, array $actual): array
    {
        $diff = [];

        foreach ($expected as $expectedKey => $expectedValue) {
            if (array_key_exists($expectedKey, $actual)) {
                # If this is a nested associative array, run the check over that array to get nested changes
                if ($this->isNestedObjects($expectedValue)) {
                    $changed = $this->getChanged($expectedValue, $actual[$expectedKey]);

                    if (!empty($changed)) {
                        $diff[$expectedKey] = $changed;
                    }
                    continue;
                }

                if ($expectedValue !== $actual[$expectedKey]) {
                    # If it's not nested, then just return changed values
                    $edited = [
                        'old' => $expectedValue,
                        'new' => $actual[$expectedKey],
                    ];

                    $diff[$expectedKey] = $edited;
                }
            }
        }

        return $diff;
    }

    private function isNestedObjects(mixed $value): bool
    {
        if (is_array($value)) {
            if (!array_is_list($value)) {
                return true;
            }

            if (!empty($value) && is_array($value[0])) {
                return true;
            }
        }

        return false;
    }
}
