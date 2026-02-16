# Example: Polymorphic entity and anonymization via service (e.g. AWS path migration)

This guide shows how to combine **Doctrine polymorphism (STI)** with **anonymization via a custom service** (`anonymizeService`) in a real-world scenario: entities that store **file paths** (e.g. AWS S3 keys) in the database, where “anonymizing” means **copying the file from one storage (e.g. production S3) to another (e.g. dev/anonymized S3)** and updating the stored path.

## When to use this pattern

- **Polymorphic entities**: You have a hierarchy (e.g. `AbstractDocument` → `StoredDocument`, `ExternalLinkDocument`) and only some subtypes store a path that needs special handling.
- **Non-trivial anonymization**: Replacing the path with a random string is not enough; you need to **migrate the underlying file** (e.g. from bucket A to bucket B) and then store the **new path** in the database.
- **One service per subtype**: Each subtype can have its own anonymizer service, so you keep logic out of the entity and in dedicated classes.

## Scenario: documents with storage paths

- **Table**: `documents` (Single Table Inheritance), discriminator `type` (`stored` | `link`).
- **Subtypes**:
  - `StoredDocument`: has a column `storage_path` (e.g. `s3://source-bucket/users/123/file.pdf`). We want to copy the file to a destination bucket and save the new path.
  - `ExternalLinkDocument`: has only a URL; we can anonymize it with a normal `AnonymizeProperty` (e.g. faker URL) and do **not** need a service.
- **Anonymization goal**: For each `StoredDocument` row, copy the object from the source AWS bucket to the destination bucket (or re-upload a placeholder) and update `storage_path` (and optionally `updated_at`, etc.) in the database.

## Flow (step by step)

1. You run `nowo:anonymize:run` (or with `--connection=default`, etc.).
2. The bundle discovers entities with `#[Anonymize]`. For the **default** entity manager it loads **all** records from `documents`; for **polymorphic** entities it restricts the query with `WHERE type = 'stored'` so only `StoredDocument` rows are processed when handling that child.
3. For each record, the bundle calls your **anonymizer service** (e.g. `StoredDocumentAnonymizerService`), passing the raw row (e.g. `id`, `type`, `storage_path`, `created_at`, …) and `dryRun`.
4. Inside the service you:
   - Read the current `storage_path` from `$record['storage_path']`.
   - If `$dryRun`: return the columns you *would* update (e.g. `['storage_path' => 's3://dest-bucket/anon/...']`) without performing the copy.
   - If not dry run: copy the object from the source bucket to the destination bucket (or generate a placeholder and upload it), compute the new path, then return `['storage_path' => $newPath]` (and any other columns to update).
5. The bundle executes an `UPDATE documents SET storage_path = ?, ... WHERE id = ?` with the returned map. Only the columns you return are updated.

So: **DB stores path → service reads path → copies file (or placeholder) to new storage → returns new path → bundle updates DB.**

## Code example

### 1. Entity (polymorphic STI, one subtype with service)

```php
// src/Entity/AbstractDocument.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'documents')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['stored' => StoredDocument::class, 'link' => ExternalLinkDocument::class])]
abstract class AbstractDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
}

// src/Entity/StoredDocument.php
namespace App\Entity;

use App\Service\StoredDocumentAnonymizerService;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;

#[ORM\Entity]
#[ORM\DiscriminatorValue('stored')]
#[Anonymize(anonymizeService: StoredDocumentAnonymizerService::class)]
class StoredDocument extends AbstractDocument
{
    #[ORM\Column(length: 500)]
    private ?string $storagePath = null;

    public function getStoragePath(): ?string { return $this->storagePath; }
    public function setStoragePath(?string $storagePath): void { $this->storagePath = $storagePath; }
}

// src/Entity/ExternalLinkDocument.php — no service; use AnonymizeProperty if needed
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;

#[ORM\Entity]
#[ORM\DiscriminatorValue('link')]
#[Anonymize]
class ExternalLinkDocument extends AbstractDocument
{
    #[ORM\Column(length: 500)]
    #[AnonymizeProperty(type: FakerType::URL)]
    private ?string $url = null;
    // ...
}
```

Only `StoredDocument` uses `anonymizeService`; the bundle will call the service only for rows with `type = 'stored'` and will load/update only those rows (polymorphic filtering is automatic).

### 2. Anonymizer service (copy from AWS source to destination, update path)

