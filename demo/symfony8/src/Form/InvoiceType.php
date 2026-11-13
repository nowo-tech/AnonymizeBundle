<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Invoice;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'invoiceNumber', TextType::class, [
            'label'              => 'Invoice number',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'companyName', TextType::class, [
            'label'              => 'Company name',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'companyAddress', TextType::class, [
            'label'              => 'Company address',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'bankAccount', TextType::class, [
            'label'              => 'Bank account',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'creditCard', TextType::class, [
            'label'              => 'Credit card',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'amount', MoneyType::class, [
            'label'              => 'Amount',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'currency'           => 'USD',
        ]);
        $this->addWithDefaults($builder, 'status', ChoiceType::class, [
            'label'              => 'Status',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'choices'            => [
                'Draft'     => 'draft',
                'Sent'      => 'sent',
                'Paid'      => 'paid',
                'Overdue'   => 'overdue',
                'Cancelled' => 'cancelled',
            ],
            'required' => true,
        ]);
        $this->addWithDefaults($builder, 'issueDate', DateTimeType::class, [
            'label'              => 'Issue date',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'widget'             => 'single_text',
        ]);
        $this->addWithDefaults($builder, 'dueDate', DateTimeType::class, [
            'label'              => 'Due date',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'widget'             => 'single_text',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}
