<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

/**
 * Service for matching records against inclusion/exclusion patterns.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class PatternMatcher
{
    /**
     * Checks if a record matches the given inclusion and exclusion patterns.
     *
     * Exclusion patterns take precedence over inclusion patterns. If exclusion patterns
     * match, the record is excluded. If only inclusion patterns are provided, the record
     * must match all inclusion patterns. If no patterns are provided, the record is included.
     *
     * @param array<string, mixed> $record The record to check
     * @param array<string> $includePatterns Array of patterns to include (e.g., ['id' => '>100', 'status' => 'active'])
     * @param array<string> $excludePatterns Array of patterns to exclude (e.g., ['id' => '<=100'])
     * @return bool True if the record matches (passes inclusion and doesn't match exclusion)
     */
    public function matches(array $record, array $includePatterns = [], array $excludePatterns = []): bool
    {
        // If exclusion patterns exist and match, exclude
        if (!empty($excludePatterns) && $this->matchesPatterns($record, $excludePatterns)) {
            return false;
        }

        // If inclusion patterns exist, check if they match
        if (!empty($includePatterns)) {
            return $this->matchesPatterns($record, $includePatterns);
        }

        // If no patterns, include by default
        return true;
    }

    /**
     * Checks if a record matches all given patterns.
     *
     * Supports comparison operators: >, >=, <, <=, =, !=, <>, and SQL LIKE patterns (%).
     * All patterns must match for the method to return true.
     *
     * @param array<string, mixed> $record The record to check
     * @param array<string> $patterns Array of patterns (e.g., ['id' => '>100', 'status' => 'active'])
     * @return bool True if all patterns match
     */
    private function matchesPatterns(array $record, array $patterns): bool
    {
        foreach ($patterns as $field => $pattern) {
            if (!isset($record[$field])) {
                return false;
            }

            $value = $record[$field];

            // Handle comparison operators
            if (str_starts_with($pattern, '>=')) {
                $threshold = (float) substr($pattern, 2);
                if (!($value >= $threshold)) {
                    return false;
                }
            } elseif (str_starts_with($pattern, '<=')) {
                $threshold = (float) substr($pattern, 2);
                if (!($value <= $threshold)) {
                    return false;
                }
            } elseif (str_starts_with($pattern, '>')) {
                $threshold = (float) substr($pattern, 1);
                if (!($value > $threshold)) {
                    return false;
                }
            } elseif (str_starts_with($pattern, '<')) {
                $threshold = (float) substr($pattern, 1);
                if (!($value < $threshold)) {
                    return false;
                }
            } elseif (str_starts_with($pattern, '!=') || str_starts_with($pattern, '<>')) {
                $expected = substr($pattern, 2);
                if ($value == $expected) {
                    return false;
                }
            } elseif (str_starts_with($pattern, '=')) {
                $expected = substr($pattern, 1);
                if ($value != $expected) {
                    return false;
                }
            } else {
                // Exact match, contains, or multiple values with | (OR)
                if (is_string($value) && is_string($pattern)) {
                    if (str_contains($pattern, '%')) {
                        // SQL LIKE pattern
                        $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';
                        if (!preg_match($regex, $value)) {
                            return false;
                        }
                    } elseif (str_contains($pattern, '|')) {
                        // Multiple values with OR operator (e.g., 'value1|value2|value3')
                        $options = explode('|', $pattern);
                        $matched = false;
                        foreach ($options as $option) {
                            $option = trim($option);
                            if (str_contains($option, '%')) {
                                // SQL LIKE pattern within OR
                                $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($option, '/')) . '$/i';
                                if (preg_match($regex, $value)) {
                                    $matched = true;
                                    break;
                                }
                            } elseif (str_contains($value, $option)) {
                                // Contains check (useful for email domains)
                                $matched = true;
                                break;
                            } elseif ($value === $option) {
                                // Exact match
                                $matched = true;
                                break;
                            }
                        }
                        if (!$matched) {
                            return false;
                        }
                    } elseif ($value !== $pattern) {
                        return false;
                    }
                } elseif ($value != $pattern) {
                    return false;
                }
            }
        }

        return true;
    }
}
