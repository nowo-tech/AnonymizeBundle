<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\FakerTypeExample;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FakerTypeExampleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'help'     => 'Uses FakerType::EMAIL enum',
            ])
            ->add('legalId', TextType::class, [
                'required' => false,
                'label'    => 'Legal ID (DNI/CIF)',
                'help'     => 'Uses FakerType::DNI_CIF enum',
            ])
            ->add('signature', TextareaType::class, [
                'required' => false,
                'label'    => 'Signature (HTML)',
                'attr'     => ['rows' => 5],
                'help'     => 'Uses FakerType::HTML enum',
            ])
            ->add('username', TextType::class, [
                'required' => true,
                'help'     => 'Uses FakerType::PATTERN_BASED enum',
            ])
            ->add('sensitiveNotes', TextareaType::class, [
                'required' => false,
                'label'    => 'Sensitive Notes',
                'attr'     => ['rows' => 3],
                'help'     => 'Uses FakerType::NULL enum with bypass_entity_exclusion',
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'help'     => 'Uses string (backward compatibility)',
            ])
            ->add('status', TextType::class, [
                'required' => false,
                'help'     => 'Uses FakerType::MAP (active→status_a, inactive→status_b, pending→status_c, else status_unknown)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FakerTypeExample::class,
        ]);
    }
}
