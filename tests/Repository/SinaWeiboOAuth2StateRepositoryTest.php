<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2StateRepository::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2StateRepositoryTest extends AbstractRepositoryTestCase
{
    private SinaWeiboOAuth2StateRepository $repository;

    private SinaWeiboOAuth2Config $config;

    public function testFindValidStateReturnsNullWhenStateNotFound(): void
    {
        $result = $this->repository->findValidState('non_existent_state');

        $this->assertNull($result);
    }

    public function testFindValidStateReturnsNullWhenStateIsUsed(): void
    {
        $state = $this->createStateWithData('used_state', $this->config, 30);
        $state->markAsUsed();

        $this->persistAndFlush($state);

        $result = $this->repository->findValidState('used_state');

        $this->assertNull($result);
    }

    public function testFindValidStateReturnsNullWhenStateIsExpired(): void
    {
        $state = $this->createStateWithData('expired_state', $this->config, -1);

        $this->persistAndFlush($state);

        $result = $this->repository->findValidState('expired_state');

        $this->assertNull($result);
    }

    public function testFindValidStateReturnsStateWhenValid(): void
    {
        $state = $this->createStateWithData('valid_state', $this->config, 30);

        $this->persistAndFlush($state);

        $result = $this->repository->findValidState('valid_state');

        $this->assertNotNull($result);
        $this->assertSame('valid_state', $result->getState());
        $this->assertFalse($result->isUsed());
        $this->assertFalse($result->isExpired());
    }

    public function testFindValidStateWithMultipleStatesReturnsCorrectOne(): void
    {
        $validState = $this->createStateWithData('valid_state', $this->config, 30);
        $usedState = $this->createStateWithData('used_state', $this->config, 30);
        $usedState->markAsUsed();
        $expiredState = $this->createStateWithData('expired_state', $this->config, -1);

        $this->persistAndFlush($validState);
        $this->persistAndFlush($usedState);
        $this->persistAndFlush($expiredState);

        $result = $this->repository->findValidState('valid_state');
        $this->assertNotNull($result);
        $this->assertSame('valid_state', $result->getState());

        $result = $this->repository->findValidState('used_state');
        $this->assertNull($result);

        $result = $this->repository->findValidState('expired_state');
        $this->assertNull($result);
    }

    public function testCleanupExpiredStatesRemovesExpiredAndUsedStates(): void
    {
        $initialCount = $this->repository->count([]);

        $validState = $this->createStateWithData('valid_state', $this->config, 30);
        $usedState = $this->createStateWithData('used_state', $this->config, 30);
        $usedState->markAsUsed();
        $expiredState = $this->createStateWithData('expired_state', $this->config, -1);

        $this->persistAndFlush($validState);
        $this->persistAndFlush($usedState);
        $this->persistAndFlush($expiredState);

        $beforeCleanupCount = $this->repository->count([]);
        $deletedCount = $this->repository->cleanupExpiredStates();
        $afterCleanupCount = $this->repository->count([]);

        $this->assertGreaterThanOrEqual(2, $deletedCount);
        $this->assertSame($beforeCleanupCount - $afterCleanupCount, $deletedCount);

        self::getEntityManager()->clear();

        $found = self::getEntityManager()->find(SinaWeiboOAuth2State::class, $validState->getId());
        $this->assertNotNull($found);

        $this->assertEntityNotExists(SinaWeiboOAuth2State::class, $usedState->getId());
        $this->assertEntityNotExists(SinaWeiboOAuth2State::class, $expiredState->getId());
    }

    public function testCleanupExpiredStatesReturnsZeroWhenNoStatesToCleanup(): void
    {
        $em = self::getEntityManager();
        $existingStates = $this->repository->findAll();
        foreach ($existingStates as $state) {
            $em->remove($state);
        }
        $em->flush();

        $validState = $this->createStateWithData('valid_state', $this->config, 30);

        $this->persistAndFlush($validState);

        $deletedCount = $this->repository->cleanupExpiredStates();

        $this->assertSame(0, $deletedCount);
    }

    public function testCountActiveStatesReturnsCorrectCount(): void
    {
        $em = self::getEntityManager();
        $existingStates = $this->repository->findAll();
        foreach ($existingStates as $state) {
            $em->remove($state);
        }
        $em->flush();

        $validState1 = $this->createStateWithData('valid_state_1', $this->config, 30);
        $validState2 = $this->createStateWithData('valid_state_2', $this->config, 30);
        $usedState = $this->createStateWithData('used_state', $this->config, 30);
        $usedState->markAsUsed();
        $expiredState = $this->createStateWithData('expired_state', $this->config, -1);

        $this->persistAndFlush($validState1);
        $this->persistAndFlush($validState2);
        $this->persistAndFlush($usedState);
        $this->persistAndFlush($expiredState);

        $count = $this->repository->countActiveStates();

        $this->assertSame(2, $count);
    }

    public function testCountActiveStatesReturnsZeroWhenNoActiveStates(): void
    {
        $em = self::getEntityManager();
        $existingStates = $this->repository->findAll();
        foreach ($existingStates as $state) {
            $em->remove($state);
        }
        $em->flush();

        $usedState = $this->createStateWithData('used_state', $this->config, 30);
        $usedState->markAsUsed();
        $expiredState = $this->createStateWithData('expired_state', $this->config, -1);

        $this->persistAndFlush($usedState);
        $this->persistAndFlush($expiredState);

        $count = $this->repository->countActiveStates();

        $this->assertSame(0, $count);
    }

    public function testFindStatesBySessionReturnsCorrectStates(): void
    {
        $sessionId = 'test_session_123';
        $now = new \DateTimeImmutable();

        $state1 = $this->createStateWithData('state_1', $this->config, 30);
        $state1->setSessionId($sessionId);
        $state1->setCreateTime($now->modify('-1 minute'));

        $state2 = $this->createStateWithData('state_2', $this->config, 30);
        $state2->setSessionId($sessionId);
        $state2->setCreateTime($now);

        $state3 = $this->createStateWithData('state_3', $this->config, 30);
        $state3->setSessionId('different_session');
        $state3->setCreateTime($now);

        $this->persistAndFlush($state1);
        $this->persistAndFlush($state2);
        $this->persistAndFlush($state3);

        $results = $this->repository->findStatesBySession($sessionId);

        $this->assertCount(2, $results);
        $states = array_map(fn ($s) => $s->getState(), $results);
        $this->assertContains('state_1', $states);
        $this->assertContains('state_2', $states);
        $this->assertNotContains('state_3', $states);

        $this->assertSame('state_2', $results[0]->getState());
        $this->assertSame('state_1', $results[1]->getState());
    }

    public function testFindStatesBySessionReturnsEmptyArrayWhenNoStatesFound(): void
    {
        $results = $this->repository->findStatesBySession('non_existent_session');

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSaveEntityPersistsState(): void
    {
        $state = $this->createStateWithData('new_state', $this->config, 60);
        $state->setSessionId('session_123');

        $this->repository->save($state);

        $this->assertEntityPersisted($state);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2State::class, $state->getId());
        $this->assertNotNull($found);
        $this->assertSame('new_state', $found->getState());
        $this->assertSame('session_123', $found->getSessionId());
        $this->assertFalse($found->isUsed());
    }

    public function testSaveEntityWithoutFlushDoesNotPersistImmediately(): void
    {
        $state = $this->createStateWithData('state_no_flush', $this->config, 60);

        $this->repository->save($state, false);

        $em = self::getEntityManager();

        $allStates = $this->repository->findBy(['state' => 'state_no_flush']);
        $this->assertEmpty($allStates);

        $em->flush();

        $allStates = $this->repository->findBy(['state' => 'state_no_flush']);
        $this->assertCount(1, $allStates);
        $this->assertSame('state_no_flush', $allStates[0]->getState());
    }

    public function testRemoveEntityDeletesState(): void
    {
        $state = $this->createStateWithData('to_delete', $this->config, 60);

        $this->persistAndFlush($state);
        $stateId = $state->getId();

        $this->repository->remove($state);

        $this->assertEntityNotExists(SinaWeiboOAuth2State::class, $stateId);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2State::class, $stateId);
        $this->assertNull($found);
    }

    public function testRemoveEntityWithoutFlushDoesNotDeleteImmediately(): void
    {
        $state = $this->createStateWithData('to_delete_no_flush', $this->config, 60);

        $this->persistAndFlush($state);
        $stateId = $state->getId();

        $this->repository->remove($state, false);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2State::class, $stateId);
        $this->assertNotNull($found);

        self::getEntityManager()->flush();
        $this->assertEntityNotExists(SinaWeiboOAuth2State::class, $stateId);
    }

    public function testFindValidStateQueryParametersAreCorrect(): void
    {
        $testState = 'test_query_state';
        $state = $this->createStateWithData($testState, $this->config, 30);

        $this->persistAndFlush($state);

        $result = $this->repository->findValidState($testState);

        $this->assertNotNull($result);
        $this->assertSame($testState, $result->getState());
        $this->assertSame($this->config->getId(), $result->getConfig()->getId());
    }

    public function testFindOneByAssociationConfigShouldReturnMatchingEntity(): void
    {
        $anotherConfig = new SinaWeiboOAuth2Config();
        $anotherConfig->setAppId('another_app');
        $anotherConfig->setAppSecret('another_secret');
        $anotherConfig->setValid(true);
        $this->persistAndFlush($anotherConfig);

        $state1 = $this->createStateWithData('state_1', $this->config, 30);
        $state2 = $this->createStateWithData('state_2', $anotherConfig, 30);

        $this->persistAndFlush($state1);
        $this->persistAndFlush($state2);

        $result = $this->repository->findOneBy(['config' => $this->config]);

        $this->assertNotNull($result);
        $this->assertSame('state_1', $result->getState());
        $this->assertSame($this->config->getId(), $result->getConfig()->getId());
    }

    public function testCountByAssociationConfigShouldReturnCorrectNumber(): void
    {
        $anotherConfig = new SinaWeiboOAuth2Config();
        $anotherConfig->setAppId('another_app');
        $anotherConfig->setAppSecret('another_secret');
        $anotherConfig->setValid(true);
        $this->persistAndFlush($anotherConfig);

        $state1 = $this->createStateWithData('state_1', $this->config, 30);
        $state2 = $this->createStateWithData('state_2', $this->config, 30);
        $state3 = $this->createStateWithData('state_3', $this->config, 30);
        $state4 = $this->createStateWithData('state_4', $this->config, 30);
        $state5 = $this->createStateWithData('state_5', $anotherConfig, 30);
        $state6 = $this->createStateWithData('state_6', $anotherConfig, 30);

        $this->persistAndFlush($state1);
        $this->persistAndFlush($state2);
        $this->persistAndFlush($state3);
        $this->persistAndFlush($state4);
        $this->persistAndFlush($state5);
        $this->persistAndFlush($state6);

        $count = $this->repository->count(['config' => $this->config]);

        $this->assertSame(4, $count);
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(SinaWeiboOAuth2StateRepository::class);

        $this->config = new SinaWeiboOAuth2Config();
        $this->config->setAppId('test_app_id_' . uniqid());
        $this->config->setAppSecret('test_secret_' . uniqid());
        $this->config->setValid(true);
        $this->config->setCreateTime(new \DateTimeImmutable());

        $this->persistAndFlush($this->config);
    }

    protected function createNewEntity(): object
    {
        $entity = $this->createStateWithData('test_state_' . uniqid(), $this->config, 30);
        $entity->setSessionId('test_session_' . uniqid());

        return $entity;
    }

    private function createStateWithData(string $state, SinaWeiboOAuth2Config $config, int $expiresInMinutes = 30): SinaWeiboOAuth2State
    {
        $stateEntity = new SinaWeiboOAuth2State();
        $stateEntity->setState($state);
        $stateEntity->setConfig($config);
        $stateEntity->setExpiresInMinutes($expiresInMinutes);

        return $stateEntity;
    }

    /**
     * @return ServiceEntityRepository<SinaWeiboOAuth2State>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
