<?php

namespace App\Controller;

use App\Entity\Film;
use App\Entity\Image;
use App\Form\FilmForm;
use App\Form\ImageForm;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FilmController extends AbstractController
{

    #[Route('/film/nouveau', name: 'app_film_create', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        $film = new Film();
        $form = $this->createForm(FilmForm::class, $film);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($film);
            $entityManager->flush();

            return $this->redirectToRoute('film_image', ['id'=>$film->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('film/create.html.twig', [
            'film' => $film,
            'form' => $form,
        ]);
    }

    #[Route('/film/{id}', name: 'app_film_show',)]
    public function show(Film $film): Response
    {
        return $this->render('film/show.html.twig', [
            'film' => $film,
        ]);
    }

    #[Route('/film/{id}/modifier', name: 'app_film_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Film $film, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(FilmForm::class, $film);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('film/edit.html.twig', [
            'film' => $film,
            'form' => $form,
        ]);
    }

    #[Route('/film/supprimer/{id}', name: 'app_film_delete')]
    public function delete(Request $request, Film $film, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $entityManager->remove($film);
        $entityManager->flush();


        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/film/ajoutimage/{id}', name: 'film_image')]
    public function addImage(Film $film, Request $request, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $image = new Image();
        $form = $this->createForm(ImageForm::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image->setFilm($film);
            $manager->persist($image);
            $manager->flush();

            return $this->redirectToRoute('film_image', ['id' => $film->getId()]);
        }

        return $this->render('film/image.html.twig', [
            'film' => $film,
            'form' => $form,
        ]);
    }

    #[Route('/film/supprimerimage/{id}', name: 'remove_image')]
    public function removeImage(Image $image, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }


        $filmId = $image->getFilm()->getId();

        $manager->remove($image);
        $manager->flush();

        return $this->redirectToRoute('film_image', ['id' => $filmId]);
    }
}
