<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;

#[AsCommand(
    name: 'sina-weibo-oauth2:cleanup',
    description: 'Clean up expired OAuth2 states and tokens'
)]
class SinaWeiboOAuth2CleanupCommand extends Command
{
    public function __construct(
        private SinaWeiboOAuth2Service $oauth2Service
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be cleaned up without actually doing it')
            ->setHelp('This command cleans up expired OAuth2 states and tokens for Sina Weibo OAuth2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Running in dry-run mode. No changes will be made.');
        }

        $io->title('Sina Weibo OAuth2 Cleanup');

        try {
            if (!$dryRun) {
                $cleanedStates = $this->oauth2Service->cleanupExpiredStates();
                $io->success(sprintf('Cleaned up %d expired states', $cleanedStates));
            } else {
                $io->info('Would clean up expired states (dry-run mode)');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to perform cleanup: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}