<?php


declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Import;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Exception\TypesException;
use Mcdennem\BoltTranslations\Repository\TranslationRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Translation\Loader\XliffFileLoader;

/**
 * Imports translations from translations/*.xlf into the database.
 */
readonly class XliffImporter
{
    public function __construct(
        private TranslationRepository $repository,
        #[Autowire('%kernel.project_dir%')]
        private string                $projectDir
    )
    {
    }

    /**
     * @return array<string, int> Number of imported translations per file name
     * @throws Exception
     * @throws TypesException
     */
    public function import(): array
    {
        $this->repository->ensureTable();

        $files = glob($this->projectDir . '/translations/*.xlf') ?: [];
        $loader = new XliffFileLoader();
        $result = [];

        foreach ($files as $file) {
            // messages+intl-icu.nl.xlf → domain "messages", locale "nl"
            $name = basename($file, '.xlf');
            $locale = substr($name, strrpos($name, '.') + 1);
            $domain = substr($name, 0, strrpos($name, '.'));

            $catalogue = $loader->load($file, $locale, $domain);
            $count = 0;

            foreach ($catalogue->all() as $catalogueDomain => $messages) {
                $cleanDomain = str_replace('+intl-icu', '', $catalogueDomain);

                foreach ($messages as $key => $message) {
                    $this->repository->save($cleanDomain, (string)$key, $locale, $message);
                    $count++;
                }
            }

            $result[basename($file)] = $count;
        }

        return $result;
    }
}
