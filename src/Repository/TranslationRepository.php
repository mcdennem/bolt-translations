<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Exception\TypesException;

class TranslationRepository
{
    public const string TABLE = 'bolt_translations';

    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * @throws TypesException
     * @throws Exception
     */
    public function ensureTable(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist([self::TABLE])) {
            return;
        }

        $table = new Table(self::TABLE);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('domain', 'string', ['length' => 100]);
        $table->addColumn('trans_key', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 10]);
        $table->addColumn('message', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['domain', 'trans_key', 'locale'], 'bolt_translations_unique');

        $schemaManager->createTable($table);
    }

    /**
     * @throws Exception
     */
    public function save(string $domain, string $key, string $locale, ?string $message): void
    {
        $criteria = [
            'domain' => $domain,
            'trans_key' => $key,
            'locale' => $locale,
        ];

        $exists = $this->connection->fetchOne(
            'SELECT id FROM ' . self::TABLE . ' WHERE domain = ? AND trans_key = ? AND locale = ?',
            [$domain, $key, $locale]
        );

        if ($exists !== false) {
            $this->connection->update(self::TABLE, ['message' => $message], $criteria);

            return;
        }

        $this->connection->insert(self::TABLE, $criteria + ['message' => $message]);
    }

    /**
     * Removes a key for all locales.
     * @throws Exception
     */
    public function delete(string $domain, string $key): void
    {
        $this->connection->delete(self::TABLE, [
            'domain' => $domain,
            'trans_key' => $key,
        ]);
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getLocales(): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT DISTINCT locale FROM ' . self::TABLE . ' ORDER BY locale'
        );
    }

    /**
     * One row per domain + key, with a message per locale.
     *
     * @return array<int, array{domain: string, key: string, messages: array<string, ?string>}>
     * @throws Exception
     */
    public function getOverview(?string $search = null): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT domain, trans_key, locale, message FROM ' . self::TABLE . ' ORDER BY domain, trans_key'
        );

        $grouped = [];

        foreach ($rows as $row) {
            $id = $row['domain'] . '|' . $row['trans_key'];

            $grouped[$id] ??= [
                'domain' => $row['domain'],
                'key' => $row['trans_key'],
                'messages' => [],
            ];

            $grouped[$id]['messages'][$row['locale']] = $row['message'];
        }

        if ($search !== null && $search !== '') {
            $grouped = array_filter($grouped, function (array $item) use ($search): bool {
                $haystack = $item['key'] . ' ' . implode(' ', array_filter($item['messages']));

                return mb_stripos($haystack, $search) !== false;
            });
        }

        return array_values($grouped);
    }
}
