<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController
{
    #[Route('/tasks', name: 'task_list')]
    public function listAction(TaskRepository $taskRepository):response
    {
        $tasks = $taskRepository->findTasksForUser($this->getUser());

        return $this->render('task/list.html.twig', ['tasks' => $tasks]);
    }

    #[Route('/tasks/create', name: 'task_create')]
    public function createAction(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $task->setAuthor($this->getUser());
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'La tâche a bien été ajoutée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/tasks/{id}/edit', name: 'task_edit')]
    public function editAction(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        // Vérifie que l'utilisateur actuel est bien l'auteur de la tâche
        if ($task->getAuthor() !== $this->getUser()) {
            $this->addFlash('danger', "Vous n'avez pas le droit de modifier cette tâche.");
        }

        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/toggle', name: 'task_toggle')]
    public function toggleTaskAction(Task $task, EntityManagerInterface $em): Response
    {
        if ($task->getAuthor() !== $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier cette tâche.');
            return $this->redirectToRoute('task_list');
        }

        $task->toggle(!$task->isDone());
        $em->flush();

        $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    #[Route('/tasks/{id}/delete', name: 'task_delete')]
    public function deleteTaskAction(Task $task, EntityManagerInterface $em): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isAuthor = $task->getAuthor() === $this->getUser();
        $isAnonymousTask = $task->getAuthor()?->getUsername() === 'anonyme';

        if (!$isAuthor && !($isAnonymousTask && $isAdmin)) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer cette tâche.');
            return $this->redirectToRoute('task_list');
        }
        
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }
}