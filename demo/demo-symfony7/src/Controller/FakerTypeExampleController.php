<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FakerTypeExample;
use App\Form\FakerTypeExampleType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{connection}/faker_type_example')]
class FakerTypeExampleController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {}

    #[Route('/', name: 'faker_type_example_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, FakerTypeExample::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata = $em->getClassMetadata(FakerTypeExample::class);
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
            $fakertypeexample_list = $dbConnection->fetchAllAssociative($sql);
        } else {
            $fakertypeexample_list = $em->getRepository(FakerTypeExample::class)->findAll();
        }

        return $this->render('faker_type_example/index.html.twig', [
            'fakertypeexample_list' => $fakertypeexample_list,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'faker_type_example_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $fakertypeexample = new FakerTypeExample();
        $em = $this->doctrine->getManager($connection);
        $form = $this->createForm(FakerTypeExampleType::class, $fakertypeexample);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($fakertypeexample);
            $em->flush();

            $this->addFlash('success', 'FakerTypeExample created successfully!');

            return $this->redirectToRoute('faker_type_example_index', ['connection' => $connection]);
        }

        return $this->render('faker_type_example/new.html.twig', [
            'fakertypeexample' => $fakertypeexample,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'faker_type_example_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, FakerTypeExample::class);
        $fakertypeexample = $em->getRepository(FakerTypeExample::class)->find($id);

        if (!$fakertypeexample) {
            throw $this->createNotFoundException('FakerTypeExample not found');
        }

        return $this->render('faker_type_example/show.html.twig', [
            'fakertypeexample' => $fakertypeexample,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'faker_type_example_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $fakertypeexample = $em->getRepository(FakerTypeExample::class)->find($id);

        if (!$fakertypeexample) {
            throw $this->createNotFoundException('FakerTypeExample not found');
        }

        $form = $this->createForm(FakerTypeExampleType::class, $fakertypeexample);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'FakerTypeExample updated successfully!');

            return $this->redirectToRoute('faker_type_example_index', ['connection' => $connection]);
        }

        return $this->render('faker_type_example/edit.html.twig', [
            'fakertypeexample' => $fakertypeexample,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'faker_type_example_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $fakertypeexample = $em->getRepository(FakerTypeExample::class)->find($id);

        if (!$fakertypeexample) {
            throw $this->createNotFoundException('FakerTypeExample not found');
        }

        if ($this->isCsrfTokenValid('delete' . $fakertypeexample->getId(), $request->request->get('_token'))) {
            $em->remove($fakertypeexample);
            $em->flush();

            $this->addFlash('success', 'FakerTypeExample deleted successfully!');
        }

        return $this->redirectToRoute('faker_type_example_index', ['connection' => $connection]);
    }
}
