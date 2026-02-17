<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('firstName', TextType::class, [
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('lastName', TextType::class, [
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('age', IntegerType::class, [
                'required' => true,
                'attr'     => ['class' => 'form-control', 'min' => 1],
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('iban', TextType::class, [
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('creditCard', TextType::class, [
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active'   => 'active',
                    'Inactive' => 'inactive',
                ],
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
