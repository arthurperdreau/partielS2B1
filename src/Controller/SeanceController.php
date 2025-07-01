<?php

namespace App\Controller;

use App\Entity\Film;
use App\Entity\Seance;
use App\Entity\Siege;
use App\Form\SeanceForm;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SeanceController extends AbstractController
{
    #[Route('/admin/seances', name: 'app_seance')]
    public function index(SeanceRepository $seanceRepository): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('seance/index.html.twig', [
            'seances' => $seanceRepository->findAll(),
        ]);
    }

    #[Route('/admin/seance/{id}', name: 'app_seance_show',priority: -1)]
    public function show(Seance $seance): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('seance/show.html.twig', [
            'seance' => $seance,
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
            $date = $seance->getDate();
            $horaireEntity = $seance->getHoraire();
            $horaire = $horaireEntity ? $horaireEntity->getHoraire() : null;
            $salle = $seance->getSalle();

            if (!$date instanceof \DateTimeInterface || !$horaire instanceof \DateTimeInterface) {
                return $this->redirectToRoute('app_seance_create');
            }

            $seanceDateTime = (new \DateTime())
                ->setDate((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'))
                ->setTime((int) $horaire->format('H'), (int) $horaire->format('i'));

            // Date et heure actuelles
            $now = new \DateTime();

            if ($seanceDateTime < $now) {
                return $this->redirectToRoute('app_seance_create');
            }


            $jour = (int) $date->format('N');
            $heure = (int) $horaire->format('H');
            $minute = (int) $horaire->format('i');

            //lundi = 1 ... dimanche = 7
            if ($jour >= 1 && $jour <= 6) {
                // horaire doit être >= 10h15 entre lundi et samedi
                if ($heure < 10 || ($heure === 10 && $minute < 15)) {
                    return $this->redirectToRoute('app_seance_create');
                }
            } elseif ($jour === 7) {
                //seance autorisée que le matin donc avant 12h
                if ($heure >= 12) {
                    return $this->redirectToRoute('app_seance_create');
                }
            } else {
                return $this->redirectToRoute('app_seance_create');
            }

            //règle pour les films non francophones en vf que le matin
            $film = $seance->getFilm();
            if ($film && !$film->isFrancophone() && $seance->getVersion() === 'VF') {
                if ($heure >= 12) {
                    return $this->redirectToRoute('app_seance_create');
                }
            }

            //vérification qu'il n'y a pas déjà une séance à la même date et horaire dans cette salle
            $seanceExistante = $entityManager->getRepository(Seance::class)
                ->findExistingSeance($salle, $date, $horaireEntity);

            if ($seanceExistante) {
                return $this->redirectToRoute('app_seance_create');
            }
            if ($salle) {
                $nombreDePlaces = $salle->getPlace();

                for ($i = 1; $i <= $nombreDePlaces; $i++) {
                    $siege = new Siege();
                    $siege->setNumero($i);
                    $seance->addSiege($siege);

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


    #[Route('/seance/{id}/modifier', name: 'app_seance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser() || !in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(SeanceForm::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $date = $seance->getDate();
            $horaireEntity = $seance->getHoraire();
            $horaire = $horaireEntity ? $horaireEntity->getHoraire() : null;
            $salle = $seance->getSalle();

            if (!$date instanceof \DateTimeInterface || !$horaire instanceof \DateTimeInterface) {
                return $this->redirectToRoute('app_seance_edit', ['id' => $seance->getId()]);
            }
            $seanceDateTime = (new \DateTime())
                ->setDate((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'))
                ->setTime((int) $horaire->format('H'), (int) $horaire->format('i'));

            // Date et heure actuelles
            $now = new \DateTime();

            if ($seanceDateTime < $now) {
                return $this->redirectToRoute('app_seance_edit', ['id' => $seance->getId()]);
            }


            $jour = (int) $date->format('N');
            $heure = (int) $horaire->format('H');
            $minute = (int) $horaire->format('i');

            //lundi = 1 ... dimanche = 7
            if ($jour >= 1 && $jour <= 6) {
                // horaire doit être >= 10h15 entre lundi et samedi
                if ($heure < 10 || ($heure === 10 && $minute < 15)) {
                    return $this->redirectToRoute('app_seance_edit', ['id' => $seance->getId()]);
                }
            } elseif ($jour === 7) {
                //seance autorisée que le matin donc avant 12h
                if ($heure >= 12) {
                    return $this->redirectToRoute('app_seance_edit', ['id' => $seance->getId()]);
                }
            } else {
                return $this->redirectToRoute('app_seance_edit', ['id' => $seance->getId()]);
            }

            //règle pour les films non francophones en vf que le matin
            $film = $seance->getFilm();
            if ($film && !$film->isFrancophone() && $seance->getVersion() === 'VF') {
                if ($heure >= 12) {
                    return $this->redirectToRoute('app_seance_edit', ['id' => $seance->getId()]);
                }
            }

            //vérification qu'il n'y a pas déjà une seance à la meme date et meme horaire et meme salle
            $seanceExistante = $entityManager->getRepository(Seance::class)
                ->findExistingSeance($salle, $date, $horaireEntity, $seance);

            if ($seanceExistante) {
                return $this->redirectToRoute('app_seance_edit', ['id' => $seance->getId()]);
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

    #[Route('/film/seances/{id}', name: 'app_film_seance', methods: ['GET'])]
    public function voirSeance(Film $film, SeanceRepository $seanceRepository): Response
    {
        $seances = $seanceRepository->findUpcomingByFilm($film);

        return $this->render('seance/voir.html.twig', [
            'film' => $film,
            'seances' => $seances,
        ]);
    }


}
