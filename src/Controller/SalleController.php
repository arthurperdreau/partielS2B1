<?php

namespace App\Controller;

use App\Entity\Salle;
use App\Form\SalleForm;
use App\Repository\SalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalleController extends AbstractController
{
    #[Route('/salle', name: 'app_salle')]
    public function index(SalleRepository $salleRepository): Response
    {
        return $this->render('salle/index.html.twig', [
            'controller_name' => 'SalleController',
            'salles' => $salleRepository->findAll(),
        ]);
    }

    #[Route('/salle/nouveau', name: 'app_salle_create', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        $salle = new Salle();
        $form = $this->createForm(SalleForm::class, $salle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($salle);
            $entityManager->flush();

            return $this->redirectToRoute('app_salle');
        }

        return $this->render('salle/create.html.twig', [
            'salle' => $salle,
            'form' => $form,
        ]);
    }

    #[Route('/sale/{id}', name: 'app_salle_show',)]
    public function show(Salle $salle): Response
    {
        return $this->render('salle/show.html.twig', [
            'salle' => $salle,
        ]);
    }

    #[Route('/salle/{id}/modifier', name: 'app_salle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Salle $salle, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(SalleForm::class, $salle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_salle', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salle/edit.html.twig', [
            'salle' => $salle,
            'form' => $form,
        ]);
    }

    #[Route('/salle/supprimer/{id}', name: 'app_salle_delete')]
    public function delete(Request $request, Salle $salle, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $entityManager->remove($salle);
        $entityManager->flush();


        return $this->redirectToRoute('app_salle', [], Response::HTTP_SEE_OTHER);
    }

}
