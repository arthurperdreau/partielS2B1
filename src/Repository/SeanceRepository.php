<?php

namespace App\Repository;

use App\Entity\Film;
use App\Entity\Seance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seance>
 */
class SeanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seance::class);
    }

    //    /**
    //     * @return Seance[] Returns an array of Seance objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Seance
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findExistingSeance($salle, $date, $horaire, ?Seance $excludeSeance = null): ?Seance
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.salle = :salle')
            ->andWhere('s.date = :date')
            ->andWhere('s.horaire = :horaire')
            ->setParameter('salle', $salle)
            ->setParameter('date', $date)
            ->setParameter('horaire', $horaire);

        if ($excludeSeance) {
            $qb->andWhere('s != :excludeSeance')
                ->setParameter('excludeSeance', $excludeSeance);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findUpcomingByFilm(Film $film): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('s')
            ->join('s.horaire', 'h')
            ->where('s.film = :film')
            ->andWhere('
            (s.date > :today)
            OR
            (s.date = :today AND h.horaire > :now)
        ')
            ->setParameter('film', $film)
            ->setParameter('today', $now->format('Y-m-d'))
            ->setParameter('now', $now->format('H:i:s'))
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('h.horaire', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
