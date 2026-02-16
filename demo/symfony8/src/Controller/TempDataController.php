<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\TempData;
use App\Form\TempDataType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{connection}/temp-data')]
class TempDataController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {}

    #[Route('/', name: 'temp_data_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, TempData::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata = $em->getClassMetadata(TempData::class);
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
            $tempDataList = $dbConnection->fetchAllAssociative($sql);
        } else {
            $tempDataList = $em->getRepository(TempData::class)->findAll();
        }

        return $this->render('temp_data/index.html.twig', [
            'temp_data_list' => $tempDataList,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'temp_data_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $tempData = new TempData();
        $em = $this->doctrine->getManager($connection);
        $form = $this->createForm(TempDataType::class, $tempData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tempData);
            $em->flush();

            $this->addFlash('success', 'TempData created successfully!');

            return $this->redirectToRoute('temp_data_index', ['connection' => $connection]);
        }

        return $this->render('temp_data/new.html.twig', [
            'temp_data' => $tempData,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'temp_data_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, TempData::class);
        $tempData = $em->getRepository(TempData::class)->find($id);

        if (!$tempData) {
            throw $this->createNotFoundException('TempData not found');
        }

        return $this->render('temp_data/show.html.twig', [
            'temp_data' => $tempData,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'temp_data_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $tempData = $em->getRepository(TempData::class)->find($id);

        if (!$tempData) {
            throw $this->createNotFoundException('TempData not found');
        }

        $form = $this->createForm(TempDataType::class, $tempData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'TempData updated successfully!');

            return $this->redirectToRoute('temp_data_index', ['connection' => $connection]);
        }

        return $this->render('temp_data/edit.html.twig', [
            'temp_data' => $tempData,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'temp_data_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $tempData = $em->getRepository(TempData::class)->find($id);

        if (!$tempData) {
            throw $this->createNotFoundException('TempData not found');
        }

        if ($this->isCsrfTokenValid('delete' . $tempData->getId(), $request->request->get('_token'))) {
            $em->remove($tempData);
            $em->flush();

            $this->addFlash('success', 'TempData deleted successfully!');
        }

        return $this->redirectToRoute('temp_data_index', ['connection' => $connection]);
    }
}
