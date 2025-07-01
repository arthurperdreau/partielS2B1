<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Seance;
use App\Entity\Siege;
use App\Form\ReservationForm;
use App\Repository\ReservationRepository;
use App\Repository\SeanceRepository;
use App\Repository\SiegeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ReservationController extends AbstractController
{
    #[Route('/seance/{id}/reserver', name: 'app_reservation')]
    public function reserver(
        Seance $seance,
        SiegeRepository $siegeRepository,
    ): Response {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reservation/reserver.html.twig', [
            'sieges' => $siegeRepository->findBy([ 'seance' => $seance ]),
            'seance' => $seance,
            'film' => $seance->getFilm(),
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
        ]);
    }

    #[Route('/seance/{id}/create-checkout-session', name: 'create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(
        Seance $seance,
        Request $request,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $siegesIds = $data['sieges'] ?? [];

        if (empty($siegesIds)) {
            return new JsonResponse(['error' => 'No seats selected'], 400);
        }

        // Récupérer les sièges demandés
        $sieges = $entityManager->getRepository(Siege::class)->findBy(['id' => $siegesIds]);

        foreach ($sieges as $siege) {
            if ($siege->isReserve()) {
                return new JsonResponse(['error' => 'One or more seats are already reserved'], 409);
            }
        }

        $prixTotal = count($sieges) * 7.5;

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $lineItems = [[
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => intval($prixTotal * 100),
                'product_data' => [
                    'name' => 'Réservation séance ' . $seance->getFilm()->getName(),
                ],
            ],
            'quantity' => 1,
        ]];

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $urlGenerator->generate('reservation_payment_success', [
                'id' => $seance->getId(),
                'sieges' => implode(',', $siegesIds),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $urlGenerator->generate('app_reservation', ['id' => $seance->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $session->id]);
    }

    #[Route('/seance/{id}/payment-success', name: 'reservation_payment_success')]
    public function paymentSuccess(
        Seance $seance,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        $siegesIds = explode(',', $request->query->get('sieges', ''));

        $sieges = $entityManager->getRepository(Siege::class)->findBy(['id' => $siegesIds]);
        $user = $this->getUser();

        $reservation = new Reservation();
        $reservation->setSeance($seance);
        $reservation->setOwner($user);
        $reservation->setCreatedAt(new \DateTimeImmutable());

        foreach ($sieges as $siege) {
            $siege->setReserve(true);
            $reservation->addSiege($siege);
        }

        $reservation->setNombreTickets(count($sieges));
        $reservation->setPrix(count($sieges) * 7.5);

        $entityManager->persist($reservation);
        $entityManager->flush();

        return $this->render('reservation/success.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('admin/seance/{id}', name: 'app_reservation_admin', methods: ['POST'])]
    public function reserverSansPayer(
        Seance $seance,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $siegesIds = $data['sieges'] ?? [];

        if (empty($siegesIds)) {
            return new JsonResponse(['error' => 'No seats selected'], 400);
        }

        $sieges = $entityManager->getRepository(Siege::class)->findBy(['id' => $siegesIds]);

        foreach ($sieges as $siege) {
            if ($siege->isReserve()) {
                return new JsonResponse(['error' => 'One or more seats are already reserved'], 409);
            }
        }

        $reservation = new Reservation();
        $reservation->setSeance($seance);
        $reservation->setOwner($this->getUser());
        $reservation->setCreatedAt(new \DateTimeImmutable());

        foreach ($sieges as $siege) {
            $siege->setReserve(true);
            $reservation->addSiege($siege);
        }

        $reservation->setNombreTickets(count($sieges));
        $reservation->setPrix(count($sieges)*7.5);

        $entityManager->persist($reservation);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Réservation admin enregistrée ',
            'reservationId' => $reservation->getId(),
        ]);
    }

    #[Route('/admin/reservations/avenir', name: 'app_reservations_avenir')]
    public function reservationsAvenir(ReservationRepository $reservationRepository): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $now = new \DateTime();

        $reservations = $reservationRepository->createQueryBuilder('r')
            ->join('r.seance', 's')
            ->where('r.owner = :user')
            ->andWhere(
                "CONCAT(s.date, ' ', h.horaire) >= :now"
            )
            ->join('s.horaire', 'h')
            ->setParameter('user', $this->getUser())
            ->setParameter('now', $now->format('Y-m-d H:i:s'))
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('h.horaire', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('reservation/avenir.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/admin/reservations/all', name: 'app_reservations_all')]
    public function reservationsToutes(ReservationRepository $reservationRepository): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles())) {
            return $this->redirectToRoute('app_login');
        }

        $reservations = $reservationRepository->findAll();

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }




}
