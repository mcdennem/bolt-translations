<?php

declare(strict_types=1);

namespace Mcdennem\BoltTranslations\Command;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Exception\TypesException;
use Mcdennem\BoltTranslations\Import\XliffImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'translations:import',
    description: 'Import translations from translations/*.xlf into the database'
)]
class ImportCommand extends Command
{
    public function __construct(
        private readonly XliffImporter $importer
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws TypesException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->importer->import();

        if ($result === []) {
            $io->warning('No .xlf files found in translations/.');

            return Command::SUCCESS;
        }

        foreach ($result as $file => $count) {
            $io->writeln(sprintf('%s: %d', $file, $count));
        }

        $io->success(sprintf('%d translations imported.', array_sum($result)));

        return Command::SUCCESS;
    }
}
