<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CustomerType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'email', EmailType::class, [
            'label'              => 'Email',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'name', TextType::class, [
            'label'              => 'Name',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'status', ChoiceType::class, [
            'label'              => 'Status',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'choices'            => [
                'Active'   => 'active',
                'Inactive' => 'inactive',
            ],
            'required' => true,
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
