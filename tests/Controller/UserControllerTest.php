<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Security\Core\User\UserInterface;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $urlGenerator;
    private $admin;
    // private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class);
        $this->urlGenerator = $this->client->getContainer()->get('router.default');
        $this->admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($this->admin);
    }

    public function testUserListIsUp(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('user_list'));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des utilisateurs');
    }


    public function testUserCreatePageIsUp(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('user_create'));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form');
    }

    public function testAdminCanCreateUser(): void
{
    $crawler = $this->client->request('GET', $this->urlGenerator->generate('user_create'));

    $form = $crawler->selectButton('Ajouter')->form([
        'user[username]' => 'user6',
        'user[password][first]' => 'pass_user6',
        'user[password][second]' => 'pass_user6',
        'user[email]' => 'user6@example.com',
        'user[roles]' => ['ROLE_USER'],
    ]);

    $this->client->submit($form);

    // Vérifiez que la réponse est une redirection
    $this->assertTrue($this->client->getResponse()->isRedirect());

    // Suivez la redirection
    $this->client->followRedirect();

    // $response = $this->client->getResponse();
    // var_dump($response->getStatusCode(), $response->getContent());

    // Vérifiez que le message de succès est présent
    $this->assertSelectorTextContains('.alert-success', "L'utilisateur a bien été ajouté.");

    // Vérifiez que l'utilisateur a bien été créé dans la base de données
    $user = $this->userRepository->findOneByEmail('user6@example.com');
    $this->assertNotNull($user);
}

    public function testAdminCanEditUser(): void
    {
        $user = $this->userRepository->findOneByEmail('user6@example.com');

        $crawler = $this->client->request('GET', $this->urlGenerator->generate('user_edit', ['id' => $user->getId()]));

        $form = $crawler->selectButton('Modifier')->form([
            'user[username]' => 'user6bis',
            'user[password][first]' => 'pass_user6bis',
            'user[password][second]' => 'pass_user6bis',
            'user[email]' => 'usermodifie6@exemple.com',
            'user[roles]' => ['ROLE_USER'],
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects($this->urlGenerator->generate('user_list'));
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', "L'utilisateur a bien été modifié.");
    }

    public function testAdminCanDeleteOtherUser(): void
    {
        $user = $this->userRepository->findOneByEmail('usermodifie6@exemple.com');

        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('user_delete', ['id' => $user->getId(),]));

        $this->assertResponseRedirects($this->urlGenerator->generate('user_list'));
    }

    public function testAdminCannotDeleteSelf(): void
    {
        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('user_delete', ['id' => $this->admin->getId(),]));

        $this->assertResponseRedirects($this->urlGenerator->generate('user_list'));
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'Vous ne pouvez pas supprimer votre propre compte.');
    }
}