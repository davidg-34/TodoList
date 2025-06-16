<?php

namespace App\Tests\DataFixtures;

use App\Entity\User;
use App\Entity\Task;
use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixturesTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $loader = new Loader();
        $container = static::getContainer();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $loader->addFixture(new AppFixtures($passwordHasher));

        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->purge(); // vide la base
        $executor->execute($loader->getFixtures()); // recharge les fixtures
    }

    public function testUserFixturesLoaded(): void
    {
        $userRepo = $this->entityManager->getRepository(User::class);
        $users = $userRepo->findAll();

        // 1 Admin + 3 Users + 1 Anonyme = 5
        $this->assertCount(5, $users, "Il y a 5 utilisateurs.");
        
        // Vérifier que l'admin est bien présent
        $admin = $userRepo->findOneBy(['email' => 'admin@example.com']);
        $this->assertNotNull($admin, "L'utilisateur admin devrait exister.");
        $this->assertContains('ROLE_ADMIN', $admin->getRoles());
    }

    public function testTaskFixturesLoaded(): void
    {
        $taskRepo = $this->entityManager->getRepository(Task::class);
        $tasks = $taskRepo->findAll();

        /**
         * Tâches :
         * - 3 users × 2 tâches = 6
         * - 2 tâches pour utilisateur anonyme
         * - 1 tâche terminée
         * = 9 tâches
         */
        foreach ($tasks as $task) {
        echo $task->getTitle() . ' | ' . ($task->getAuthor()?->getEmail() ?? 'anonyme') . PHP_EOL;
        }

        $this->assertCount(9, $tasks, "Il devrait y avoir exactement 9 tâches en base.");

        $doneTasks = $taskRepo->findBy(['isDone' => true]);
        $this->assertCount(1, $doneTasks, "1 tâche marquée comme faite.");
    }
}