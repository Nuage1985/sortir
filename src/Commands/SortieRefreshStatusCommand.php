<?php

namespace App\Commands;

use App\Service\SortieStatusService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:sortie:refresh-status', description: 'Met à jour les états des sorties terminées')]
class SortieRefreshStatusCommand extends Command
{
    public function __construct(
        private readonly SortieStatusService $sortieStatusService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->sortieStatusService->refreshStatus();

        $output->writeln('États des sorties mis à jour.');

        return Command::SUCCESS;
    }
}
