<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EmailSubscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailSubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'Unsubscribed' => 'unsubscribed',
                ],
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('backupEmail', EmailType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('source', ChoiceType::class, [
                'choices' => [
                    'Website' => 'website',
                    'Newsletter' => 'newsletter',
                    'Promotion' => 'promotion',
                    'Partner' => 'partner',
                ],
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('subscribedAt', DateTimeType::class, [
                'required' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('unsubscribedAt', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailSubscription::class,
        ]);
    }
}
