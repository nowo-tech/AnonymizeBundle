<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\CustomFakerExample;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures for CustomFakerExample entity.
 *
 * These fixtures demonstrate various scenarios for the ExampleCustomFaker:
 * - Fields with preserve_original=true (values remain unchanged)
 * - Fields with preserve_original=false (values are anonymized)
 * - Fields with custom options
 * - Fields with null values
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class CustomFakerExampleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Example 1: All fields with values
        $example1 = new CustomFakerExample();
        $example1->setPreservedField('original_value_1');
        $example1->setAnonymizedField('sensitive_data_1');
        $example1->setReferenceField('REF-001');
        $example1->setCustomOptionField('custom_value_1');
        $manager->persist($example1);

        // Example 2: Empty/minimal value (use empty string to avoid NOT NULL issues on sqlite when column not yet nullable)
        $example2 = new CustomFakerExample();
        $example2->setPreservedField('original_value_2');
        $example2->setAnonymizedField('');
        $example2->setReferenceField('REF-002');
        $example2->setCustomOptionField('custom_value_2');
        $manager->persist($example2);

        // Example 3: Different values
        $example3 = new CustomFakerExample();
        $example3->setPreservedField('test@example.com');
        $example3->setAnonymizedField('John Doe');
        $example3->setReferenceField('REF-003');
        $example3->setCustomOptionField(null);
        $manager->persist($example3);

        // Example 4: Long values
        $example4 = new CustomFakerExample();
        $example4->setPreservedField('very_long_original_value_that_should_be_preserved');
        $example4->setAnonymizedField('very_long_sensitive_data_that_should_be_anonymized');
        $example4->setReferenceField('REF-004');
        $example4->setCustomOptionField('very_long_custom_value');
        $manager->persist($example4);

        // Example 5: Special characters
        $example5 = new CustomFakerExample();
        $example5->setPreservedField('value with spaces & special chars!@#$%');
        $example5->setAnonymizedField('sensitive with émojis 🎉 and unicode ñ');
        $example5->setReferenceField('REF-005');
        $example5->setCustomOptionField('custom with "quotes" and \'apostrophes\'');
        $manager->persist($example5);

        $manager->flush();
    }
}
