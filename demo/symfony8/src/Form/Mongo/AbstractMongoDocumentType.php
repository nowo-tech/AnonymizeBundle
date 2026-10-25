<?php

declare(strict_types=1);

namespace App\Form\Mongo;

use ArrayAccess;
use DateTime;
use DateTimeInterface;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function count;
use function is_array;
use function is_object;
use function is_scalar;

/**
 * Base Form Type for the MongoDB demo documents (REQ-TWIG-005): each subclass declares its fields once; the type
 * builds the form, the edit data from a stored document and the document payload from the submitted data, so the
 * controllers no longer read raw request parameters.
 *
 * Field spec: name => [form type class, form options, kind, default, path?]; kind is one of string, int, float,
 * bool01 (choice "1"/"0" stored as bool), datetime (datetime-local stored as UTCDateTime); path is the nested
 * document path when the field is not stored at the top level.
 *
 * @extends AbstractType<array<string, mixed>>
 */
abstract class AbstractMongoDocumentType extends AbstractType
{
    /**
     * @return array<string, array{0: class-string, 1: array<string, mixed>, 2: string, 3: mixed, 4?: list<string>}>
     */
    abstract public static function fields(): array;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (static::fields() as $name => [$type, $fieldOptions, $kind]) {
            if ($kind === 'datetime') {
                $type = DateTimeType::class;
                $fieldOptions += [
                    'widget'       => 'single_text',
                    'input'        => 'string',
                    'input_format' => 'Y-m-d\TH:i',
                    'with_seconds' => false,
                    'required'     => false,
                ];
            }
            $builder->add($name, $type, $fieldOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['method' => 'POST']);
    }

    /**
     * Initial data of the "new" form.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $data = [];
        foreach (static::fields() as $name => $spec) {
            $data[$name] = $spec[2] === 'datetime' ? (new DateTime())->format('Y-m-d\TH:i') : $spec[3];
        }

        return $data;
    }

    /**
     * Edit data from a stored document (BSON document or array).
     *
     * @return array<string, mixed>
     */
    public static function fromDocument(mixed $document): array
    {
        $data = [];
        foreach (static::fields() as $name => $spec) {
            $value       = self::read($document, $spec[4] ?? [$name]);
            $data[$name] = match ($spec[2]) {
                'datetime'     => $value instanceof UTCDateTime ? $value->toDateTime()->format('Y-m-d\TH:i') : null,
                'bool01'       => $value === null ? $spec[3] : ($value ? '1' : '0'),
                'int', 'float' => $value === null ? null : $value,
                default        => $value === null ? $spec[3] : (is_scalar($value) ? (string) $value : $spec[3]),
            };
        }

        return $data;
    }

    /**
     * Document payload from the submitted form data, with the same defaults the demo used before the migration.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function toDocument(array $data): array
    {
        $document = [];
        foreach (static::fields() as $name => $spec) {
            $value = $data[$name] ?? null;
            $value = match ($spec[2]) {
                'datetime' => new UTCDateTime(new DateTime(is_scalar($value) && (string) $value !== '' ? (string) $value : 'now')),
                'int'      => (int) $value,
                'float'    => (float) $value,
                'bool01'   => $value === '1',
                default    => $value ?? '',
            };
            $path = $spec[4] ?? [$name];
            if (count($path) === 2) {
                $document[$path[0]][$path[1]] = $value;
            } else {
                $document[$path[0]] = $value;
            }
        }

        return $document;
    }

    /**
     * @param list<string> $path
     */
    private static function read(mixed $document, array $path): mixed
    {
        $value = $document;
        foreach ($path as $key) {
            if (is_array($value) || $value instanceof ArrayAccess) {
                $value = isset($value[$key]) ? $value[$key] : null;
            } elseif (is_object($value) && isset($value->{$key})) {
                $value = $value->{$key};
            } else {
                return null;
            }
        }

        return $value instanceof DateTimeInterface ? new UTCDateTime($value) : $value;
    }
}
