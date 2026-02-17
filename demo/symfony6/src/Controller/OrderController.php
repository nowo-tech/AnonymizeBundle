<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/{connection}/order')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'order_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, Order::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(Order::class);
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

            $orders = [];
            foreach ($results as $row) {
                $order = new Order();
                foreach ($metadata->getFieldNames() as $fieldName) {
                    if ($fieldName !== 'anonymized') {
                        $columnName = $metadata->getColumnName($fieldName);
                        if (isset($row[$columnName])) {
                            $metadata->setFieldValue($order, $fieldName, $row[$columnName]);
                        }
                    }
                }
                $orders[] = $order;
            }
        } else {
            $orders = $em->getRepository(Order::class)->findAll();
        }

        return $this->render('order/index.html.twig', [
            'orders'              => $orders,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $order = new Order();
        $em    = $this->doctrine->getManager($connection);
        $form  = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($order);
            $em->flush();

            $this->addFlash('success', 'Order created successfully!');

            return $this->redirectToRoute('order_index', ['connection' => $connection]);
        }

        return $this->render('order/new.html.twig', [
            'order'      => $order,
            'form'       => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, Order::class);
        $order               = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('order/show.html.twig', [
            'order'               => $order,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em    = $this->doctrine->getManager($connection);
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Order updated successfully!');

            return $this->redirectToRoute('order_index', ['connection' => $connection]);
        }

        return $this->render('order/edit.html.twig', [
            'order'      => $order,
            'form'       => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'order_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em    = $this->doctrine->getManager($connection);
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            $em->remove($order);
            $em->flush();

            $this->addFlash('success', 'Order deleted successfully!');
        }

        return $this->redirectToRoute('order_index', ['connection' => $connection]);
    }
}
