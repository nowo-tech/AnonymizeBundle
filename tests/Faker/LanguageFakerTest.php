<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\LanguageFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for LanguageFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class LanguageFakerTest extends TestCase
{
    /**
     * Test that LanguageFaker generates a valid language code.
     */
    public function testGenerate(): void
    {
        $faker = new LanguageFaker('en_US');
        $language = $faker->generate();

        $this->assertIsString($language);
        $this->assertNotEmpty($language);
    }

    /**
     * Test that LanguageFaker generates code format.
     */
    public function testGenerateCode(): void
    {
        $faker = new LanguageFaker('en_US');
        $language = $faker->generate(['format' => 'code']);

        $this->assertIsString($language);
        $this->assertNotEmpty($language);
    }

    /**
     * Test that LanguageFaker generates name format.
     */
    public function testGenerateName(): void
    {
        $faker = new LanguageFaker('en_US');
        $language = $faker->generate(['format' => 'name']);

        $this->assertIsString($language);
        $this->assertNotEmpty($language);
    }
}
