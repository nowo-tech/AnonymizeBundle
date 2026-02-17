<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserAccount;
use App\Form\UserAccountType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/{connection}/user_account')]
class UserAccountController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'user_account_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, UserAccount::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(UserAccount::class);
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

            $sql              = sprintf('SELECT %s FROM %s', implode(', ', $columns), $dbConnection->quoteSingleIdentifier($tableName));
            $useraccount_list = $dbConnection->fetchAllAssociative($sql);
        } else {
            $useraccount_list = $em->getRepository(UserAccount::class)->findAll();
        }

        return $this->render('user_account/index.html.twig', [
            'useraccount_list'    => $useraccount_list,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'user_account_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $useraccount = new UserAccount();
        $em          = $this->doctrine->getManager($connection);
        $form        = $this->createForm(UserAccountType::class, $useraccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($useraccount);
            $em->flush();

            $this->addFlash('success', 'UserAccount created successfully!');

            return $this->redirectToRoute('user_account_index', ['connection' => $connection]);
        }

        return $this->render('user_account/new.html.twig', [
            'useraccount' => $useraccount,
            'form'        => $form,
            'connection'  => $connection,
        ]);
    }

    #[Route('/{id}', name: 'user_account_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, UserAccount::class);
        $useraccount         = $em->getRepository(UserAccount::class)->find($id);

        if (!$useraccount) {
            throw $this->createNotFoundException('UserAccount not found');
        }

        return $this->render('user_account/show.html.twig', [
            'useraccount'         => $useraccount,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em          = $this->doctrine->getManager($connection);
        $useraccount = $em->getRepository(UserAccount::class)->find($id);

        if (!$useraccount) {
            throw $this->createNotFoundException('UserAccount not found');
        }

        $form = $this->createForm(UserAccountType::class, $useraccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'UserAccount updated successfully!');

            return $this->redirectToRoute('user_account_index', ['connection' => $connection]);
        }

        return $this->render('user_account/edit.html.twig', [
            'useraccount' => $useraccount,
            'form'        => $form,
            'connection'  => $connection,
        ]);
    }

    #[Route('/{id}', name: 'user_account_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em          = $this->doctrine->getManager($connection);
        $useraccount = $em->getRepository(UserAccount::class)->find($id);

        if (!$useraccount) {
            throw $this->createNotFoundException('UserAccount not found');
        }

        if ($this->isCsrfTokenValid('delete' . $useraccount->getId(), $request->request->get('_token'))) {
            $em->remove($useraccount);
            $em->flush();

            $this->addFlash('success', 'UserAccount deleted successfully!');
        }

        return $this->redirectToRoute('user_account_index', ['connection' => $connection]);
    }
}
