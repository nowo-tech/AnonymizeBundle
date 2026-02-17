<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\MarketingCampaign;
use App\Form\MarketingCampaignType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

#[Route('/{connection}/marketing_campaign')]
class MarketingCampaignController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'marketing_campaign_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, MarketingCampaign::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(MarketingCampaign::class);
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

            $sql                    = sprintf('SELECT %s FROM %s', implode(', ', $columns), $dbConnection->quoteSingleIdentifier($tableName));
            $marketingcampaign_list = $dbConnection->fetchAllAssociative($sql);
        } else {
            $marketingcampaign_list = $em->getRepository(MarketingCampaign::class)->findAll();
        }

        return $this->render('marketing_campaign/index.html.twig', [
            'marketingcampaign_list' => $marketingcampaign_list,
            'connection'             => $connection,
            'hasAnonymizedColumn'    => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/new', name: 'marketing_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $marketingcampaign = new MarketingCampaign();
        $em                = $this->doctrine->getManager($connection);
        $form              = $this->createForm(MarketingCampaignType::class, $marketingcampaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($marketingcampaign);
            $em->flush();

            $this->addFlash('success', 'MarketingCampaign created successfully!');

            return $this->redirectToRoute('marketing_campaign_index', ['connection' => $connection]);
        }

        return $this->render('marketing_campaign/new.html.twig', [
            'marketingcampaign' => $marketingcampaign,
            'form'              => $form,
            'connection'        => $connection,
        ]);
    }

    #[Route('/{id}', name: 'marketing_campaign_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, MarketingCampaign::class);
        $marketingcampaign   = $em->getRepository(MarketingCampaign::class)->find($id);

        if (!$marketingcampaign) {
            throw $this->createNotFoundException('MarketingCampaign not found');
        }

        return $this->render('marketing_campaign/show.html.twig', [
            'marketingcampaign'   => $marketingcampaign,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}/edit', name: 'marketing_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em                = $this->doctrine->getManager($connection);
        $marketingcampaign = $em->getRepository(MarketingCampaign::class)->find($id);

        if (!$marketingcampaign) {
            throw $this->createNotFoundException('MarketingCampaign not found');
        }

        $form = $this->createForm(MarketingCampaignType::class, $marketingcampaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'MarketingCampaign updated successfully!');

            return $this->redirectToRoute('marketing_campaign_index', ['connection' => $connection]);
        }

        return $this->render('marketing_campaign/edit.html.twig', [
            'marketingcampaign' => $marketingcampaign,
            'form'              => $form,
            'connection'        => $connection,
        ]);
    }

    #[Route('/{id}', name: 'marketing_campaign_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em                = $this->doctrine->getManager($connection);
        $marketingcampaign = $em->getRepository(MarketingCampaign::class)->find($id);

        if (!$marketingcampaign) {
            throw $this->createNotFoundException('MarketingCampaign not found');
        }

        if ($this->isCsrfTokenValid('delete' . $marketingcampaign->getId(), $request->request->get('_token'))) {
            $em->remove($marketingcampaign);
            $em->flush();

            $this->addFlash('success', 'MarketingCampaign deleted successfully!');
        }

        return $this->redirectToRoute('marketing_campaign_index', ['connection' => $connection]);
    }
}
