<?php

declare(strict_types=1);

namespace App\Form\Mongo;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Analytics event form (MongoDB demo, REQ-TWIG-005).
 */
final class AnalyticsEventDocumentType extends AbstractMongoDocumentType
{
    public static function fields(): array
    {
        return [
            'eventId'            => [TextType::class, ['label' => 'Event ID (UUID)', 'required' => false, 'attr' => ['placeholder' => '550e8400-e29b-41d4-a716-446655440000']], 'string', ''],
            'eventType'          => [ChoiceType::class, ['label' => 'Event Type *', 'required' => true, 'choices' => ['Page View' => 'page_view', 'Click' => 'click', 'Purchase' => 'purchase', 'Signup' => 'signup']], 'string', 'page_view'],
            'country'            => [TextType::class, ['label' => 'Country Code', 'required' => false, 'attr' => ['maxlength' => 2, 'placeholder' => 'ES']], 'string', ''],
            'language'           => [TextType::class, ['label' => 'Language Code', 'required' => false, 'attr' => ['maxlength' => 5, 'placeholder' => 'es-ES']], 'string', ''],
            'eventData'          => [TextareaType::class, ['label' => 'Event Data (JSON)', 'required' => false, 'attr' => ['rows' => 3, 'placeholder' => '{"key": "value"}']], 'string', '{}'],
            'description'        => [TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 3]], 'string', ''],
            'userIdHash'         => [TextType::class, ['label' => 'User ID Hash', 'required' => false], 'string', ''],
            'category'           => [ChoiceType::class, ['label' => 'Category', 'required' => false, 'choices' => ['Pending' => 'pending', 'Processing' => 'processing', 'Completed' => 'completed', 'Failed' => 'failed'], 'placeholder' => '-- Select --'], 'string', ''],
            'dataClassification' => [TextType::class, ['label' => 'Data Classification', 'required' => false], 'string', 'ANONYMIZED'],
            'timestamp'          => [TextType::class, ['label' => 'Timestamp', 'required' => false], 'datetime', null],
        ];
    }
}
