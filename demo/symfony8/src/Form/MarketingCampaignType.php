<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\MarketingCampaign;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MarketingCampaignType extends DemoFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'utmSource', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => true,
            'label'              => 'UTM Source',
            'help'               => 'Uses UtmFaker with type: source, format: snake_case',
        ]);
        $this->addWithDefaults($builder, 'utmMedium', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => true,
            'label'              => 'UTM Medium',
            'help'               => 'Uses UtmFaker with type: medium, format: snake_case',
        ]);
        $this->addWithDefaults($builder, 'utmCampaign', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => true,
            'label'              => 'UTM Campaign',
            'help'               => 'Uses UtmFaker with type: campaign, format: snake_case',
        ]);
        $this->addWithDefaults($builder, 'utmTerm', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'UTM Term',
            'help'               => 'Uses UtmFaker with type: term',
        ]);
        $this->addWithDefaults($builder, 'utmContent', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'UTM Content',
            'help'               => 'Uses UtmFaker with type: content',
        ]);
        $this->addWithDefaults($builder, 'utmSourceKebab', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'UTM Source (Kebab-case)',
            'help'               => 'Uses UtmFaker with format: kebab-case',
        ]);
        $this->addWithDefaults($builder, 'utmSourceCustom', TextType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'required'           => false,
            'label'              => 'UTM Source (Custom)',
            'help'               => 'Uses UtmFaker with custom sources',
        ]);
        $this->addWithDefaults($builder, 'createdAt', DateTimeType::class, [
            'translation_domain' => false,
            'placeholder'        => false,
            'help'               => false,
            'required'           => false,
            'widget'             => 'single_text',
            'label'              => 'Created At',
        ]);
        $this->addSaveButton($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarketingCampaign::class,
        ]);
    }
}
