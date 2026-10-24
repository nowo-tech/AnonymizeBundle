<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\LogEntry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('message', TextareaType::class, [
                'attr'     => ['class' => 'form-control', 'rows' => 3],
                'required' => true,
            ])
            ->add('ipAddress', TextType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => true,
                'label'    => 'IP Address',
            ])
            ->add('loggedAt', DateTimeType::class, [
                'attr'     => ['class' => 'form-control'],
                'required' => true,
                'widget'   => 'single_text',
                'label'    => 'Logged At',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogEntry::class,
        ]);
    }
}
