<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{

    #[Route('/users', name: 'user_list')]
    #[IsGranted('ROLE_ADMIN', message: 'Droits administrateur requis !')]
    public function list(EntityManagerInterface $em): Response
    {

        $users = $em->getRepository(User::class)->findAll();

        return $this->render('user/list.html.twig', [
            'users' => $users,
        ]);
    }

    #[IsGranted('ROLE_ADMIN', message: 'Droits administrateur requis !')]
    #[Route('/users/create', name: 'user_create')]
    public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $userPasswordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été ajouté.");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    #[IsGranted('ROLE_ADMIN', message: 'Droits administrateur requis !')]
    #[Route('/users/{id}/edit', name: 'user_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): Response
    {

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $userPasswordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été modifié.");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(), 
            'user' => $user
        ]);
    }

    #[IsGranted('ROLE_ADMIN', message: 'Droits administrateur requis !')]
    #[Route('/users/{id}/delete', name: 'user_delete')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        // empêche un admin de se supprimer lui-même
        //  vérifie que l’utilisateur à supprimer est le même que celui actuellement connecté.
        if ($user === $this->getUser()) {
            $this->addFlash('danger', "Vous ne pouvez pas supprimer votre propre compte.");
            return $this->redirectToRoute('user_list');
        }
        $em->remove($user);
        $em->flush();
    
        $this->addFlash('success', "L'utilisateur a été supprimé avec succès.");

        return $this->redirectToRoute('user_list');
    }
}
