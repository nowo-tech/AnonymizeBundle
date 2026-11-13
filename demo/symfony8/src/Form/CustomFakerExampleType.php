<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CustomFakerExample;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CustomFakerExampleType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'preservedField', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => true,
            'label'              => 'Preserved Field (ExampleCustomFaker with preserve_original=true)',
            'help'               => 'This field uses ExampleCustomFaker and will preserve the original value',
        ]);
        $this->addWithDefaults($builder, 'anonymizedField', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => true,
            'label'              => 'Anonymized Field (ExampleCustomFaker with preserve_original=false)',
            'help'               => 'This field uses ExampleCustomFaker and will be anonymized',
        ]);
        $this->addWithDefaults($builder, 'referenceField', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'Reference Field',
            'help'               => 'This field can be accessed by ExampleCustomFaker via $options[\'record\'][\'referenceField\']',
        ]);
        $this->addWithDefaults($builder, 'customOptionField', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'Custom Option Field',
            'help'               => 'This field uses ExampleCustomFaker with custom options',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomFakerExample::class,
        ]);
    }
}
