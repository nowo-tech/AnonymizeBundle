<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\CompanyFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for CompanyFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class CompanyFakerTest extends TestCase
{
    /**
     * Test that CompanyFaker generates a valid company name.
     */
    public function testGenerate(): void
    {
        $faker = new CompanyFaker('en_US');
        $company = $faker->generate();

        $this->assertIsString($company);
        $this->assertNotEmpty($company);
    }

    /**
     * Test that CompanyFaker respects type option (corporation).
     */
    public function testGenerateWithTypeCorporation(): void
    {
        $faker = new CompanyFaker('en_US');
        $company = $faker->generate(['type' => 'corporation']);

        $this->assertIsString($company);
        $this->assertStringEndsWith('Corp.', $company);
    }

    /**
     * Test that CompanyFaker respects type option (llc).
     */
    public function testGenerateWithTypeLlc(): void
    {
        $faker = new CompanyFaker('en_US');
        $company = $faker->generate(['type' => 'llc']);

        $this->assertIsString($company);
        $this->assertStringEndsWith('LLC', $company);
    }

    /**
     * Test that CompanyFaker respects type option (inc).
     */
    public function testGenerateWithTypeInc(): void
    {
        $faker = new CompanyFaker('en_US');
        $company = $faker->generate(['type' => 'inc']);

        $this->assertIsString($company);
        $this->assertStringEndsWith('Inc.', $company);
    }

    /**
     * Test that CompanyFaker respects suffix option.
     */
    public function testGenerateWithSuffix(): void
    {
        $faker = new CompanyFaker('en_US');
        $company = $faker->generate(['suffix' => 'Ltd.']);

        $this->assertIsString($company);
        $this->assertStringEndsWith('Ltd.', $company);
    }
}
