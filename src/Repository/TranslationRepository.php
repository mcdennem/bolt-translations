<?php


declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

class TranslationRepository
{
    public const TABLE = 'bolt_translations';

    public function __construct(
        private readonly Connection $connection
    )
    {
    }

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
}
