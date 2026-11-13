<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CacheData;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CacheDataType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'cacheKey', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'label'              => 'Cache Key',
        ]);
        $this->addWithDefaults($builder, 'cacheValue', TextareaType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'attr'               => ['rows' => 10],
            'required'           => true,
            'label'              => 'Cache Value (JSON)',
            'help'               => 'Enter JSON data as a string (e.g., {"key": "value"})',
            'mapped'             => false,
        ]);
        $this->addWithDefaults($builder, 'expiresAt', DateTimeType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'widget'             => 'single_text',
            'label'              => 'Expires At',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CacheData::class,
        ]);
    }
}
