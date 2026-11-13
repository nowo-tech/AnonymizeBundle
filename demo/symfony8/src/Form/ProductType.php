<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'name', TextType::class, [
            'label'              => 'Name',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'description', TextareaType::class, [
            'label'              => 'Description',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'attr'               => ['rows' => 4],
        ]);
        $this->addWithDefaults($builder, 'price', MoneyType::class, [
            'label'              => 'Price',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'currency'           => 'USD',
        ]);
        $this->addWithDefaults($builder, 'imageUrl', UrlType::class, [
            'label'              => 'Image url',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'status', ChoiceType::class, [
            'label'              => 'Status',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'choices'            => [
                'Active'   => 'active',
                'Inactive' => 'inactive',
                'Draft'    => 'draft',
            ],
            'required' => true,
        ]);
        $this->addWithDefaults($builder, 'createdAt', DateTimeType::class, [
            'label'              => 'Created at',
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
            'data_class' => Product::class,
        ]);
    }
}
