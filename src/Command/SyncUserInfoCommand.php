<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2TokenRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboUserInfoRepository;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboApiService;

/**
 * 同步微博用户信息命令
 * 用于批量更新用户的粉丝数、关注数等统计信息
 */
#[AsCommand(
    name: 'weibo:sync-user-info',
    description: '同步微博用户信息（粉丝数、关注数等）'
)]
class SyncUserInfoCommand extends Command
{
    public function __construct(
        private readonly SinaWeiboOAuth2TokenRepository $tokenRepository,
        private readonly SinaWeiboUserInfoRepository $userInfoRepository,
        private readonly SinaWeiboApiService $apiService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('weibo-uid', InputArgument::OPTIONAL, '指定要同步的微博用户UID')
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, '同步N天前更新的用户信息', 7)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, '每次最多同步的用户数量', 100)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '试运行模式，不实际更新数据')
            ->setHelp('
此命令用于同步微博用户的统计信息，包括粉丝数、关注数、微博数等。

示例用法：
  php bin/console weibo:sync-user-info                    # 同步所有需要更新的用户
  php bin/console weibo:sync-user-info 123456789          # 同步指定UID的用户
  php bin/console weibo:sync-user-info --days=1           # 同步1天前更新的用户
  php bin/console weibo:sync-user-info --limit=50         # 限制每次同步50个用户
  php bin/console weibo:sync-user-info --dry-run          # 试运行模式
');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $weiboUid = $input->getArgument('weibo-uid');
        $days = (int)$input->getOption('days');
        $limit = (int)$input->getOption('limit');
        $dryRun = $input->getOption('dry-run');

        $io->title('微博用户信息同步');

        if ($dryRun) {
            $io->note('运行在试运行模式，不会实际更新数据');
        }

        try {
            $usersToSync = $this->getUsersToSync($weiboUid, $days, $limit);

            if (empty($usersToSync)) {
                $io->success('没有需要同步的用户');
                return Command::SUCCESS;
            }

            $io->note(sprintf('找到 %d 个用户需要同步', count($usersToSync)));

            $successCount = 0;
            $errorCount = 0;

            foreach ($usersToSync as $userInfo) {
                try {
                    // 查找有效的访问令牌
                    $token = $this->findValidTokenForUser($userInfo->getWeiboUid());

                    if (!$token) {
                        $io->warning(sprintf('用户 %s 没有有效的访问令牌，跳过', $userInfo->getWeiboUid()));
                        continue;
                    }

                    if (!$dryRun) {
                        // 调用API获取最新用户信息
                        $updatedUser = $this->apiService->getUserInfo($token);

                        $io->text(sprintf(
                            '更新用户 %s：粉丝 %d -> %d，关注 %d -> %d，微博 %d -> %d',
                            $userInfo->getScreenName(),
                            $userInfo->getFollowersCount(),
                            $updatedUser->getFollowersCount(),
                            $userInfo->getFriendsCount(),
                            $updatedUser->getFriendsCount(),
                            $userInfo->getStatusesCount(),
                            $updatedUser->getStatusesCount()
                        ));
                    } else {
                        $io->text(sprintf(
                            '将同步用户：%s (%s)',
                            $userInfo->getScreenName(),
                            $userInfo->getWeiboUid()
                        ));
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $io->error(sprintf(
                        '同步用户 %s 失败：%s',
                        $userInfo->getWeiboUid(),
                        $e->getMessage()
                    ));

                    $this->logger->error('Failed to sync user info', [
                        'weibo_uid' => $userInfo->getWeiboUid(),
                        'error' => $e->getMessage(),
                        'exception' => $e
                    ]);
                }
            }

            if (!$dryRun) {
                $this->entityManager->flush();
            }

            $io->success(sprintf(
                '同步完成！成功：%d，失败：%d',
                $successCount,
                $errorCount
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('同步过程中发生错误：' . $e->getMessage());
            $this->logger->error('User info sync command failed', [
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * 获取需要同步的用户列表
     */
    private function getUsersToSync(?string $weiboUid, int $days, int $limit): array
    {
        if ($weiboUid) {
            $user = $this->userInfoRepository->findByWeiboUid($weiboUid);
            return $user ? [$user] : [];
        }

        $beforeDate = new \DateTime("-{$days} days");
        return $this->userInfoRepository->findUsersNeedUpdate($beforeDate, $limit);
    }

    /**
     * 查找用户的有效访问令牌
     */
    private function findValidTokenForUser(string $weiboUid): ?\Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token
    {
        $tokens = $this->tokenRepository->findBy(['weiboUid' => $weiboUid], ['createTime' => 'DESC']);

        foreach ($tokens as $token) {
            if ($token->isTokenValid()) {
                return $token;
            }
        }

        return null;
    }
}
