<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function sprintf;

/**
 * Demo delete forms (REQ-TWIG-005): form name "delete_{id}", CSRF token id "delete{id}" (the id the controllers
 * always checked), POST to the entity/document delete route, confirm prompt on submit.
 */
final readonly class DeleteFormFactory
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $routeParams
     *
     * @return FormInterface<null>
     */
    public function create(string $id, string $route = '', array $routeParams = [], string $confirm = 'Are you sure?', string $class = 'nowo-ui-row-actions__form'): FormInterface
    {
        $options = [
            'csrf_token_id' => 'delete' . $id,
            'attr'          => [
                'class'    => $class,
                'onsubmit' => sprintf("return confirm('%s')", $confirm),
            ],
        ];
        if ($route !== '') {
            $options['action'] = $this->urlGenerator->generate($route, $routeParams);
        }

        return $this->formFactory->createNamed('delete_' . $id, CsrfOnlyType::class, null, $options);
    }

    /**
     * Binds the delete POST of the given id. A missing or invalid CSRF token (or a foreign origin) answers 422, so the
     * delete never runs.
     */
    public function isValidSubmission(Request $request, string $id): bool
    {
        $form = $this->create($id);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new UnprocessableEntityHttpException('Invalid CSRF token.');
        }

        return true;
    }
}
