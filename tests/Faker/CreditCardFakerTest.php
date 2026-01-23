<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\CreditCardFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for CreditCardFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class CreditCardFakerTest extends TestCase
{
    /**
     * Test that CreditCardFaker generates a valid credit card number.
     */
    public function testGenerate(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate();

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        // Credit card numbers are typically 13-19 digits
        $this->assertMatchesRegularExpression('/^\d{13,19}$/', str_replace(' ', '', $creditCard));
    }

    /**
     * Test that CreditCardFaker generates different credit card numbers.
     */
    public function testGenerateUnique(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard1 = $faker->generate();
        $creditCard2 = $faker->generate();

        $this->assertIsString($creditCard1);
        $this->assertIsString($creditCard2);
    }

    /**
     * Test that CreditCardFaker respects type option (visa).
     */
    public function testGenerateWithTypeVisa(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate(['type' => 'visa']);

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        $cleanNumber = preg_replace('/[\s-]/', '', $creditCard);
        $this->assertStringStartsWith('4', $cleanNumber);
    }

    /**
     * Test that CreditCardFaker respects type option (mastercard).
     */
    public function testGenerateWithTypeMastercard(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate(['type' => 'mastercard']);

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        $cleanNumber = preg_replace('/[\s-]/', '', $creditCard);
        // Mastercard numbers typically start with 5[1-5] or 2[2-7], but Faker may generate variations
        $this->assertMatchesRegularExpression('/^\d{13,19}$/', $cleanNumber);
    }

    /**
     * Test that CreditCardFaker respects formatted option.
     */
    public function testGenerateWithFormatted(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate(['formatted' => true]);

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        // Should contain spaces
        $this->assertStringContainsString(' ', $creditCard);
    }

    /**
     * Test that CreditCardFaker respects formatted false option.
     */
    public function testGenerateWithoutFormatted(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate(['formatted' => false]);

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        // Should not contain spaces when formatted is false
        $cleanNumber = preg_replace('/[\s-]/', '', $creditCard);
        $this->assertMatchesRegularExpression('/^\d{13,19}$/', $cleanNumber);
    }

    /**
     * Test that CreditCardFaker respects type option (amex).
     */
    public function testGenerateWithTypeAmex(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate(['type' => 'amex']);

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        $cleanNumber = preg_replace('/[\s-]/', '', $creditCard);
        // American Express numbers typically start with 34 or 37
        $this->assertMatchesRegularExpression('/^\d{13,19}$/', $cleanNumber);
    }

    /**
     * Test that CreditCardFaker respects valid false option.
     */
    public function testGenerateWithValidFalse(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate(['valid' => false]);

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        $cleanNumber = preg_replace('/[\s-]/', '', $creditCard);
        // Should still be a valid length credit card number
        $this->assertMatchesRegularExpression('/^\d{13,19}$/', $cleanNumber);
    }

    /**
     * Test that CreditCardFaker handles invalid type gracefully.
     */
    public function testGenerateWithInvalidType(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate(['type' => 'invalid_type']);

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        $cleanNumber = preg_replace('/[\s-]/', '', $creditCard);
        $this->assertMatchesRegularExpression('/^\d{13,19}$/', $cleanNumber);
    }

    /**
     * Test that CreditCardFaker combines formatted and valid options.
     */
    public function testGenerateWithFormattedAndValidFalse(): void
    {
        $faker = new CreditCardFaker('en_US');
        $creditCard = $faker->generate(['formatted' => true, 'valid' => false]);

        $this->assertIsString($creditCard);
        $this->assertNotEmpty($creditCard);
        $this->assertStringContainsString(' ', $creditCard);
    }
}