The service receives the **raw record** (keys = column names). You return a map **column name → new value** for every column you want to update. The bundle then runs a single `UPDATE` with those columns.

```php
// src/Service/StoredDocumentAnonymizerService.php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;

class StoredDocumentAnonymizerService implements EntityAnonymizerServiceInterface
{
    public function __construct(
        private AwsSourceBucketClient $sourceBucket,
        private AwsDestinationBucketClient $destBucket,
        private string $destPrefix = 'anonymized/',
    ) {}

    public function anonymize(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        array $record,
        bool $dryRun
    ): array {
        $currentPath = $record['storage_path'] ?? null;
        if ($currentPath === null || $currentPath === '') {
            return [];
        }

        // Build new key in destination (e.g. anonymized/{id}.pdf)
        $id = $record['id'] ?? 0;
        $extension = pathinfo(parse_url($currentPath, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'bin';
        $newKey = $this->destPrefix . $id . '.' . $extension;

        if ($dryRun) {
            // Only report what would be updated; do not copy the file
            return ['storage_path' => $this->destBucket->getPublicUrl($newKey)];
        }

        // Copy object from source bucket to destination bucket
        $sourceKey = $this->parseSourceKey($currentPath);
        $this->sourceBucket->copyTo($sourceKey, $this->destBucket->getName(), $newKey);

        $newPath = $this->destBucket->getPublicUrl($newKey);

        return ['storage_path' => $newPath];
    }

    private function parseSourceKey(string $path): string
    {
        // Example: s3://source-bucket/path/to/object.pdf → path/to/object.pdf
        if (str_starts_with($path, 's3://')) {
            $parts = parse_url($path);
            return ltrim($parts['path'] ?? '', '/');
        }
        return $path;
    }
}
```

- **Dry-run**: You return the same shape of updates (e.g. `['storage_path' => $newPath]`) but **do not** perform the copy. The bundle will not run `UPDATE` in dry-run; the command only reports what would change.
- **Real run**: Copy the file (or upload a placeholder), then return the new path so the bundle can update the row.

You can also return more columns (e.g. `updated_at`, `anonymized_at`) if your table has them.

### 3. Service registration (Symfony)

Register the anonymizer and your AWS clients in `config/services.yaml` (or attribute/constructor injection). The bundle resolves the service by id; using the class name is enough if the class is in `src/` and autowired:

```yaml
# config/services.yaml
services:
  App\Service\StoredDocumentAnonymizerService:
    arguments:
      $destPrefix: 'anonymized/%env(APP_ENV)%/'
```

No need to tag it; the bundle only needs the service to exist and to implement `EntityAnonymizerServiceInterface`.

### 4. Flow summary

| Step | Who | What |
|------|-----|------|
| 1 | You | Run `php bin/console nowo:anonymize:run` (optionally `--connection=default`, `--dry-run`). |
| 2 | Bundle | Finds `StoredDocument` with `#[Anonymize(anonymizeService: ...)]`, builds `SELECT * FROM documents WHERE type = 'stored'`. |
| 3 | Bundle | For each row, calls `StoredDocumentAnonymizerService::anonymize($em, $metadata, $record, $dryRun)`. |
| 4 | Your service | Reads `$record['storage_path']`, copies file from AWS source → destination (or in dry-run skips copy), returns `['storage_path' => $newPath]`. |
| 5 | Bundle | Runs `UPDATE documents SET storage_path = ? WHERE id = ?` with the returned values (skipped in dry-run). |

So: **DB path → service copies file to new storage → service returns new path → bundle updates DB.**

## Optional: placeholder instead of real copy

If you prefer not to copy the real file (e.g. for size or privacy), generate a placeholder (e.g. a small PDF or image) and upload it to the destination bucket; then return that new path. The flow is the same; only the service implementation changes (e.g. `$this->destBucket->uploadPlaceholder($newKey)` instead of `copyTo`).

## See also

- [USAGE.md](USAGE.md#anonymizing-via-a-custom-service-anonymizeservice) — `anonymizeService` and `EntityAnonymizerServiceInterface`.
- [USAGE.md](USAGE.md#polymorphic-entities-sticti) — Truncation and query filtering for polymorphic entities (STI/CTI).
- Demo entities: `SmsNotification` + `SmsNotificationAnonymizerService` in the bundle demos (symfony6/7/8).
