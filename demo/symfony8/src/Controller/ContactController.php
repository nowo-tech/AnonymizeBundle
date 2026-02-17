<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/{connection}/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'contact_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, Contact::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(Contact::class);
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

            $sql      = sprintf('SELECT %s FROM %s', implode(', ', $columns), $dbConnection->quoteSingleIdentifier($tableName));
            $contacts = $dbConnection->fetchAllAssociative($sql);
        } else {
            $contacts = $em->getRepository(Contact::class)->findAll();
        }

        return $this->render('contact/index.html.twig', [
            'contacts'            => $contacts,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $contact = new Contact();
        $em      = $this->doctrine->getManager($connection);
        $form    = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($contact);
            $em->flush();

            $this->addFlash('success', 'Contact created successfully!');

            return $this->redirectToRoute('contact_index', ['connection' => $connection]);
        }

        return $this->render('contact/new.html.twig', [
            'contact'    => $contact,
            'form'       => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'contact_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, Contact::class);
        $contact             = $em->getRepository(Contact::class)->find($id);

        if (!$contact) {
            throw $this->createNotFoundException('Contact not found');
        }

        return $this->render('contact/show.html.twig', [
            'contact'             => $contact,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em      = $this->doctrine->getManager($connection);
        $contact = $em->getRepository(Contact::class)->find($id);

        if (!$contact) {
            throw $this->createNotFoundException('Contact not found');
        }

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Contact updated successfully!');

            return $this->redirectToRoute('contact_index', ['connection' => $connection]);
        }

        return $this->render('contact/edit.html.twig', [
            'contact'    => $contact,
            'form'       => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'contact_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em      = $this->doctrine->getManager($connection);
        $contact = $em->getRepository(Contact::class)->find($id);

        if (!$contact) {
            throw $this->createNotFoundException('Contact not found');
        }

        if ($this->isCsrfTokenValid('delete' . $contact->getId(), $request->request->get('_token'))) {
            $em->remove($contact);
            $em->flush();

            $this->addFlash('success', 'Contact deleted successfully!');
        }

        return $this->redirectToRoute('contact_index', ['connection' => $connection]);
    }
}
