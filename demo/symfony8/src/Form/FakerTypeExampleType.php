<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\FakerTypeExample;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FakerTypeExampleType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'email', EmailType::class, [
            'label'              => 'Email',
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => true,
            'help'               => 'Uses FakerType::EMAIL enum',
        ]);
        $this->addWithDefaults($builder, 'legalId', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'Legal ID (DNI/CIF)',
            'help'               => 'Uses FakerType::DNI_CIF enum',
        ]);
        $this->addWithDefaults($builder, 'signature', TextareaType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'Signature (HTML)',
            'attr'               => ['rows' => 5],
            'help'               => 'Uses FakerType::HTML enum',
        ]);
        $this->addWithDefaults($builder, 'username', TextType::class, [
            'label'              => 'Username',
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => true,
            'help'               => 'Uses FakerType::PATTERN_BASED enum',
        ]);
        $this->addWithDefaults($builder, 'sensitiveNotes', TextareaType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'Sensitive Notes',
            'attr'               => ['rows' => 3],
            'help'               => 'Uses FakerType::NULL enum with bypass_entity_exclusion',
        ]);
        $this->addWithDefaults($builder, 'name', TextType::class, [
            'label'              => 'Name',
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => true,
            'help'               => 'Uses string (backward compatibility)',
        ]);
        $this->addWithDefaults($builder, 'status', TextType::class, [
            'label'              => 'Status',
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'help'               => 'Uses FakerType::MAP (active→status_a, inactive→status_b, pending→status_c, else status_unknown)',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FakerTypeExample::class,
        ]);
    }
}
