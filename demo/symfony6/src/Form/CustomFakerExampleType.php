<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CustomFakerExample;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomFakerExampleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('preservedField', TextType::class, [
                'required' => true,
                'label'    => 'Preserved Field (ExampleCustomFaker with preserve_original=true)',
                'help'     => 'This field uses ExampleCustomFaker and will preserve the original value',
            ])
            ->add('anonymizedField', TextType::class, [
                'required' => true,
                'label'    => 'Anonymized Field (ExampleCustomFaker with preserve_original=false)',
                'help'     => 'This field uses ExampleCustomFaker and will be anonymized',
            ])
            ->add('referenceField', TextType::class, [
                'required' => false,
                'label'    => 'Reference Field',
                'help'     => 'This field can be accessed by ExampleCustomFaker via $options[\'record\'][\'referenceField\']',
            ])
            ->add('customOptionField', TextType::class, [
                'required' => false,
                'label'    => 'Custom Option Field',
                'help'     => 'This field uses ExampleCustomFaker with custom options',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomFakerExample::class,
        ]);
    }
}
