<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Person;
use App\Form\PersonType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{connection}/person')]
class PersonController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {}

    #[Route('/', name: 'person_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, Person::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata = $em->getClassMetadata(Person::class);
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
            $person_list = $dbConnection->fetchAllAssociative($sql);
        } else {
            $person_list = $em->getRepository(Person::class)->findAll();
        }

        return $this->render('person/index.html.twig', [
            'person_list' => $person_list,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'person_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $person = new Person();
        $em = $this->doctrine->getManager($connection);
        $form = $this->createForm(PersonType::class, $person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($person);
            $em->flush();

            $this->addFlash('success', 'Person created successfully!');

            return $this->redirectToRoute('person_index', ['connection' => $connection]);
        }

        return $this->render('person/new.html.twig', [
            'person' => $person,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'person_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, Person::class);
        $person = $em->getRepository(Person::class)->find($id);

        if (!$person) {
            throw $this->createNotFoundException('Person not found');
        }

        return $this->render('person/show.html.twig', [
            'person' => $person,
            'connection' => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'person_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $person = $em->getRepository(Person::class)->find($id);

        if (!$person) {
            throw $this->createNotFoundException('Person not found');
        }

        $form = $this->createForm(PersonType::class, $person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Person updated successfully!');

            return $this->redirectToRoute('person_index', ['connection' => $connection]);
        }

        return $this->render('person/edit.html.twig', [
            'person' => $person,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'person_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $person = $em->getRepository(Person::class)->find($id);

        if (!$person) {
            throw $this->createNotFoundException('Person not found');
        }

        if ($this->isCsrfTokenValid('delete' . $person->getId(), $request->request->get('_token'))) {
            $em->remove($person);
            $em->flush();

            $this->addFlash('success', 'Person deleted successfully!');
        }

        return $this->redirectToRoute('person_index', ['connection' => $connection]);
    }
}
