<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskRepositoryTest extends WebTestCase
{
    private $client;
    private $em;
    private $taskRepository;
    private $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();

        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->taskRepository = $this->em->getRepository(Task::class);
        $this->userRepository = $this->em->getRepository(User::class);
    }

    public function testFindTasksForAdminUser(): void
    {
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->assertNotNull($admin, 'Utilisateur admin introuvable.');

        $tasks = $this->taskRepository->findTasksForUser($admin);
        $allTasks = $this->taskRepository->findAll();

        $this->assertEquals(count($allTasks), count($tasks), 'L\'admin devrait voir toutes les tâches.');
    }

    public function testFindTasksForNormalUser(): void
    {
        $normalUser = $this->userRepository->findOneByEmail('user@example.com');
        if (!$normalUser) {
            $normalUser = new User();
            $normalUser->setUsername('user');
            $normalUser->setEmail('user@example.com');
            $normalUser->setRoles(['ROLE_USER']);
            $normalUser->setPassword('pass_user');
            $this->em->persist($normalUser);
            $this->em->flush();
        }

        $tasks = $this->taskRepository->findTasksForUser($normalUser);

        $this->assertNotEmpty($tasks, 'Le user devrait voir des tâches.');
        foreach ($tasks as $task) {
            $this->assertNotEquals('anonyme', $task->getAuthor()?->getUsername(), 'Les tâches anonymes ne doivent pas être visibles pour les utilisateurs normaux.');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
        $this->em = null;
    }
}