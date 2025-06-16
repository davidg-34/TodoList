<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TaskControllerTest extends WebTestCase
{
    private KernelBrowser|null $client = null;
    private EntityManagerInterface $manager;
    private UrlGeneratorInterface $urlGenerator;
    private $user;
    private $UserRepository;
    private User $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->UserRepository = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class);
        $this->user = $this->UserRepository->findOneByEmail('admin@example.com');
        $this->urlGenerator = $this->client->getContainer()->get('router.default');
        $this->client->loginUser($this->user);
    }

    public function testListAction()
    {
        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('task_list'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    
    public function testCreateAction()
    {
        $crawler = $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('task_create'));
        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'Titre tâche test';
        $form['task[content]'] = 'Contenu de la tâche test';
        $this->client->submit($form);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('div.alert.alert-success','La tâche a bien été ajoutée.');
    }

    public function testEditAction()
    {
        // Création d'une tâche en base via le repository
        $task = new Task();
        $task->setTitle('Tâche initiale');
        $task->setContent('Contenu initial');
        $task->setAuthor($this->user);

        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($task);
        $em->flush();

        // Accès à la page d'édition
        $crawler = $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('task_edit', ['id' => $task->getId()])
        );

        // Remplir et soumettre le formulaire
        $form = $crawler->selectButton('Modifier')->form();
        $form['task[title]'] = 'Titre modifié';
        $form['task[content]'] = 'Contenu modifié';
        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('div.alert.alert-success', 'La tâche a bien été modifiée.');
    }

    public function testEditActionWithDifferentUser(): void
    {
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    
        // Récupérer les deux utilisateurs
        $user1 = $this->UserRepository->findOneByEmail('user1@example.com');
        $user2 = $this->UserRepository->findOneByEmail('user2@example.com');
        $this->assertNotNull($user1, 'Utilisateur user1 non trouvé.');
        $this->assertNotNull($user2, 'Utilisateur user2 non trouvé.');
    
        // Créer une tâche associée à user1 (l'auteur)
        $task = new Task();
        $task->setTitle('Tâche de user1');
        $task->setContent('Contenu de la tâche de user1');
        $task->setAuthor($user1);
        $em->persist($task);
        $em->flush();
    
        // Se connecter en tant que user2 (différent de l’auteur)
        $this->client->loginUser($user2);
    
        // Tenter d’éditer la tâche
        $this->client->request('GET', $this->urlGenerator->generate('task_edit', ['id' => $task->getId()]));
        $this->client->followRedirect();
    
        // Vérifier qu’un message d’erreur est affiché
        $this->assertSelectorExists('.alert.alert-danger');
        $this->assertSelectorTextContains('.alert.alert-danger', "Vous n'avez pas le droit de modifier cette tâche.");
    }

    public function testToggleTaskAction(): void
    {
        // Étape 1 : Créer une tâche via le formulaire
        $crawler = $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('task_create'));
        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'ReToggle Test';
        $form['task[content]'] = 'Contenu pour le retoggle';
        $this->client->submit($form);
        $this->client->followRedirect();

        // Étape 2 : Récupérer la tâche créée via Doctrine
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $task = $em->getRepository(Task::class)->findOneBy([], ['id' => 'DESC']);
        $this->assertNotNull($task, 'Tâche non trouvée.');

        $initialState = $task->isDone();

        // Étape 3 : 1er toggle → on inverse le statut initial
        $this->client->request('GET', $this->urlGenerator->generate('task_toggle', ['id' => $task->getId()]));
        $this->client->followRedirect();

        $em->clear(); // Force le rechargement depuis la base
        $toggledTask = $em->getRepository(Task::class)->find($task->getId());

        $this->assertNotEquals($initialState, $toggledTask->isDone(), 'La tâche aurait dû changer de statut au premier toggle.');

        // Étape 4 : 2e toggle → on revient au statut initial
        $this->client->request('GET', $this->urlGenerator->generate('task_toggle', ['id' => $task->getId()]));
        $this->client->followRedirect();

        $em->clear();
        $reToggledTask = $em->getRepository(Task::class)->find($task->getId());

        $this->assertEquals($initialState, $reToggledTask->isDone(), 'La tâche aurait dû revenir à son statut initial après le second toggle.');
    }

    public function testToggleTaskAccessDenied(): void
    {
        // Créer une tâche appartenant à un autre utilisateur
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $em->getRepository(User::class);
        $otherUser = $userRepo->findOneByEmail('user1@example.com');

        $task = new Task();
        $task->setTitle('Tâche autre user');
        $task->setContent('Contenu autre user');
        $task->setAuthor($otherUser);
        $em->persist($task);
        $em->flush();

        // Essayer de la toggle
        $this->client->request('GET', $this->urlGenerator->generate('task_toggle', ['id' => $task->getId()]));
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert.alert-danger', 'Vous ne pouvez pas modifier cette tâche.');
    }

    public function testDeleteTaskAction(): void
    {
        // 1. Créer une tâche à supprimer
        $crawler = $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('task_create'));
        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'Tâche à supprimer';
        $form['task[content]'] = 'Contenu à supprimer';
        $this->client->submit($form);
        $this->client->followRedirect();
    
        // 2. Récupérer la tâche créée
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $task = $em->getRepository(Task::class)->findOneBy([], ['id' => 'DESC']);
        $this->assertNotNull($task, 'Tâche non trouvée.');
    
        $taskId = $task->getId();
    
        // 3. Supprimer la tâche
        $this->client->request('GET', $this->urlGenerator->generate('task_delete', ['id' => $taskId]));
        $this->client->followRedirect();
        
        // 4. Optionnel : vérifier le message flash
        $this->assertSelectorTextContains('div.alert.alert-success', 'La tâche a bien été supprimée.');
    }

    public function testDeleteTaskAccessDenied(): void
    {
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $userRepo = $em->getRepository(User::class);
        $otherUser = $userRepo->findOneByEmail('user1@example.com');

        $task = new Task();
        $task->setTitle('Suppression tâche par user');
        $task->setContent('Contenu autre user');
        $task->setAuthor($otherUser);
        $em->persist($task);
        $em->flush();

        $this->client->request('GET', $this->urlGenerator->generate('task_delete', ['id' => $task->getId()]));
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert.alert-danger', 'Vous ne pouvez pas supprimer cette tâche.');
    }
}