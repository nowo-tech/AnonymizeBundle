<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer;

use function sprintf;

/**
 * The deterministic rule every fixture anonymizer applies, whichever SPI calls it: the deprecated
 * EntityAnonymizerServiceInterface reads the table and key from the Doctrine metadata, RowAnonymizerInterface from the
 * AnonymizeTarget. Same input, same output, so any difference in the database comes from the bundle.
 *
 * - `status = 'skip-service'`: the service returns no update (the row is not counted as updated).
 * - otherwise the table's columns ({@see COLUMNS} for `ddd_patient`, {@see COLUMNS_BY_TABLE} for the other fixture
 *   tables) become `anon:{table}:{column}:{id}`; `{id}` joins every primary-key column with `/` (composite keys).
 */
final class PatientRedaction
{
    public const COLUMNS = ['email', 'full_name', 'contact_phone'];

    /** Columns rewritten in the other fixture tables. */
    public const COLUMNS_BY_TABLE = [
        'eq_ticket' => ['subject', 'requester'],
        'eq_party'  => ['name', 'email'],
    ];

    /**
     * @param list<string> $identifierColumns
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public static function row(string $table, array $identifierColumns, array $record): array
    {
        if (($record['status'] ?? null) === 'skip-service') {
            return [];
        }
        $id = implode('/', array_map(static fn (string $column): string => (string) $record[$column], $identifierColumns));

        $updates = [];
        foreach (self::COLUMNS_BY_TABLE[$table] ?? self::COLUMNS as $column) {
            $updates[$column] = sprintf('anon:%s:%s:%s', $table, $column, $id);
        }

        return $updates;
    }

    /**
     * @param list<string> $identifierColumns
     * @param array<int, array<string, mixed>> $records
     *
     * @return array<int, array<string, mixed>>
     */
    public static function rows(string $table, array $identifierColumns, array $records): array
    {
        $updates = [];
        foreach ($records as $index => $record) {
            $row = self::row($table, $identifierColumns, $record);
            if ($row !== []) {
                $updates[$index] = $row;
            }
        }

        return $updates;
    }
}
