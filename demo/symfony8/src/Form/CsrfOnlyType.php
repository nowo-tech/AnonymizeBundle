<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * REQ-DEMO-015: a FormKit form (profile `demo`): CSRF-only POST (demo row deletes) with a `delete` submit button; the
 * page renders it, with its own label where it needs one. Empty block prefix keeps `_token` flat.
 *
 * @extends AbstractType<null>
 */
#[FormKitConfig('demo')]
final class CsrfOnlyType extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'delete', SubmitType::class, [
            'label'              => 'Delete',
            'translation_domain' => false,
            'attr'               => ['class' => 'nowo-ui-btn nowo-ui-btn--sm nowo-ui-btn--danger'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_token_id'   => 'demo',
            'csrf_field_name' => '_token',
        ]);
        $resolver->setAllowedTypes('csrf_token_id', 'string');
    }
}
