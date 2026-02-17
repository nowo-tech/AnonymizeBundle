<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Example listener for AnonymizePropertyEvent.
 *
 * Dispatched before each property is anonymized. Use it to pre-process values,
 * e.g. download files from Amazon S3 and upload to another storage before
 * the field is replaced with an anonymized value.
 *
 * Requires Symfony 6.3+ for #[AsEventListener].
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizePropertySubscriber
{
    /**
     * Called before a property value is anonymized.
     *
     * Example: if the field stores an Amazon S3 URL/key, you can download
     * the file from S3, upload it to another site, and set the new URL
     * as the anonymized value so the DB stores the new location.
     */
    #[AsEventListener(event: AnonymizePropertyEvent::class)]
    public function onAnonymizeProperty(AnonymizePropertyEvent $event): void
    {
        $originalValue = $event->getOriginalValue();
        $propertyName  = $event->getPropertyName();
        $columnName    = $event->getColumnName();

        // Only act on properties that look like file/URL storage (customize condition to your entities/columns)
        // if ($columnName !== 'file_url' && $columnName !== 's3_key') {
        //     return;
        // }

        // if ($originalValue === null || $originalValue === '') {
        //     return;
        // }

        /*
         * Example: migrate file from Amazon S3 to another storage before anonymizing the field.
         *
         * // 1) Resolve S3 URL or key from $originalValue (e.g. full URL or bucket key)
         * // $s3Client = new \Aws\S3\S3Client([...]);
         * // $bucket = 'your-bucket';
         * // $key = $this->parseS3KeyFromValue($originalValue);
         *
         * // 2) Download file from S3
         * // $tempPath = sys_get_temp_dir() . '/' . uniqid('s3_', true);
         * // $s3Client->getObject(['Bucket' => $bucket, 'Key' => $key, 'SaveAs' => $tempPath]);
         *
         * // 3) Upload to new storage (e.g. another S3 bucket, CDN, or different provider)
         * // $newUrl = $this->uploadToNewStorage($tempPath, $propertyName, $event->getRecord());
         *
         * // 4) Use the new URL as the anonymized value (so the DB stores the new location)
         * // $event->setAnonymizedValue($newUrl);
         *
         * // 5) Optional: skip default anonymization and only keep the new value
         * // (already done by setAnonymizedValue)
         *
         * // @unlink($tempPath);
         */
    }
}
