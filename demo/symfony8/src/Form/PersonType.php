<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Person;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PersonType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'name', TextType::class, [
            'label'              => 'Name',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'firstname', TextType::class, [
            'label'              => 'Firstname',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'surname', TextType::class, [
            'label'              => 'Surname',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'email', EmailType::class, [
            'label'              => 'Email',
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
            'required'           => false,
            'attr'               => ['min' => 1],
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
}
