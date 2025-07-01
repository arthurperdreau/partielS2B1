<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function mesReservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservations = $reservationRepository->findReservationsByUser($user);

        return $this->render('profile/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

}
