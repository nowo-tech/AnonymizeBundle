<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{connection}/user')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine
    ) {
    }

    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(string $connection): Response
    {
        $em = $this->doctrine->getManager($connection);
        $users = $em->getRepository(User::class)->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'connection' => $connection,
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $connection): Response
    {
        $user = new User();
        $em = $this->doctrine->getManager($connection);
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User created successfully!');

            return $this->redirectToRoute('user_index', ['connection' => $connection]);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'User updated successfully!');

            return $this->redirectToRoute('user_index', ['connection' => $connection]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'connection' => $connection,
        ]);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, string $connection, int $id): Response
    {
        $em = $this->doctrine->getManager($connection);
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'User deleted successfully!');
        }

        return $this->redirectToRoute('user_index', ['connection' => $connection]);
    }
}
