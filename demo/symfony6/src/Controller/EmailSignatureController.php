<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EmailSignature;
use App\Form\EmailSignatureType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/{connection}/email_signature')]
class EmailSignatureController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'email_signature_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, EmailSignature::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(EmailSignature::class);
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

            $sql                 = sprintf('SELECT %s FROM %s', implode(', ', $columns), $dbConnection->quoteSingleIdentifier($tableName));
            $emailsignature_list = $dbConnection->fetchAllAssociative($sql);
        } else {
            $emailsignature_list = $em->getRepository(EmailSignature::class)->findAll();
        }

        return $this->render('email_signature/index.html.twig', [
            'emailsignature_list' => $emailsignature_list,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'email_signature_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $emailsignature = new EmailSignature();
        $em             = $this->doctrine->getManager($connection);
        $form           = $this->createForm(EmailSignatureType::class, $emailsignature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($emailsignature);
            $em->flush();

            $this->addFlash('success', 'EmailSignature created successfully!');

            return $this->redirectToRoute('email_signature_index', ['connection' => $connection]);
        }

        return $this->render('email_signature/new.html.twig', [
            'emailsignature' => $emailsignature,
            'form'           => $form,
            'connection'     => $connection,
        ]);
    }

    #[Route('/{id}', name: 'email_signature_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, EmailSignature::class);
        $emailsignature      = $em->getRepository(EmailSignature::class)->find($id);

        if (!$emailsignature) {
            throw $this->createNotFoundException('EmailSignature not found');
        }

        return $this->render('email_signature/show.html.twig', [
            'emailsignature'      => $emailsignature,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'email_signature_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em             = $this->doctrine->getManager($connection);
        $emailsignature = $em->getRepository(EmailSignature::class)->find($id);

        if (!$emailsignature) {
            throw $this->createNotFoundException('EmailSignature not found');
        }

        $form = $this->createForm(EmailSignatureType::class, $emailsignature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'EmailSignature updated successfully!');

            return $this->redirectToRoute('email_signature_index', ['connection' => $connection]);
        }

        return $this->render('email_signature/edit.html.twig', [
            'emailsignature' => $emailsignature,
            'form'           => $form,
            'connection'     => $connection,
        ]);
    }

    #[Route('/{id}', name: 'email_signature_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em             = $this->doctrine->getManager($connection);
        $emailsignature = $em->getRepository(EmailSignature::class)->find($id);

        if (!$emailsignature) {
            throw $this->createNotFoundException('EmailSignature not found');
        }

        if ($this->isCsrfTokenValid('delete' . $emailsignature->getId(), $request->request->get('_token'))) {
            $em->remove($emailsignature);
            $em->flush();

            $this->addFlash('success', 'EmailSignature deleted successfully!');
        }

        return $this->redirectToRoute('email_signature_index', ['connection' => $connection]);
    }
}
