<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('price', MoneyType::class, [
                'required' => true,
                'currency' => 'USD',
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('imageUrl', UrlType::class, [
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active'   => 'active',
                    'Inactive' => 'inactive',
                    'Draft'    => 'draft',
                ],
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('createdAt', DateTimeType::class, [
                'required' => false,
                'widget'   => 'single_text',
                'attr'     => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
