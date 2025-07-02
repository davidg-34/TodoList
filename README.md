# ToDoList

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/320a1dbdcddc4925b4b760d9b173b9c1)](https://app.codacy.com/gh/davidg-34/TodoList/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)

## Projet N° 8 Améliorer une application existante de ToDo & Co

Application web de gestion de tâches développée avec Symfony.  
Ce projet est basé sur un MVP amélioré avec des fonctionnalités d’authentification, de gestion des utilisateurs, de rôles et de sécurité d’accès.

## Installation du projet

### Prérequis

- Symfony 6.4.2 LTS
- PHP 8.1.10
- Apache 2.4.5
- Symfony 6.4
- MySQL 8.0.30

### Étapes

1. Cloner le projet

        git clone https://github.com/davidg-34/TodoList.git

2. A la racine du installer les dépendances

        composer install

3. Modifiez le fichier .env pour créer votre base de données

        DATABASE_URL="mysql://root@127.0.0.1:3306/todolist?serverVersion=8.0.30&charset=utf8mb4"

4. Créez la base de données et exécutez les migrations :

        php bin/console doctrine:database:create
        php bin/console doctrine:schema:update --force

5. Lancer les fixtures

        php bin/console doctrine:fixtures:load

6. Lancer le serveur local

        symfony serve ou symfony server:start -d

### Test et couverture

- Le rapport est disponible dans public/test-coverage/index.html.

- Générer un test de couverture de code :

        vendor/bin/phpunit --coverage-html deliverables/test-coverage
