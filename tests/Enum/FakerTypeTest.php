<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Enum;

use Nowo\AnonymizeBundle\Enum\FakerType;
use PHPUnit\Framework\TestCase;

/**
 * Test case for FakerType enum.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class FakerTypeTest extends TestCase
{
    /**
     * Test that all enum cases have correct string values.
     */
    public function testAllEnumCasesHaveCorrectValues(): void
    {
        $this->assertEquals('email', FakerType::EMAIL->value);
        $this->assertEquals('name', FakerType::NAME->value);
        $this->assertEquals('surname', FakerType::SURNAME->value);
        $this->assertEquals('age', FakerType::AGE->value);
        $this->assertEquals('phone', FakerType::PHONE->value);
        $this->assertEquals('iban', FakerType::IBAN->value);
        $this->assertEquals('credit_card', FakerType::CREDIT_CARD->value);
        $this->assertEquals('address', FakerType::ADDRESS->value);
        $this->assertEquals('date', FakerType::DATE->value);
        $this->assertEquals('username', FakerType::USERNAME->value);
        $this->assertEquals('url', FakerType::URL->value);
        $this->assertEquals('company', FakerType::COMPANY->value);
        $this->assertEquals('masking', FakerType::MASKING->value);
        $this->assertEquals('password', FakerType::PASSWORD->value);
        $this->assertEquals('ip_address', FakerType::IP_ADDRESS->value);
        $this->assertEquals('mac_address', FakerType::MAC_ADDRESS->value);
        $this->assertEquals('uuid', FakerType::UUID->value);
        $this->assertEquals('hash', FakerType::HASH->value);
        $this->assertEquals('coordinate', FakerType::COORDINATE->value);
        $this->assertEquals('color', FakerType::COLOR->value);
        $this->assertEquals('boolean', FakerType::BOOLEAN->value);
        $this->assertEquals('numeric', FakerType::NUMERIC->value);
        $this->assertEquals('file', FakerType::FILE->value);
        $this->assertEquals('json', FakerType::JSON->value);
        $this->assertEquals('text', FakerType::TEXT->value);
        $this->assertEquals('enum', FakerType::ENUM->value);
        $this->assertEquals('country', FakerType::COUNTRY->value);
        $this->assertEquals('language', FakerType::LANGUAGE->value);
        $this->assertEquals('hash_preserve', FakerType::HASH_PRESERVE->value);
        $this->assertEquals('shuffle', FakerType::SHUFFLE->value);
        $this->assertEquals('constant', FakerType::CONSTANT->value);
        $this->assertEquals('service', FakerType::SERVICE->value);
    }

    /**
     * Test that enum can be created from string value.
     */
    public function testEnumCanBeCreatedFromStringValue(): void
    {
        $this->assertEquals(FakerType::EMAIL, FakerType::from('email'));
        $this->assertEquals(FakerType::NAME, FakerType::from('name'));
        $this->assertEquals(FakerType::PASSWORD, FakerType::from('password'));
        $this->assertEquals(FakerType::SERVICE, FakerType::from('service'));
    }

    /**
     * Test that enum throws exception for invalid value.
     */
    public function testEnumThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        FakerType::from('invalid_type');
    }

    /**
     * Test that enum can be created using tryFrom without exception.
     */
    public function testEnumTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(FakerType::tryFrom('invalid_type'));
        $this->assertEquals(FakerType::EMAIL, FakerType::tryFrom('email'));
    }

    /**
     * Test that all enum cases are accessible.
     */
    public function testAllEnumCasesAreAccessible(): void
    {
        $cases = FakerType::cases();
        $this->assertIsArray($cases);
        $this->assertGreaterThan(0, count($cases));

        // Verify all expected cases exist
        $expectedCases = [
            'EMAIL', 'NAME', 'SURNAME', 'AGE', 'PHONE', 'IBAN', 'CREDIT_CARD',
            'ADDRESS', 'DATE', 'USERNAME', 'URL', 'COMPANY', 'MASKING', 'PASSWORD',
            'IP_ADDRESS', 'MAC_ADDRESS', 'UUID', 'HASH', 'COORDINATE', 'COLOR',
            'BOOLEAN', 'NUMERIC', 'FILE', 'JSON', 'TEXT', 'ENUM', 'COUNTRY',
            'LANGUAGE', 'HASH_PRESERVE', 'SHUFFLE', 'CONSTANT', 'SERVICE',
        ];

        foreach ($expectedCases as $caseName) {
            $this->assertTrue(
                in_array(constant("Nowo\\AnonymizeBundle\\Enum\\FakerType::$caseName"), $cases),
                "Case $caseName should exist"
            );
        }
    }
}
