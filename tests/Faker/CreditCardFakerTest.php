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
}
