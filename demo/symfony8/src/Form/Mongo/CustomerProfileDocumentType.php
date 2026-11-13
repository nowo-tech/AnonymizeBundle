<?php

declare(strict_types=1);

namespace App\Form\Mongo;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * Customer profile form (MongoDB demo, REQ-TWIG-005).
 */
final class CustomerProfileDocumentType extends AbstractMongoDocumentType
{
    public static function fields(): array
    {
        return [
            'email'     => [EmailType::class, ['label' => 'Email *', 'required' => true], 'string', ''],
            'username'  => [TextType::class, ['label' => 'Username', 'required' => false], 'string', ''],
            'firstName' => [TextType::class, ['label' => 'First Name *', 'required' => true], 'string', ''],
            'lastName'  => [TextType::class, ['label' => 'Last Name *', 'required' => true], 'string', ''],
            'phone'     => [TelType::class, ['label' => 'Phone', 'required' => false], 'string', ''],
            'age'       => [IntegerType::class, ['label' => 'Age', 'required' => false, 'attr' => ['min' => 0, 'max' => 150]], 'int', null],
            'address'   => [TextType::class, ['label' => 'Address', 'required' => false], 'string', ''],
            'company'   => [TextType::class, ['label' => 'Company', 'required' => false], 'string', ''],
            'website'   => [UrlType::class, ['label' => 'Website', 'required' => false, 'attr' => ['placeholder' => 'https://example.com'], 'default_protocol' => null], 'string', ''],
            'status'    => [ChoiceType::class, ['label' => 'Status *', 'required' => true, 'choices' => ['Active' => 'active', 'Inactive' => 'inactive', 'Suspended' => 'suspended']], 'string', 'active'],
            'createdAt' => [TextType::class, ['label' => 'Created At', 'required' => false], 'datetime', null],
        ];
    }
}
