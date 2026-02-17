<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SystemLog;
use App\Form\SystemLogType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/{connection}/system-log')]
class SystemLogController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'system_log_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, SystemLog::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(SystemLog::class);
            $tableName = $metadata->getTableName();
            /** @var Connection $dbConnection */
            $dbConnection = $em->getConnection();

            $columns = [];
            foreach ($metadata->getFieldNames() as $fieldName) {
                if ($fieldName !== 'anonymized') {
                    $fieldMapping = $metadata->getFieldMapping($fieldName);
                    $columns[]    = $dbConnection->quoteSingleIdentifier($fieldMapping['columnName'] ?? $fieldName);
                }
            }

            $sql     = sprintf('SELECT %s FROM %s', implode(', ', $columns), $dbConnection->quoteSingleIdentifier($tableName));
            $results = $dbConnection->fetchAllAssociative($sql);

            $logs = [];
            foreach ($results as $row) {
                $log = new SystemLog();
                foreach ($metadata->getFieldNames() as $fieldName) {
                    if ($fieldName !== 'anonymized') {
                        $columnName = $metadata->getColumnName($fieldName);
                        if (isset($row[$columnName])) {
                            $metadata->setFieldValue($log, $fieldName, $row[$columnName]);
                        }
                    }
                }
                $logs[] = $log;
            }
        } else {
            $logs = $em->getRepository(SystemLog::class)->findAll();
        }

        return $this->render('system_log/index.html.twig', [
            'logs'                => $logs,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'system_log_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $log  = new SystemLog();
        $em   = $this->doctrine->getManager($connection);
        $form = $this->createForm(SystemLogType::class, $log);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'System log created successfully!');

            return $this->redirectToRoute('system_log_index', ['connection' => $connection]);
        }

        return $this->render('system_log/new.html.twig', [
            'log'        => $log,
            'form'       => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'system_log_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, SystemLog::class);
        $log                 = $em->getRepository(SystemLog::class)->find($id);

        if (!$log) {
            throw $this->createNotFoundException('System log not found');
        }

        return $this->render('system_log/show.html.twig', [
            'log'                 => $log,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'system_log_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em  = $this->doctrine->getManager($connection);
        $log = $em->getRepository(SystemLog::class)->find($id);

        if (!$log) {
            throw $this->createNotFoundException('System log not found');
        }

        $form = $this->createForm(SystemLogType::class, $log);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'System log updated successfully!');

            return $this->redirectToRoute('system_log_index', ['connection' => $connection]);
        }

        return $this->render('system_log/edit.html.twig', [
            'log'        => $log,
            'form'       => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'system_log_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em  = $this->doctrine->getManager($connection);
        $log = $em->getRepository(SystemLog::class)->find($id);

        if (!$log) {
            throw $this->createNotFoundException('System log not found');
        }

        if ($this->isCsrfTokenValid('delete' . $log->getId(), $request->request->get('_token'))) {
            $em->remove($log);
            $em->flush();

            $this->addFlash('success', 'System log deleted successfully!');
        }

        return $this->redirectToRoute('system_log_index', ['connection' => $connection]);
    }
}
