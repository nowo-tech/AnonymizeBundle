<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SystemLog;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SystemLogType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'sessionId', TextType::class, [
            'label'              => 'Session id',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'ipAddress', TextType::class, [
            'label'              => 'Ip address',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'macAddress', TextType::class, [
            'label'              => 'Mac address',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'logLevel', ChoiceType::class, [
            'label'              => 'Log level',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'choices'            => [
                'Info'    => 'info',
                'Warning' => 'warning',
                'Error'   => 'error',
                'Debug'   => 'debug',
            ],
            'required' => true,
        ]);
        $this->addWithDefaults($builder, 'countryCode', TextType::class, [
            'label'              => 'Country code',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'attr'               => ['maxlength' => 2],
        ]);
        $this->addWithDefaults($builder, 'languageCode', TextType::class, [
            'label'              => 'Language code',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'attr'               => ['maxlength' => 5],
        ]);
        $this->addWithDefaults($builder, 'apiKey', TextType::class, [
            'label'              => 'Api key',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'tokenHash', TextType::class, [
            'label'              => 'Token hash',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'location', TextType::class, [
            'label'              => 'Location',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'themeColor', TextType::class, [
            'label'              => 'Theme color',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'score', TextType::class, [
            'label'              => 'Score',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'isActive', CheckboxType::class, [
            'label'              => 'Is active',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'attr'               => ['class' => 'form-check-input'],
        ]);
        $this->addWithDefaults($builder, 'logFile', TextType::class, [
            'label'              => 'Log file',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'metadata', TextareaType::class, [
            'label'              => 'Metadata',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'attr'               => ['rows' => 3],
        ]);
        $this->addWithDefaults($builder, 'description', TextareaType::class, [
            'label'              => 'Description',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'attr'               => ['rows' => 3],
        ]);
        $this->addWithDefaults($builder, 'userIdHash', TextType::class, [
            'label'              => 'User id hash',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'processStatus', ChoiceType::class, [
            'label'              => 'Process status',
            'translation_domain' => false,
            'help'               => false,
            'choices'            => [
                'Pending'    => 'pending',
                'Processing' => 'processing',
                'Completed'  => 'completed',
                'Failed'     => 'failed',
            ],
            'required'    => false,
            'placeholder' => '-- Select --',
        ]);
        $this->addWithDefaults($builder, 'dataClassification', TextType::class, [
            'label'              => 'Data classification',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
        ]);
        $this->addWithDefaults($builder, 'createdAt', DateTimeType::class, [
            'label'              => 'Created at',
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'widget'             => 'single_text',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SystemLog::class,
        ]);
    }
}
