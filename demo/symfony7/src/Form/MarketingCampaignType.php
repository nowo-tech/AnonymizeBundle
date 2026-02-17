<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\MarketingCampaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarketingCampaignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('utmSource', TextType::class, [
                'required' => true,
                'label'    => 'UTM Source',
                'help'     => 'Uses UtmFaker with type: source, format: snake_case',
            ])
            ->add('utmMedium', TextType::class, [
                'required' => true,
                'label'    => 'UTM Medium',
                'help'     => 'Uses UtmFaker with type: medium, format: snake_case',
            ])
            ->add('utmCampaign', TextType::class, [
                'required' => true,
                'label'    => 'UTM Campaign',
                'help'     => 'Uses UtmFaker with type: campaign, format: snake_case',
            ])
            ->add('utmTerm', TextType::class, [
                'required' => false,
                'label'    => 'UTM Term',
                'help'     => 'Uses UtmFaker with type: term',
            ])
            ->add('utmContent', TextType::class, [
                'required' => false,
                'label'    => 'UTM Content',
                'help'     => 'Uses UtmFaker with type: content',
            ])
            ->add('utmSourceKebab', TextType::class, [
                'required' => false,
                'label'    => 'UTM Source (Kebab-case)',
                'help'     => 'Uses UtmFaker with format: kebab-case',
            ])
            ->add('utmSourceCustom', TextType::class, [
                'required' => false,
                'label'    => 'UTM Source (Custom)',
                'help'     => 'Uses UtmFaker with custom sources',
            ])
            ->add('createdAt', DateTimeType::class, [
                'required' => false,
                'widget'   => 'single_text',
                'label'    => 'Created At',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarketingCampaign::class,
        ]);
    }
}
