<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'orderNumber', TextType::class, [
            'label'              => 'Order number',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'customerEmail', EmailType::class, [
            'label'              => 'Customer email',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'shippingAddress', TextType::class, [
            'label'              => 'Shipping address',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'billingAddress', TextType::class, [
            'label'              => 'Billing address',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'totalAmount', MoneyType::class, [
            'label'              => 'Total amount',
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
                'Pending'    => 'pending',
                'Processing' => 'processing',
                'Shipped'    => 'shipped',
                'Completed'  => 'completed',
                'Cancelled'  => 'cancelled',
            ],
            'required' => true,
        ]);
        $this->addWithDefaults($builder, 'orderDate', DateTimeType::class, [
            'label'              => 'Order date',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'widget'             => 'single_text',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
