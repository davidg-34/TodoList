<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    public function testLogin()
    {
        $client = static::createClient();

        // Simuler une requête GET vers la route 'login'
        $client->request('GET', '/login');

        // Vérifier que la réponse est réussie
        $this->assertResponseIsSuccessful();

        // Vérifier que le statut de la réponse est 200
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Vérifier la présence du bouton de connexion
        $this->assertSelectorExists('button.btn-success');

        // Ou vérifier la présence d'un texte spécifique
        $this->assertSelectorTextContains('body', 'Se connecter');
    }

    public function testLogoutCheck()
    {
        $client = static::createClient();

        // Simuler une requête GET vers la route 'logout'
        $client->request('GET', '/logout');

        // Vérifier que la réponse est une redirection (généralement 302)
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}