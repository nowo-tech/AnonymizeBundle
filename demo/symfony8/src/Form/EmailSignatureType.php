<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EmailSignature;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EmailSignatureType extends DemoFormType
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
        $this->addWithDefaults($builder, 'signature', TextareaType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'label'              => 'Signature (HTML)',
            'attr'               => ['rows' => 8],
        ]);
        $this->addWithDefaults($builder, 'emailBody', TextareaType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'label'              => 'Email Body (HTML)',
            'attr'               => ['rows' => 8],
        ]);
        $this->addWithDefaults($builder, 'senderName', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'label'              => 'Sender Name',
        ]);
        $this->addWithDefaults($builder, 'sentAt', DateTimeType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'widget'             => 'single_text',
            'label'              => 'Sent At',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailSignature::class,
        ]);
    }
}
