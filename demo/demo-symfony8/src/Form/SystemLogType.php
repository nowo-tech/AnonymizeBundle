<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SystemLog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SystemLogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sessionId', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('ipAddress', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('macAddress', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('logLevel', ChoiceType::class, [
                'choices' => [
                    'Info' => 'info',
                    'Warning' => 'warning',
                    'Error' => 'error',
                    'Debug' => 'debug',
                ],
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('countryCode', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'maxlength' => 2],
            ])
            ->add('languageCode', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'maxlength' => 5],
            ])
            ->add('apiKey', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('tokenHash', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('themeColor', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isActive', BooleanType::class, [
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('score', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('logFile', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('metadata', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('createdAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('userIdHash', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('processStatus', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Processing' => 'processing',
                    'Completed' => 'completed',
                    'Failed' => 'failed',
                ],
                'required' => false,
                'placeholder' => '-- Select --',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('dataClassification', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SystemLog::class,
        ]);
    }
}
