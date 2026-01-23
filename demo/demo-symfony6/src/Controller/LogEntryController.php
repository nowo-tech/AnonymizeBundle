<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LogEntry;
use App\Form\LogEntryType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{connection}/log-entry')]
class LogEntryController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {}

    #[Route('/', name: 'log_entry_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, LogEntry::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata = $em->getClassMetadata(LogEntry::class);
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
            $logEntryList = $dbConnection->fetchAllAssociative($sql);
        } else {
            $logEntryList = $em->getRepository(LogEntry::class)->findAll();
        }

        return $this->render('log_entry/index.html.twig', [
            'log_entry_list' => $logEntryList,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'log_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $logEntry = new LogEntry();
        $em = $this->doctrine->getManager($connection);
        $form = $this->createForm(LogEntryType::class, $logEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($logEntry);
            $em->flush();

            $this->addFlash('success', 'LogEntry created successfully!');

            return $this->redirectToRoute('log_entry_index', ['connection' => $connection]);
        }

        return $this->render('log_entry/new.html.twig', [
            'log_entry' => $logEntry,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'log_entry_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, LogEntry::class);
        $logEntry = $em->getRepository(LogEntry::class)->find($id);

        if (!$logEntry) {
            throw $this->createNotFoundException('LogEntry not found');
        }

        return $this->render('log_entry/show.html.twig', [
            'log_entry' => $logEntry,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'log_entry_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $logEntry = $em->getRepository(LogEntry::class)->find($id);

        if (!$logEntry) {
            throw $this->createNotFoundException('LogEntry not found');
        }

        $form = $this->createForm(LogEntryType::class, $logEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'LogEntry updated successfully!');

            return $this->redirectToRoute('log_entry_index', ['connection' => $connection]);
        }

        return $this->render('log_entry/edit.html.twig', [
            'log_entry' => $logEntry,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'log_entry_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $logEntry = $em->getRepository(LogEntry::class)->find($id);

        if (!$logEntry) {
            throw $this->createNotFoundException('LogEntry not found');
        }

        if ($this->isCsrfTokenValid('delete' . $logEntry->getId(), $request->request->get('_token'))) {
            $em->remove($logEntry);
            $em->flush();

            $this->addFlash('success', 'LogEntry deleted successfully!');
        }

        return $this->redirectToRoute('log_entry_index', ['connection' => $connection]);
    }
}
