<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\UserAccount;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserAccountType extends DemoFormType
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
        $this->addWithDefaults($builder, 'username', TextType::class, [
            'label'              => 'Username',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'usernameCanonical', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'label'              => 'Username Canonical',
        ]);
        $this->addWithDefaults($builder, 'emailCanonical', EmailType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'label'              => 'Email Canonical',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserAccount::class,
        ]);
    }
}
