<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\TempData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TempDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('phone', TextType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('createdAt', DateTimeType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => true,
                'widget'   => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TempData::class,
        ]);
    }
}
