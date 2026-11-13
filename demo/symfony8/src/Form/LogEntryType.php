<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\LogEntry;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class LogEntryType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'message', TextareaType::class, [
            'label'              => 'Message',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'attr'               => ['rows' => 3],
            'required'           => true,
        ]);
        $this->addWithDefaults($builder, 'ipAddress', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'label'              => 'IP Address',
        ]);
        $this->addWithDefaults($builder, 'loggedAt', DateTimeType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => true,
            'widget'             => 'single_text',
            'label'              => 'Logged At',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogEntry::class,
        ]);
    }
}
