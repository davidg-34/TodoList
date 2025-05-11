<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // --- 1. création Admin ---
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'pass_admin'));
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        // --- 2. Création des Utilisateurs  ---
        $users = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->setUsername("user$i");
            $user->setEmail("user$i@example.com");
            $password = "pass_user{$i}";
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setRoles(["ROLE_USER"]);
            $manager->persist($user);
            $users[] = $user;
        }

        // --- 3. Création Utilisateur Anonyme ---
        $anonyme = new User();
        $anonyme->setUsername('anonyme');
        $anonyme->setEmail('anonyme@example.com');
        $anonyme->setPassword($this->passwordHasher->hashPassword($anonyme, 'pass_anonyme'));
        $anonyme->setRoles(["ROLE_USER"]);
        $manager->persist($anonyme);

        $manager->flush(); // On flush ici pour obtenir les IDs

        // --- Ajout des références (pour tests automatisés) ---
        $this->addReference('admin_user', $admin);
        $this->addReference('user_1', $users[0]);
        $this->addReference('user_anonyme', $anonyme);

        // --- 4. Tâches associées aux utilisateurs ---
        foreach ($users as $i => $user) {
            for ($j = 1; $j <= 2; $j++) {
                $task = new Task();
                $task->setTitle("Tâche $j de {$user->getUsername()}");
                $task->setContent("Contenu de la tâche $j pour l'utilisateur {$user->getUsername()}");
                $task->setCreatedAt(new \DateTimeImmutable());
                $task->setIsDone(false);
                $task->setAuthor($user);
                $manager->persist($task);
            }
        }

        // --- 5. Tâches déjà créées, rattachées à un utilisateur anonyme. ---
        for ($i = 1; $i <= 2; $i++) {
            $task = new Task();
            $task->setTitle("Tâche $i de l'utilisateur anonyme");
            $task->setContent("Contenu de la tâche $i pour l'utilisateur anonyme");
            $task->setCreatedAt(new \DateTimeImmutable());
            $task->setIsDone(false);
            $task->setAuthor($anonyme);
            $manager->persist($task);
        }

        // --- 6. Création de tâches terminées (isDone = true) ---
        $doneTask = new Task();
        $doneTask->setTitle("Tâche terminée");
        $doneTask->setContent("Ceci est une tâche complétée");
        $doneTask->setCreatedAt(new \DateTimeImmutable());
        $doneTask->setIsDone(true);
        $doneTask->setAuthor($users[0]); // ou anonyme
        $manager->persist($doneTask);

        $manager->flush();

    }
}
