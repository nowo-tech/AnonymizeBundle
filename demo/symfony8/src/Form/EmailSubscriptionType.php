<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EmailSubscription;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EmailSubscriptionType extends DemoFormType
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
                'Active'       => 'active',
                'Inactive'     => 'inactive',
                'Unsubscribed' => 'unsubscribed',
            ],
            'required' => true,
        ]);
        $this->addWithDefaults($builder, 'source', ChoiceType::class, [
            'label'              => 'Source',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'choices'            => [
                'Website'    => 'website',
                'Newsletter' => 'newsletter',
                'Promotion'  => 'promotion',
                'Partner'    => 'partner',
            ],
            'required' => true,
        ]);
        $this->addWithDefaults($builder, 'backupEmail', EmailType::class, [
            'label'              => 'Backup email',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'subscribedAt', DateTimeType::class, [
            'label'              => 'Subscribed at',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'widget'             => 'single_text',
        ]);
        $this->addWithDefaults($builder, 'unsubscribedAt', DateTimeType::class, [
            'label'              => 'Unsubscribed at',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'widget'             => 'single_text',
        ]);
        $this->addWithDefaults($builder, 'notes', TextareaType::class, [
            'label'              => 'Notes',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'attr'               => ['rows' => 3],
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailSubscription::class,
        ]);
    }
}
