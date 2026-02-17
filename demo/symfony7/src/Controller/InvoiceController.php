<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Invoice;
use App\Form\InvoiceType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/{connection}/invoice')]
class InvoiceController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'invoice_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, Invoice::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(Invoice::class);
            $tableName = $metadata->getTableName();
            /** @var Connection $dbConnection */
            $dbConnection = $em->getConnection(); // @phpstan-ignore-line

            $columns = [];
            foreach ($metadata->getFieldNames() as $fieldName) {
                if ($fieldName !== 'anonymized') {
                    $fieldMapping = $metadata->getFieldMapping($fieldName);
                    $columns[]    = $dbConnection->quoteSingleIdentifier($fieldMapping['columnName'] ?? $fieldName);
                }
            }

            $sql     = sprintf('SELECT %s FROM %s', implode(', ', $columns), $dbConnection->quoteSingleIdentifier($tableName));
            $results = $dbConnection->fetchAllAssociative($sql);

            $invoices = [];
            foreach ($results as $row) {
                $invoice = new Invoice();
                foreach ($metadata->getFieldNames() as $fieldName) {
                    if ($fieldName !== 'anonymized') {
                        $columnName = $metadata->getColumnName($fieldName);
                        if (isset($row[$columnName])) {
                            $metadata->setFieldValue($invoice, $fieldName, $row[$columnName]);
                        }
                    }
                }
                $invoices[] = $invoice;
            }
        } else {
            $invoices = $em->getRepository(Invoice::class)->findAll();
        }

        return $this->render('invoice/index.html.twig', [
            'invoices'            => $invoices,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'invoice_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $invoice = new Invoice();
        $em      = $this->doctrine->getManager($connection);
        $form    = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($invoice);
            $em->flush();

            $this->addFlash('success', 'Invoice created successfully!');

            return $this->redirectToRoute('invoice_index', ['connection' => $connection]);
        }

        return $this->render('invoice/new.html.twig', [
            'invoice'    => $invoice,
            'form'       => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'invoice_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, Invoice::class);
        $invoice             = $em->getRepository(Invoice::class)->find($id);

        if (!$invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }

        return $this->render('invoice/show.html.twig', [
            'invoice'             => $invoice,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'invoice_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em      = $this->doctrine->getManager($connection);
        $invoice = $em->getRepository(Invoice::class)->find($id);

        if (!$invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Invoice updated successfully!');

            return $this->redirectToRoute('invoice_index', ['connection' => $connection]);
        }

        return $this->render('invoice/edit.html.twig', [
            'invoice'    => $invoice,
            'form'       => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'invoice_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em      = $this->doctrine->getManager($connection);
        $invoice = $em->getRepository(Invoice::class)->find($id);

        if (!$invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }

        if ($this->isCsrfTokenValid('delete' . $invoice->getId(), $request->request->get('_token'))) {
            $em->remove($invoice);
            $em->flush();

            $this->addFlash('success', 'Invoice deleted successfully!');
        }

        return $this->redirectToRoute('invoice_index', ['connection' => $connection]);
    }
}
