<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;

#[AsCommand(
    name: self::NAME,
    description: 'Manage Sina Weibo OAuth2 configuration'
)]
class SinaWeiboOAuth2ConfigCommand extends Command
{
    public const NAME = 'sina-weibo-oauth2:config';
    public function __construct(
        private SinaWeiboOAuth2ConfigRepository $configRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform (list|create|update|delete)')
            ->addOption('app-id', null, InputOption::VALUE_REQUIRED, 'App ID')
            ->addOption('app-secret', null, InputOption::VALUE_REQUIRED, 'App Secret')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'OAuth2 scope', 'email')
            ->addOption('active', null, InputOption::VALUE_OPTIONAL, 'Is active (true/false)', 'true')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Config ID for update/delete operations')
            ->setHelp('This command allows you to manage Sina Weibo OAuth2 configurations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');

        match ($action) {
            'list' => $this->listConfigs($io),
            'create' => $this->createConfig($input, $io),
            'update' => $this->updateConfig($input, $io),
            'delete' => $this->deleteConfig($input, $io),
            default => $io->error(sprintf('Unknown action: %s', $action))
        };

        return Command::SUCCESS;
    }

    private function listConfigs(SymfonyStyle $io): void
    {
        $configs = $this->configRepository->findAll();

        if (empty($configs)) {
            $io->info('No Sina Weibo OAuth2 configurations found.');
            return;
        }

        $rows = [];
        foreach ($configs as $config) {
            $rows[] = [
                $config->getId(),
                $config->getAppId(),
                str_repeat('*', strlen($config->getAppSecret()) - 4) . substr($config->getAppSecret(), -4),
                $config->getScope() ?? 'email',
                $config->isActive() ? 'Yes' : 'No',
                $config->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        $io->table(
            ['ID', 'App ID', 'App Secret', 'Scope', 'Active', 'Created At'],
            $rows
        );
    }

    private function createConfig(InputInterface $input, SymfonyStyle $io): void
    {
        $appId = $input->getOption('app-id');
        $appSecret = $input->getOption('app-secret');
        $scope = $input->getOption('scope');
        $active = filter_var($input->getOption('active'), FILTER_VALIDATE_BOOLEAN);

        if (empty($appId) || empty($appSecret)) {
            $io->error('App ID and App Secret are required for creating configuration');
            return;
        }

        $config = new SinaWeiboOAuth2Config();
        $config->setAppId($appId)
            ->setAppSecret($appSecret)
            ->setScope($scope)
            ->setValid($active);

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $io->success(sprintf('Created Sina Weibo OAuth2 configuration with ID: %d', $config->getId()));
    }

    private function updateConfig(InputInterface $input, SymfonyStyle $io): void
    {
        $id = $input->getOption('id');
        if (empty($id)) {
            $io->error('Config ID is required for update operation');
            return;
        }

        $config = $this->configRepository->find($id);
        if ($config === null) {
            $io->error(sprintf('Configuration with ID %d not found', $id));
            return;
        }

        if (($appId = $input->getOption('app-id')) !== null) {
            $config->setAppId($appId);
        }

        if (($appSecret = $input->getOption('app-secret')) !== null) {
            $config->setAppSecret($appSecret);
        }

        if (($scope = $input->getOption('scope')) !== null) {
            $config->setScope($scope);
        }

        if ($input->hasParameterOption('--active')) {
            $active = filter_var($input->getOption('active'), FILTER_VALIDATE_BOOLEAN);
            $config->setValid($active);
        }

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $io->success(sprintf('Updated Sina Weibo OAuth2 configuration with ID: %d', $config->getId()));
    }

    private function deleteConfig(InputInterface $input, SymfonyStyle $io): void
    {
        $id = $input->getOption('id');
        if (empty($id)) {
            $io->error('Config ID is required for delete operation');
            return;
        }

        $config = $this->configRepository->find($id);
        if ($config === null) {
            $io->error(sprintf('Configuration with ID %d not found', $id));
            return;
        }

        $this->entityManager->remove($config);
        $this->entityManager->flush();

        $io->success(sprintf('Deleted Sina Weibo OAuth2 configuration with ID: %d', $id));
    }
}