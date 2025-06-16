<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Task;
use App\Entity\User;

class TaskTest extends TestCase
{
    public function testTaskEntity(): void
    {
        $task = new Task();
        $user = new User();

        $title = 'Titre de test';
        $content = 'Contenu de test';

        $task->setTitle($title)
             ->setContent($content)
             ->setIsDone(true)
             ->setAuthor($user);

        $this->assertSame($title, $task->getTitle());
        $this->assertSame($content, $task->getContent());
        $this->assertTrue($task->isDone());
        $this->assertSame($user, $task->getAuthor());
        $this->assertInstanceOf(\DateTimeImmutable::class, $task->getCreatedAt());
    }

    public function testToggle(): void
    {
        $task = new Task();

        $task->toggle(true);
        $this->assertTrue($task->isDone());

        $task->toggle(false);
        $this->assertFalse($task->isDone());
    }

    public function testDefaultValues(): void
    {
        $task = new Task();
        $this->assertFalse($task->isDone());
        $this->assertInstanceOf(\DateTimeImmutable::class, $task->getCreatedAt());
    }

    public function testSetCreatedAt(): void
    {
        $task = new Task();
        $newDate = new \DateTimeImmutable();
        $task->setCreatedAt($newDate);
        $this->assertSame($newDate, $task->getCreatedAt());
    }
}