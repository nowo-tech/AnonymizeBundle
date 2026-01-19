<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{connection}/customer')]
class CustomerController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine
    ) {}

    #[Route('/', name: 'customer_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->hasAnonymizedColumn($em, Customer::class);
        
        // Use custom query if column doesn't exist to avoid SQL errors
        if (!$hasAnonymizedColumn) {
            $customers = $em->createQueryBuilder()
                ->select('c')
                ->from(Customer::class, 'c')
                ->getQuery()
                ->getResult();
        } else {
            $customers = $em->getRepository(Customer::class)->findAll();
        }

        return $this->render('customer/index.html.twig', [
            'customers' => $customers,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $customer = new Customer();
        $em = $this->doctrine->getManager($connection);
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($customer);
            $em->flush();

            $this->addFlash('success', 'Customer created successfully!');

            return $this->redirectToRoute('customer_index', ['connection' => $connection]);
        }

        return $this->render('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'customer_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->hasAnonymizedColumn($em, Customer::class);
        $customer = $em->getRepository(Customer::class)->find($id);

        if (!$customer) {
            throw $this->createNotFoundException('Customer not found');
        }

        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $customer = $em->getRepository(Customer::class)->find($id);

        if (!$customer) {
            throw $this->createNotFoundException('Customer not found');
        }

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Customer updated successfully!');

            return $this->redirectToRoute('customer_index', ['connection' => $connection]);
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'customer_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $customer = $em->getRepository(Customer::class)->find($id);

        if (!$customer) {
            throw $this->createNotFoundException('Customer not found');
        }

        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->request->get('_token'))) {
            $em->remove($customer);
            $em->flush();

            $this->addFlash('success', 'Customer deleted successfully!');
        }

        return $this->redirectToRoute('customer_index', ['connection' => $connection]);
    }

    /**
     * Checks if the anonymized column exists in the database table.
     */
    private function hasAnonymizedColumn(EntityManagerInterface $em, string $entityClass): bool
    {
        try {
            $metadata = $em->getClassMetadata($entityClass);
            $tableName = $metadata->getTableName();
            $connection = $em->getConnection();
            $schemaManager = $connection->createSchemaManager();

            if (!$schemaManager->tablesExist([$tableName])) {
                return false;
            }

            $columns = $schemaManager->listTableColumns($tableName);
            foreach ($columns as $column) {
                if ($column->getName() === 'anonymized') {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
