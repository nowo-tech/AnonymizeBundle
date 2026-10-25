<?php

declare(strict_types=1);

namespace App\Twig;

use App\Form\DeleteFormFactory;
use Symfony\Component\Form\FormView;
use Twig\Attribute\AsTwigFunction;

/**
 * demo_delete_form(): delete-button form for list and show pages (REQ-TWIG-005). The lists render rows from several
 * Doctrine connections and MongoDB, so the Form view is built per row here, from the same factory the delete
 * actions validate with.
 */
final readonly class DeleteFormExtension
{
    public function __construct(private DeleteFormFactory $deleteForms)
    {
    }

    /**
     * @param array<string, mixed> $routeParams
     */
    #[AsTwigFunction('demo_delete_form')]
    public function deleteForm(string $route, array $routeParams, mixed $id, string $confirm = 'Are you sure?', string $class = 'd-inline'): FormView
    {
        return $this->deleteForms->create((string) $id, $route, $routeParams, $confirm, $class)->createView();
    }
}
