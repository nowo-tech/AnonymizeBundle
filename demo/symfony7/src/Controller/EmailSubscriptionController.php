<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EmailSubscription;
use App\Form\EmailSubscriptionType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/{connection}/email-subscription')]
class EmailSubscriptionController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'email_subscription_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, EmailSubscription::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(EmailSubscription::class);
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

            $subscriptions = [];
            foreach ($results as $row) {
                $subscription = new EmailSubscription();
                foreach ($metadata->getFieldNames() as $fieldName) {
                    if ($fieldName !== 'anonymized') {
                        $columnName = $metadata->getColumnName($fieldName);
                        if (isset($row[$columnName])) {
                            $metadata->setFieldValue($subscription, $fieldName, $row[$columnName]);
                        }
                    }
                }
                $subscriptions[] = $subscription;
            }
        } else {
            $subscriptions = $em->getRepository(EmailSubscription::class)->findAll();
        }

        return $this->render('email_subscription/index.html.twig', [
            'subscriptions'       => $subscriptions,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'email_subscription_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $subscription = new EmailSubscription();
        $em           = $this->doctrine->getManager($connection);
        $form         = $this->createForm(EmailSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($subscription);
            $em->flush();

            $this->addFlash('success', 'Email subscription created successfully!');

            return $this->redirectToRoute('email_subscription_index', ['connection' => $connection]);
        }

        return $this->render('email_subscription/new.html.twig', [
            'subscription' => $subscription,
            'form'         => $form,
            'connection'   => $connection,
        ]);
    }

    #[Route('/{id}', name: 'email_subscription_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, EmailSubscription::class);
        $subscription        = $em->getRepository(EmailSubscription::class)->find($id);

        if (!$subscription) {
            throw $this->createNotFoundException('Email subscription not found');
        }

        return $this->render('email_subscription/show.html.twig', [
            'subscription'        => $subscription,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'email_subscription_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em           = $this->doctrine->getManager($connection);
        $subscription = $em->getRepository(EmailSubscription::class)->find($id);

        if (!$subscription) {
            throw $this->createNotFoundException('Email subscription not found');
        }

        $form = $this->createForm(EmailSubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Email subscription updated successfully!');

            return $this->redirectToRoute('email_subscription_index', ['connection' => $connection]);
        }

        return $this->render('email_subscription/edit.html.twig', [
            'subscription' => $subscription,
            'form'         => $form,
            'connection'   => $connection,
        ]);
    }

    #[Route('/{id}', name: 'email_subscription_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em           = $this->doctrine->getManager($connection);
        $subscription = $em->getRepository(EmailSubscription::class)->find($id);

        if (!$subscription) {
            throw $this->createNotFoundException('Email subscription not found');
        }

        if ($this->isCsrfTokenValid('delete' . $subscription->getId(), $request->request->get('_token'))) {
            $em->remove($subscription);
            $em->flush();

            $this->addFlash('success', 'Email subscription deleted successfully!');
        }

        return $this->redirectToRoute('email_subscription_index', ['connection' => $connection]);
    }
}
