<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findReservationsByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function reservationAVenir($user):array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('r')
            ->join('r.seance', 's')
            ->where('r.owner = :user')
            ->andWhere(
                "CONCAT(s.date, ' ', h.horaire) >= :now"
            )
            ->join('s.horaire', 'h')
            ->setParameter('user', $user)
            ->setParameter('now', $now->format('Y-m-d H:i:s'))
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('h.horaire', 'ASC')
            ->getQuery()
            ->getResult();

    }
}
