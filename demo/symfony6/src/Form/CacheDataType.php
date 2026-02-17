<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CacheData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CacheDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cacheKey', TextType::class, [
                'required' => true,
                'label'    => 'Cache Key',
            ])
            ->add('cacheValue', TextareaType::class, [
                'required' => true,
                'label'    => 'Cache Value (JSON)',
                'help'     => 'Enter JSON data as a string',
                'mapped'   => false,
            ])
            ->add('expiresAt', DateTimeType::class, [
                'required' => true,
                'widget'   => 'single_text',
                'label'    => 'Expires At',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CacheData::class,
        ]);
    }
}
