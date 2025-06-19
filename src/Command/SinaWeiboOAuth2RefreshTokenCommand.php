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
    name: self::NAME,
    description: 'Refresh expired OAuth2 access tokens'
)]
class SinaWeiboOAuth2RefreshTokenCommand extends Command
{
    public const NAME = 'sina-weibo-oauth2:refresh-tokens';
    public function __construct(
        private SinaWeiboOAuth2Service $oauth2Service
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be refreshed without actually doing it')
            ->setHelp('This command refreshes expired OAuth2 access tokens for Sina Weibo OAuth2.
            
Note: Sina Weibo API does not support refresh tokens like other OAuth2 providers.
This command is provided for interface compatibility but will always report 0 refreshed tokens.
Users must re-authenticate when their tokens expire.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ((bool) $dryRun) {
            $io->note('Running in dry-run mode. No changes will be made.');
        }

        $io->title('Sina Weibo OAuth2 Token Refresh');
        
        $io->warning([
            'Sina Weibo API does not support refresh tokens.',
            'Users must re-authenticate when their tokens expire.',
            'This command will always return 0 refreshed tokens.'
        ]);

        try {
            if (!(bool) $dryRun) {
                $refreshedCount = $this->oauth2Service->refreshExpiredTokens();
                if ($refreshedCount > 0) {
                    $io->success(sprintf('Refreshed %d expired tokens', $refreshedCount));
                } else {
                    $io->info('No tokens were refreshed. Sina Weibo does not support refresh tokens.');
                }
            } else {
                $io->info('Would attempt to refresh expired tokens (dry-run mode)');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to refresh tokens: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}