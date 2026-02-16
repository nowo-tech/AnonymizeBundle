<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CustomFakerExample;
use App\Form\CustomFakerExampleType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{connection}/custom_faker_example')]
class CustomFakerExampleController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {}

    #[Route('/', name: 'custom_faker_example_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, CustomFakerExample::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata = $em->getClassMetadata(CustomFakerExample::class);
            $tableName = $metadata->getTableName();
            /** @var Connection $dbConnection */
            $dbConnection = $em->getConnection();

            $columns = [];
            foreach ($metadata->getFieldNames() as $fieldName) {
                if ($fieldName !== 'anonymized') {
                    $fieldMapping = $metadata->getFieldMapping($fieldName);
                    $columns[] = $dbConnection->quoteSingleIdentifier($fieldMapping['columnName'] ?? $fieldName);
                }
            }

            $sql = sprintf('SELECT %s FROM %s', implode(', ', $columns), $dbConnection->quoteSingleIdentifier($tableName));
            $customfakerexample_list = $dbConnection->fetchAllAssociative($sql);
        } else {
            $customfakerexample_list = $em->getRepository(CustomFakerExample::class)->findAll();
        }

        return $this->render('custom_faker_example/index.html.twig', [
            'customfakerexample_list' => $customfakerexample_list,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'custom_faker_example_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $customfakerexample = new CustomFakerExample();
        $em = $this->doctrine->getManager($connection);
        $form = $this->createForm(CustomFakerExampleType::class, $customfakerexample);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($customfakerexample);
            $em->flush();

            $this->addFlash('success', 'CustomFakerExample created successfully!');

            return $this->redirectToRoute('custom_faker_example_index', ['connection' => $connection]);
        }

        return $this->render('custom_faker_example/new.html.twig', [
            'customfakerexample' => $customfakerexample,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'custom_faker_example_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, CustomFakerExample::class);
        $customfakerexample = $em->getRepository(CustomFakerExample::class)->find($id);

        if (!$customfakerexample) {
            throw $this->createNotFoundException('CustomFakerExample not found');
        }

        return $this->render('custom_faker_example/show.html.twig', [
            'customfakerexample' => $customfakerexample,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'custom_faker_example_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $customfakerexample = $em->getRepository(CustomFakerExample::class)->find($id);

        if (!$customfakerexample) {
            throw $this->createNotFoundException('CustomFakerExample not found');
        }

        $form = $this->createForm(CustomFakerExampleType::class, $customfakerexample);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'CustomFakerExample updated successfully!');

            return $this->redirectToRoute('custom_faker_example_index', ['connection' => $connection]);
        }

        return $this->render('custom_faker_example/edit.html.twig', [
            'customfakerexample' => $customfakerexample,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'custom_faker_example_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $customfakerexample = $em->getRepository(CustomFakerExample::class)->find($id);

        if (!$customfakerexample) {
            throw $this->createNotFoundException('CustomFakerExample not found');
        }

        if ($this->isCsrfTokenValid('delete' . $customfakerexample->getId(), $request->request->get('_token'))) {
            $em->remove($customfakerexample);
            $em->flush();

            $this->addFlash('success', 'CustomFakerExample deleted successfully!');
        }

        return $this->redirectToRoute('custom_faker_example_index', ['connection' => $connection]);
    }
}
