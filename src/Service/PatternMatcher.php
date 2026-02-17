<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use function is_array;
use function is_string;

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
     *
     * @return bool True if the record matches (passes inclusion and doesn't match exclusion)
     */
    public function matches(array $record, array $includePatterns = [], array $excludePatterns = []): bool
    {
        // If exclusion patterns exist and match, exclude
        if (!empty($excludePatterns) && $this->matchesExcludePatterns($record, $excludePatterns)) {
            return false;
        }

        // If inclusion patterns exist, check if they match
        if (!empty($includePatterns)) {
            return $this->matchesIncludePatterns($record, $includePatterns);
        }

        // If no patterns, include by default
        return true;
    }

    /**
     * Returns true if the record matches exclusion (exclude when any config matches).
     *
     * @param array<string, mixed> $record
     * @param array<array<int, array<string, array<string>|string>>|array<string>|string> $excludePatterns Single AND-clause or list of AND-clauses (OR between them)
     */
    private function matchesExcludePatterns(array $record, array $excludePatterns): bool
    {
        if ($this->isListOfPatternSets($excludePatterns)) {
            foreach ($excludePatterns as $set) {
                if ($this->matchesPatterns($record, $set)) {
                    return true;
                }
            }

            return false;
        }

        return $this->matchesPatterns($record, $excludePatterns);
    }

    /**
     * Returns true if the record matches inclusion (include when any config matches, when using list of sets).
     *
     * @param array<string, mixed> $record
     * @param array<array<int, array<string, array<string>|string>>|array<string>|string> $includePatterns Single AND-clause or list of AND-clauses (OR between them)
     */
    private function matchesIncludePatterns(array $record, array $includePatterns): bool
    {
        if ($this->isListOfPatternSets($includePatterns)) {
            foreach ($includePatterns as $set) {
                if ($this->matchesPatterns($record, $set)) {
                    return true;
                }
            }

            return false;
        }

        return $this->matchesPatterns($record, $includePatterns);
    }

    /**
     * True if the array is a list of pattern sets (OR between configs), e.g. [ ['role'=>'admin'], ['status'=>'deleted'] ].
     */
    private function isListOfPatternSets(array $patterns): bool
    {
        if ($patterns === [] || !array_is_list($patterns)) {
            return false;
        }
        foreach ($patterns as $item) {
            if (!is_array($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a record matches all given patterns.
     *
     * Supports comparison operators: >, >=, <, <=, =, !=, <>, and SQL LIKE patterns (%).
     * Pattern values can be a string or an array of strings (OR: match if any option matches).
     * All patterns must match for the method to return true.
     *
     * @param array<string, mixed> $record The record to check
     * @param array<array<string>|string> $patterns Array of patterns (e.g., ['id' => '>100', 'status' => 'active', 'email' => ['%@a.com', 'b@b.com']])
     *
     * @return bool True if all patterns match
     */
    private function matchesPatterns(array $record, array $patterns): bool
    {
        foreach ($patterns as $field => $pattern) {
            // Normalize: allow value to be array of options (OR)
            $options      = is_array($pattern) ? $pattern : [$pattern];
            $fieldMatched = false;

            foreach ($options as $option) {
                $option = is_string($option) ? $option : (string) $option;
                if ($this->patternMatchesValue($record, $field, $option)) {
                    $fieldMatched = true;
                    break;
                }
            }

            if (!$fieldMatched) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the value of a field in the record matches a single pattern.
     *
     * @param array<string, mixed> $record The record to check
     * @param string $field The field name (supports dot notation)
     * @param string $pattern Single pattern string
     *
     * @return bool True if the field value matches the pattern
     */
    private function patternMatchesValue(array $record, string $field, string $pattern): bool
    {
        $value = $this->getNestedValue($record, $field);

        if ($value === null && !isset($record[$field])) {
            return false;
        }

        // Handle comparison operators
        if (str_starts_with($pattern, '>=')) {
            $threshold = (float) substr($pattern, 2);

            return $value >= $threshold;
        }
        if (str_starts_with($pattern, '<=')) {
            $threshold = (float) substr($pattern, 2);

            return $value <= $threshold;
        }
        if (str_starts_with($pattern, '>')) {
            $threshold = (float) substr($pattern, 1);

            return $value > $threshold;
        }
        if (str_starts_with($pattern, '<')) {
            $threshold = (float) substr($pattern, 1);

            return $value < $threshold;
        }
        if (str_starts_with($pattern, '!=') || str_starts_with($pattern, '<>')) {
            $expected = substr($pattern, 2);

            return $value != $expected;
        }
        if (str_starts_with($pattern, '=')) {
            $expected = substr($pattern, 1);

            return $value == $expected;
        }

        // Exact match, contains, or multiple values with | (OR)
        if (is_string($value) && is_string($pattern)) {
            if (str_contains($pattern, '%')) {
                $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';

                return (bool) preg_match($regex, $value);
            }
            if (str_contains($pattern, '|')) {
                $orOptions = explode('|', $pattern);
                foreach ($orOptions as $option) {
                    $option = trim($option);
                    if (str_contains($option, '%')) {
                        $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($option, '/')) . '$/i';
                        if (preg_match($regex, $value)) {
                            return true;
                        }
                    } elseif (str_contains($value, $option) || $value === $option) {
                        return true;
                    }
                }

                return false;
            }

            return $value === $pattern;
        }

        return $value == $pattern;
    }

    /**
     * Gets a nested value from a record, supporting dot notation for relationships.
     *
     * @param array<string, mixed> $record The record to get value from
     * @param string $field The field name, optionally with dot notation (e.g., 'type.name')
     *
     * @return mixed The value or null if not found
     */
    private function getNestedValue(array $record, string $field): mixed
    {
        // Direct field access (e.g., 'id', 'status')
        if (isset($record[$field])) {
            return $record[$field];
        }

        // Relationship field access (e.g., 'type.name')
        if (str_contains($field, '.')) {
            $parts           = explode('.', $field, 2);
            $associationName = $parts[0];
            $relatedField    = $parts[1];

            // Check if we have the relationship field directly (from JOIN)
            if (isset($record[$field])) {
                return $record[$field];
            }

            // Try to get from nested structure (if entity was loaded with relationship)
            if (isset($record[$associationName]) && is_array($record[$associationName])) {
                return $record[$associationName][$relatedField] ?? null;
            }
        }

        return null;
    }
}
