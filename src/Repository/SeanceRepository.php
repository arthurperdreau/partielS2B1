<?php

namespace App\Repository;

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

}
