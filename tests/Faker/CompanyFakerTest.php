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

    /**
     * Test that CompanyFaker handles unknown type gracefully.
     */
    public function testGenerateWithUnknownType(): void
    {
        $faker = new CompanyFaker('en_US');
        $company = $faker->generate(['type' => 'unknown_type']);

        $this->assertIsString($company);
        $this->assertNotEmpty($company);
        // Should return company name without suffix modification
    }

    /**
     * Test that CompanyFaker handles type 'ltd'.
     */
    public function testGenerateWithTypeLtd(): void
    {
        $faker = new CompanyFaker('en_US');
        $company = $faker->generate(['type' => 'ltd']);

        $this->assertIsString($company);
        $this->assertStringEndsWith('Ltd.', $company);
    }

    /**
     * Test that CompanyFaker removes existing suffix before adding new one.
     */
    public function testGenerateRemovesExistingSuffix(): void
    {
        $faker = new CompanyFaker('en_US');
        // Generate multiple times to potentially get a company with existing suffix
        for ($i = 0; $i < 10; $i++) {
            $company = $faker->generate(['type' => 'llc']);
            $this->assertIsString($company);
            // Should end with LLC and not have double suffixes
            $this->assertStringEndsWith('LLC', $company);
            $this->assertStringNotContainsString('LLC LLC', $company);
        }
    }

    /**
     * Test that CompanyFaker handles type 'corp'.
     */
    public function testGenerateWithTypeCorp(): void
    {
        $faker = new CompanyFaker('en_US');
        $company = $faker->generate(['type' => 'corp']);

        $this->assertIsString($company);
        $this->assertStringEndsWith('Corp.', $company);
    }

    /**
     * Test that CompanyFaker handles suffix with existing suffix removal.
     */
    public function testGenerateWithSuffixRemovesExisting(): void
    {
        $faker = new CompanyFaker('en_US');
        // Generate multiple times to potentially get a company with existing suffix
        for ($i = 0; $i < 10; $i++) {
            $company = $faker->generate(['suffix' => 'Custom']);
            $this->assertIsString($company);
            $this->assertStringEndsWith('Custom', $company);
            // Should not have double suffixes
            $this->assertStringNotContainsString('Custom Custom', $company);
        }
    }

    /**
     * Test that CompanyFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new CompanyFaker('en_US');
        $this->assertInstanceOf(CompanyFaker::class, $faker);
    }

    /**
     * Test that CompanyFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new CompanyFaker('es_ES');
        $company = $faker->generate();

        $this->assertIsString($company);
        $this->assertNotEmpty($company);
    }
}
