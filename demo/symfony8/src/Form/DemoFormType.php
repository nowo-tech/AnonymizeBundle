<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * REQ-DEMO-015: base of every demo form. A FormKit form (profile `demo`, inherited by the subclasses) with CSRF: the
 * subclasses add their fields with the FormKit `add*()` helpers and finish with {@see addSaveButton()}.
 *
 * @template TData
 *
 * @extends AbstractType<TData>
 */
#[FormKitConfig('demo')]
abstract class DemoFormType extends AbstractType
{
    use FormOptionsTrait;

    /**
     * The submit button; the page renders it with its own label (`form_row(form.save, {label: 'Create User'})`).
     *
     * @param FormBuilderInterface<mixed> $builder
     */
    protected function addSaveButton(FormBuilderInterface $builder): void
    {
        $this->addWithDefaults($builder, 'save', SubmitType::class, [
            'label'              => 'Save',
            'translation_domain' => false,
            'attr'               => ['class' => 'nowo-ui-btn nowo-ui-btn--primary'],
        ]);
    }
}
