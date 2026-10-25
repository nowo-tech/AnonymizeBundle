<?php

declare(strict_types=1);

namespace App\Form\Mongo;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Transaction log form (MongoDB demo, REQ-TWIG-005).
 */
final class TransactionLogDocumentType extends AbstractMongoDocumentType
{
    public static function fields(): array
    {
        $c = ['class' => 'form-control'];
        $s = ['class' => 'form-select'];

        return [
            'transactionId'   => [TextType::class, ['label' => 'Transaction ID *', 'required' => true, 'attr' => $c], 'string', ''],
            'customerEmail'   => [EmailType::class, ['label' => 'Customer Email *', 'required' => true, 'attr' => $c], 'string', ''],
            'iban'            => [TextType::class, ['label' => 'IBAN', 'required' => false, 'attr' => $c + ['placeholder' => 'ES9121000418450200051332']], 'string', ''],
            'creditCard'      => [TextType::class, ['label' => 'Credit Card', 'required' => false, 'attr' => $c + ['placeholder' => '4532-1234-5678-9010']], 'string', ''],
            'maskedCard'      => [TextType::class, ['label' => 'Masked Card (Last 4 digits)', 'required' => false, 'attr' => $c + ['placeholder' => '****-****-****-9010']], 'string', ''],
            'transactionHash' => [TextType::class, ['label' => 'Transaction Hash', 'required' => false, 'attr' => $c], 'string', ''],
            'amount'          => [NumberType::class, ['label' => 'Amount *', 'required' => true, 'attr' => $c + ['step' => '0.01'], 'html5' => true, 'scale' => 2], 'float', null],
            'currency'        => [ChoiceType::class, ['label' => 'Currency *', 'required' => true, 'attr' => $s, 'choices' => ['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP']], 'string', 'EUR'],
            'status'          => [ChoiceType::class, ['label' => 'Status *', 'required' => true, 'attr' => $s, 'choices' => ['Pending' => 'pending', 'Completed' => 'completed', 'Failed' => 'failed', 'Refunded' => 'refunded']], 'string', 'pending'],
            'transactionDate' => [TextType::class, ['label' => 'Transaction Date', 'required' => false, 'attr' => $c], 'datetime', null],
        ];
    }
}
