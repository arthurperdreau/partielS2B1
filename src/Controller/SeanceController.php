<?php

namespace App\Controller;

use App\Entity\Seance;
use App\Form\SeanceForm;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SeanceController extends AbstractController
{
    #[Route('/seances', name: 'app_seance')]
    public function index(SeanceRepository $seanceRepository): Response
    {
        return $this->render('seance/index.html.twig', [
            'seances' => $seanceRepository->findAll(),
        ]);
    }

    #[Route('/seance/nouveau', name: 'app_seance_create', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser() || !in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $seance = new Seance();
        $form = $this->createForm(SeanceForm::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $film = $seance->getFilm();

            if ($film && !$film->isFrancophone() && $seance->getVersion() === 'VF') {
                $horaire = $seance->getHoraire();
                if ($horaire && $horaire->getHoraire() instanceof \DateTimeInterface) {
                    $heure = (int) $horaire->getHoraire()->format('H');
                    if ($heure >= 12) {
                        return $this->redirectToRoute('app_seance_create');
                    }
                }
            }


            $entityManager->persist($seance);
            $entityManager->flush();

            return $this->redirectToRoute('app_seance');
        }

        return $this->render('seance/create.html.twig', [
            'seance' => $seance,
            'form' => $form,
        ]);
    }


    #[Route('/seance/{id}', name: 'app_seance_show',)]
    public function show(Seance $seance): Response
    {
        return $this->render('seance/show.html.twig', [
            'seance' => $seance,
        ]);
    }

    #[Route('/seance/{id}/modifier', name: 'app_seance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser() || !in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(SeanceForm::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $film = $seance->getFilm();

            if ($film && !$film->isFrancophone() && $seance->getVersion() === 'VF') {
                $horaire = $seance->getHoraire();
                if ($horaire && $horaire->getHoraire() instanceof \DateTimeInterface) {
                    $heure = (int) $horaire->getHoraire()->format('H');
                    if ($heure >= 12) {
                        return $this->redirectToRoute('app_seance_create');
                    }
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_seance');
        }

        return $this->render('seance/edit.html.twig', [
            'seance' => $seance,
            'form' => $form,
        ]);
    }


    #[Route('/seance/supprimer/{id}', name: 'app_seance_delete', methods: ['POST'])]
    public function delete(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $entityManager->remove($seance);
        $entityManager->flush();


        return $this->redirectToRoute('app_seance', [], Response::HTTP_SEE_OTHER);
    }

}
