<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Service;

use Nowo\AnonymizeBundle\Service\PatternMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Test case for PatternMatcher.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class PatternMatcherTest extends TestCase
{
    private PatternMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new PatternMatcher();
    }

    /**
     * Test that records match when no patterns are provided.
     */
    public function testMatchesWithNoPatterns(): void
    {
        $record = ['id' => 1, 'name' => 'John'];
        $this->assertTrue($this->matcher->matches($record));
    }

    /**
     * Test that records match inclusion patterns.
     */
    public function testMatchesWithInclusionPatterns(): void
    {
        $record = ['id' => 100, 'status' => 'active'];
        $includePatterns = ['id' => '>50', 'status' => 'active'];

        $this->assertTrue($this->matcher->matches($record, $includePatterns));
    }

    /**
     * Test that records don't match when inclusion patterns fail.
     */
    public function testMatchesFailsWithInclusionPatterns(): void
    {
        $record = ['id' => 50, 'status' => 'active'];
        $includePatterns = ['id' => '>50'];

        $this->assertFalse($this->matcher->matches($record, $includePatterns));
    }

    /**
     * Test that exclusion patterns take precedence.
     */
    public function testMatchesWithExclusionPatterns(): void
    {
        $record = ['id' => 50, 'status' => 'active'];
        $includePatterns = ['id' => '>10'];
        $excludePatterns = ['id' => '<=100'];

        $this->assertFalse($this->matcher->matches($record, $includePatterns, $excludePatterns));
    }

    /**
     * Test comparison operators: greater than.
     */
    public function testMatchesWithGreaterThan(): void
    {
        $record = ['id' => 100];
        $this->assertTrue($this->matcher->matches($record, ['id' => '>50']));
        $this->assertFalse($this->matcher->matches($record, ['id' => '>150']));
    }

    /**
     * Test comparison operators: greater than or equal.
     */
    public function testMatchesWithGreaterThanOrEqual(): void
    {
        $record = ['id' => 100];
        $this->assertTrue($this->matcher->matches($record, ['id' => '>=100']));
        $this->assertTrue($this->matcher->matches($record, ['id' => '>=50']));
        $this->assertFalse($this->matcher->matches($record, ['id' => '>=150']));
    }

    /**
     * Test comparison operators: less than.
     */
    public function testMatchesWithLessThan(): void
    {
        $record = ['id' => 50];
        $this->assertTrue($this->matcher->matches($record, ['id' => '<100']));
        $this->assertFalse($this->matcher->matches($record, ['id' => '<30']));
    }

    /**
     * Test comparison operators: less than or equal.
     */
    public function testMatchesWithLessThanOrEqual(): void
    {
        $record = ['id' => 100];
        $this->assertTrue($this->matcher->matches($record, ['id' => '<=100']));
        $this->assertTrue($this->matcher->matches($record, ['id' => '<=150']));
        $this->assertFalse($this->matcher->matches($record, ['id' => '<=50']));
    }

    /**
     * Test comparison operators: equals.
     */
    public function testMatchesWithEquals(): void
    {
        $record = ['status' => 'active'];
        $this->assertTrue($this->matcher->matches($record, ['status' => '=active']));
        $this->assertFalse($this->matcher->matches($record, ['status' => '=inactive']));
    }

    /**
     * Test comparison operators: not equals.
     */
    public function testMatchesWithNotEquals(): void
    {
        $record = ['status' => 'active'];
        $this->assertTrue($this->matcher->matches($record, ['status' => '!=inactive']));
        $this->assertFalse($this->matcher->matches($record, ['status' => '!=active']));
    }

    /**
     * Test SQL LIKE pattern matching.
     */
    public function testMatchesWithLikePattern(): void
    {
        $record = ['email' => 'john.doe@example.com'];
        $this->assertTrue($this->matcher->matches($record, ['email' => '%@example.com']));
        $this->assertTrue($this->matcher->matches($record, ['email' => 'john.%']));
        $this->assertFalse($this->matcher->matches($record, ['email' => '%@other.com']));
    }

    /**
     * Test exact string matching.
     */
    public function testMatchesWithExactString(): void
    {
        $record = ['name' => 'John'];
        $this->assertTrue($this->matcher->matches($record, ['name' => 'John']));
        $this->assertFalse($this->matcher->matches($record, ['name' => 'Jane']));
    }

    /**
     * Test that missing fields cause match to fail.
     */
    public function testMatchesFailsWithMissingField(): void
    {
        $record = ['id' => 100];
        $this->assertFalse($this->matcher->matches($record, ['id' => '>50', 'status' => 'active']));
    }

    /**
     * Test multiple inclusion patterns (all must match).
     */
    public function testMatchesWithMultipleInclusionPatterns(): void
    {
        $record = ['id' => 100, 'status' => 'active', 'age' => 30];
        $includePatterns = ['id' => '>50', 'status' => 'active', 'age' => '>=18'];

        $this->assertTrue($this->matcher->matches($record, $includePatterns));

        $record2 = ['id' => 100, 'status' => 'inactive', 'age' => 30];
        $this->assertFalse($this->matcher->matches($record2, $includePatterns));
    }
}
