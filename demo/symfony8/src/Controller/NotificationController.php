<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AbstractNotification;
use App\Entity\EmailNotification;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\SchemaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function sprintf;

/**
 * Controller for polymorphic notifications (STI example).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[Route('/{connection}/notification')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SchemaService $schemaService
    ) {
    }

    #[Route('/', name: 'notification_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em                  = $this->doctrine->getManager($connection);
        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, EmailNotification::class);

        if (!$hasAnonymizedColumn) {
            /** @var ClassMetadata $metadata */
            $metadata  = $em->getClassMetadata(AbstractNotification::class);
            $tableName = $metadata->getTableName();
            /** @var Connection $dbConnection */
            $dbConnection = $em->getConnection();
            $cols         = [];
            foreach ($metadata->getFieldNames() as $fieldName) {
                if ($fieldName !== 'anonymized') {
                    $mapping = $metadata->getFieldMapping($fieldName);
                    $cols[]  = $dbConnection->quoteSingleIdentifier($mapping['columnName'] ?? $fieldName);
                }
            }
            $discCol  = $metadata->getDiscriminatorColumn();
            $discName = $discCol['name'] ?? 'type';
            $cols[]   = $dbConnection->quoteSingleIdentifier($discName);
            $sql      = sprintf('SELECT %s FROM %s ORDER BY id ASC', implode(', ', array_unique($cols)), $dbConnection->quoteSingleIdentifier($tableName));
            $rows     = $dbConnection->fetchAllAssociative($sql);
            $list     = array_map(static fn (array $row): array => ['item' => $row, 'type' => $row['type'] ?? $row[$discName] ?? ''], $rows);
        } else {
            $entities = $em->getRepository(AbstractNotification::class)->findBy([], ['id' => 'ASC']);
            $list     = array_map(static fn (AbstractNotification $n): array => [
                'item' => $n,
                'type' => $n instanceof EmailNotification ? 'email' : 'sms',
            ], $entities);
        }

        return $this->render('notification/index.html.twig', [
            'notification_list'   => $list,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }

    #[Route('/{id}', name: 'notification_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em           = $this->doctrine->getManager($connection);
        $notification = $em->getRepository(AbstractNotification::class)->find($id);

        if (!$notification) {
            throw $this->createNotFoundException('Notification not found');
        }

        $hasAnonymizedColumn = $this->schemaService->hasAnonymizedColumn($em, EmailNotification::class);
        $notificationType    = $notification instanceof EmailNotification ? 'email' : 'sms';

        return $this->render('notification/show.html.twig', [
            'notification'        => $notification,
            'notification_type'   => $notificationType,
            'connection'          => $connection,
            'hasAnonymizedColumn' => $hasAnonymizedColumn,
        ]);
    }
}
