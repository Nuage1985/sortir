<?php

namespace App\Repository;

use App\Entity\Ville;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ville>
 */
class VilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ville::class);
    }

    public function findByNom(string $nom): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.nom LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->orderBy('v.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function hasLieuUsedInSortie(int $villeId): bool
    {
        $count = $this->createQueryBuilder('v')
            ->select('COUNT(l.id)')
            ->innerJoin('v.lieux', 'l')
            ->innerJoin('l.sorties', 's')
            ->andWhere('v.id = :villeId')
            ->setParameter('villeId', $villeId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$count > 0;
    }
}
