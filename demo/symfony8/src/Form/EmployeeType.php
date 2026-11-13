<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Employee;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EmployeeType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'firstName', TextType::class, [
            'label'              => 'First name',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'lastName', TextType::class, [
            'label'              => 'Last name',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'username', TextType::class, [
            'label'              => 'Username',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'email', EmailType::class, [
            'label'              => 'Email',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'phone', TextType::class, [
            'label'              => 'Phone',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'age', IntegerType::class, [
            'label'              => 'Age',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'birthDate', DateType::class, [
            'label'              => 'Birth date',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'widget'             => 'single_text',
        ]);
        $this->addWithDefaults($builder, 'hireDate', DateTimeType::class, [
            'label'              => 'Hire date',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'widget'             => 'single_text',
        ]);
        $this->addWithDefaults($builder, 'department', ChoiceType::class, [
            'label'              => 'Department',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'choices'            => [
                'HR'         => 'HR',
                'IT'         => 'IT',
                'Sales'      => 'Sales',
                'Marketing'  => 'Marketing',
                'Finance'    => 'Finance',
                'Operations' => 'Operations',
            ],
            'required' => true,
        ]);
        $this->addWithDefaults($builder, 'previousCompany', TextType::class, [
            'label'              => 'Previous company',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
        ]);
    }
}
