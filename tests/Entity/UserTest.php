<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\User;
use App\Entity\Task;

class UserTest extends TestCase
{
    public function testUserEntity(): void
    {
        $user = new User();

        $username = 'admin';
        $email = 'admin@example.com';
        $password = 'pass_1234';
        $roles = ['ROLE_ADMIN'];

        $user->setUsername($username)
             ->setEmail($email)
             ->setPassword($password)
             ->setRoles($roles);

        $this->assertSame($username, $user->getUsername());
        $this->assertSame($username, $user->getUserIdentifier());
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($password, $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }

    public function testAddAndRemoveTask(): void
    {
        $user = new User();
        $task = new Task();

        $user->addTask($task);
        $this->assertCount(1, $user->getTasks());
        $this->assertSame($user, $task->getAuthor());

        $user->removeTask($task);
        $this->assertCount(0, $user->getTasks());
        $this->assertNull($task->getAuthor());
    }

    public function testDefaultValues(): void
    {
        $user = new User();
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testGetSalt(): void
    {
        $user = new User();
        $this->assertNull($user->getSalt());
    }

    public function testSetCreatedAt(): void
    {
        $user = new User();
        $newDate = new \DateTimeImmutable();
        $user->setCreatedAt($newDate);
        $this->assertSame($newDate, $user->getCreatedAt());
    }
}