<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Command;

use Mcdennem\BoltTranslations\Repository\TranslationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Translation\Loader\XliffFileLoader;

#[AsCommand(
    name: 'translations:import',
    description: 'Import translations from translations/*.xlf into the database'
)]
class ImportCommand extends Command
{
    public function __construct(
        private readonly TranslationRepository $repository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->repository->ensureTable();

        $files = glob($this->projectDir . '/translations/*.xlf') ?: [];

        if ($files === []) {
            $io->warning('No .xlf files found in translations/.');

            return Command::SUCCESS;
        }

        $loader = new XliffFileLoader();
        $count = 0;

        foreach ($files as $file) {
            // messages+intl-icu.nl.xlf → domain "messages", locale "nl"
            $name = basename($file, '.xlf');
            $locale = substr($name, strrpos($name, '.') + 1);
            $domain = substr($name, 0, strrpos($name, '.'));

            $catalogue = $loader->load($file, $locale, $domain);

            foreach ($catalogue->all() as $catalogueDomain => $messages) {
                $cleanDomain = str_replace('+intl-icu', '', $catalogueDomain);

                foreach ($messages as $key => $message) {
                    $this->repository->save($cleanDomain, (string) $key, $locale, $message);
                    $count++;
                }
            }

            $io->writeln(basename($file));
        }

        $io->success(sprintf('%d translations imported.', $count));

        return Command::SUCCESS;
    }
}
