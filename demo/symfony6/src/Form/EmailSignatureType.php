<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EmailSignature;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailSignatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
            ])
            ->add('signature', TextareaType::class, [
                'required' => false,
                'label'    => 'Signature (HTML)',
                'attr'     => ['rows' => 8],
            ])
            ->add('emailBody', TextareaType::class, [
                'required' => false,
                'label'    => 'Email Body (HTML)',
                'attr'     => ['rows' => 8],
            ])
            ->add('senderName', TextType::class, [
                'required' => false,
                'label'    => 'Sender Name',
            ])
            ->add('sentAt', DateTimeType::class, [
                'required' => true,
                'widget'   => 'single_text',
                'label'    => 'Sent At',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailSignature::class,
        ]);
    }
}
