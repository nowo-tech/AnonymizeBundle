<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Nowo\AnonymizeBundle\Helper\OrmHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test case for OrmHelper.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class OrmHelperTest extends TestCase
{
    public function testGetFieldColumnNameUsesMetadataGetColumnName(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getColumnName')
            ->with('email')
            ->willReturn('email_address');

        $this->assertSame('email_address', OrmHelper::getFieldColumnName($metadata, 'email'));
    }

    public function testGetFieldColumnNameFallsBackToFieldMappingWhenGetColumnNameIsEmpty(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getColumnName')->with('email')->willReturn('');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn(new FieldMapping('string', 'email', 'email_col'));

        $this->assertSame('email_col', OrmHelper::getFieldColumnName($metadata, 'email'));
    }

    public function testGetColumnNameFromFieldMappingWithFieldMappingObject(): void
    {
        $fieldMapping = new FieldMapping('string', 'email', 'email_address');

        $this->assertSame('email_address', OrmHelper::getColumnNameFromFieldMapping($fieldMapping, 'email'));
    }

    public function testGetColumnNameFromFieldMappingWithLegacyArray(): void
    {
        $fieldMapping = ['columnName' => 'legacy_col', 'type' => 'string'];

        $this->assertSame('legacy_col', OrmHelper::getColumnNameFromFieldMapping($fieldMapping, 'id'));
    }

    public function testGetColumnNameFromFieldMappingReturnsFallbackForNull(): void
    {
        $this->assertSame('id', OrmHelper::getColumnNameFromFieldMapping(null));
        $this->assertSame('custom', OrmHelper::getColumnNameFromFieldMapping(null, 'custom'));
    }

    public function testGetColumnNameFromFieldMappingReturnsFallbackForEmptyColumnName(): void
    {
        $fieldMapping = new FieldMapping('string', 'email', '');

        $this->assertSame('email', OrmHelper::getColumnNameFromFieldMapping($fieldMapping, 'email'));
    }

    public function testGetFieldTypeFromFieldMappingWithFieldMappingObject(): void
    {
        $fieldMapping = new FieldMapping('integer', 'age', 'age');

        $this->assertSame('integer', OrmHelper::getFieldTypeFromFieldMapping($fieldMapping, 'string'));
    }

    public function testGetFieldTypeFromFieldMappingWithLegacyArray(): void
    {
        $fieldMapping = ['columnName' => 'amount', 'type' => 'decimal'];

        $this->assertSame('decimal', OrmHelper::getFieldTypeFromFieldMapping($fieldMapping, 'string'));
    }

    public function testGetFieldTypeFromFieldMappingReturnsFallbackForNull(): void
    {
        $this->assertSame('string', OrmHelper::getFieldTypeFromFieldMapping(null));
        $this->assertSame('boolean', OrmHelper::getFieldTypeFromFieldMapping(null, 'boolean'));
    }

    public function testResolveDiscriminatorColumnNameWithArray(): void
    {
        $this->assertSame('dtype', OrmHelper::resolveDiscriminatorColumnName(['name' => 'dtype']));
        $this->assertSame('dtype', OrmHelper::resolveDiscriminatorColumnName(['columnName' => 'dtype']));
    }

    public function testResolveDiscriminatorColumnNameWithObject(): void
    {
        $discColumn = new class {
            public string $name = 'disc_type';
        };

        $this->assertSame('disc_type', OrmHelper::resolveDiscriminatorColumnName($discColumn));
    }

    public function testResolveDiscriminatorColumnNameReturnsNullForInvalidValue(): void
    {
        $this->assertNull(OrmHelper::resolveDiscriminatorColumnName(null));
        $this->assertNull(OrmHelper::resolveDiscriminatorColumnName([]));
    }

    public function testGetDiscriminatorColumnNameUsesFallback(): void
    {
        $this->assertSame('type', OrmHelper::getDiscriminatorColumnName(null));
        $this->assertSame('custom', OrmHelper::getDiscriminatorColumnName(null, 'custom'));
    }
}
