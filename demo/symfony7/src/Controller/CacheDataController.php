<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CacheData;
use App\Form\CacheDataType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{connection}/cache-data')]
class CacheDataController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {}

    #[Route('/', name: 'cache_data_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, CacheData::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata = $em->getClassMetadata(CacheData::class);
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
            $cacheDataList = $dbConnection->fetchAllAssociative($sql);
        } else {
            $cacheDataList = $em->getRepository(CacheData::class)->findAll();
        }

        return $this->render('cache_data/index.html.twig', [
            'cache_data_list' => $cacheDataList,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'cache_data_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $cacheData = new CacheData();
        $em = $this->doctrine->getManager($connection);
        $form = $this->createForm(CacheDataType::class, $cacheData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle JSON string conversion from form
            $cacheValueString = $form->get('cacheValue')->getData();
            if (is_string($cacheValueString) && !empty($cacheValueString)) {
                $decoded = json_decode($cacheValueString, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cacheData->setCacheValue($decoded);
                } else {
                    $cacheData->setCacheValue([]);
                }
            }

            $em->persist($cacheData);
            $em->flush();

            $this->addFlash('success', 'CacheData created successfully!');

            return $this->redirectToRoute('cache_data_index', ['connection' => $connection]);
        }

        return $this->render('cache_data/new.html.twig', [
            'cache_data' => $cacheData,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'cache_data_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, CacheData::class);
        $cacheData = $em->getRepository(CacheData::class)->find($id);

        if (!$cacheData) {
            throw $this->createNotFoundException('CacheData not found');
        }

        return $this->render('cache_data/show.html.twig', [
            'cache_data' => $cacheData,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'cache_data_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $cacheData = $em->getRepository(CacheData::class)->find($id);

        if (!$cacheData) {
            throw $this->createNotFoundException('CacheData not found');
        }

        // Store original cacheValue for later use
        $originalCacheValue = $cacheData->getCacheValue();

        $form = $this->createForm(CacheDataType::class, $cacheData);
        // Set initial value for cacheValue field (JSON string)
        $form->get('cacheValue')->setData(json_encode($originalCacheValue, JSON_PRETTY_PRINT));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle JSON string conversion from form
            $cacheValueString = $form->get('cacheValue')->getData();
            if (is_string($cacheValueString) && !empty($cacheValueString)) {
                $decoded = json_decode($cacheValueString, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cacheData->setCacheValue($decoded);
                } else {
                    $cacheData->setCacheValue($originalCacheValue); // Keep original on error
                }
            } else {
                $cacheData->setCacheValue($originalCacheValue); // Keep original if empty
            }

            $em->flush();

            $this->addFlash('success', 'CacheData updated successfully!');

            return $this->redirectToRoute('cache_data_index', ['connection' => $connection]);
        }

        return $this->render('cache_data/edit.html.twig', [
            'cache_data' => $cacheData,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'cache_data_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $cacheData = $em->getRepository(CacheData::class)->find($id);

        if (!$cacheData) {
            throw $this->createNotFoundException('CacheData not found');
        }

        if ($this->isCsrfTokenValid('delete' . $cacheData->getId(), $request->request->get('_token'))) {
            $em->remove($cacheData);
            $em->flush();

            $this->addFlash('success', 'CacheData deleted successfully!');
        }

        return $this->redirectToRoute('cache_data_index', ['connection' => $connection]);
    }
}
