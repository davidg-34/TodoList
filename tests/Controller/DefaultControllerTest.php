<?php

namespace Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        // Récupère un utilisateur existant (tu peux changer l’email selon ta BDD)
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('admin@example.com');

        // Connecte l’utilisateur
        $client->loginUser($testUser);

        // Requête sur la page d'accueil
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
        $this->assertStringContainsString('Todo List', $crawler->filter('h1')->text());
    }
}
