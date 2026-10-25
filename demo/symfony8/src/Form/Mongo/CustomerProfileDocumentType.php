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
        $c = ['class' => 'form-control'];
        $s = ['class' => 'form-select'];

        return [
            'email'     => [EmailType::class, ['label' => 'Email *', 'required' => true, 'attr' => $c], 'string', ''],
            'username'  => [TextType::class, ['label' => 'Username', 'required' => false, 'attr' => $c], 'string', ''],
            'firstName' => [TextType::class, ['label' => 'First Name *', 'required' => true, 'attr' => $c], 'string', ''],
            'lastName'  => [TextType::class, ['label' => 'Last Name *', 'required' => true, 'attr' => $c], 'string', ''],
            'phone'     => [TelType::class, ['label' => 'Phone', 'required' => false, 'attr' => $c], 'string', ''],
            'age'       => [IntegerType::class, ['label' => 'Age', 'required' => false, 'attr' => $c + ['min' => 0, 'max' => 150]], 'int', null],
            'address'   => [TextType::class, ['label' => 'Address', 'required' => false, 'attr' => $c], 'string', ''],
            'company'   => [TextType::class, ['label' => 'Company', 'required' => false, 'attr' => $c], 'string', ''],
            'website'   => [UrlType::class, ['label' => 'Website', 'required' => false, 'attr' => $c + ['placeholder' => 'https://example.com'], 'default_protocol' => null], 'string', ''],
            'status'    => [ChoiceType::class, ['label' => 'Status *', 'required' => true, 'attr' => $s, 'choices' => ['Active' => 'active', 'Inactive' => 'inactive', 'Suspended' => 'suspended']], 'string', 'active'],
            'createdAt' => [TextType::class, ['label' => 'Created At', 'required' => false, 'attr' => $c], 'datetime', null],
        ];
    }
}
