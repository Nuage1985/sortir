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

    /**
     * Construit le QueryBuilder commun avec les filtres appliqués.
     */
    private function createFilteredQueryBuilder(string $nom): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('v');

        if ($nom !== '') {
            $qb->andWhere('v.nom LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        $qb->orderBy('v.nom', 'ASC');

        return $qb;
    }

    /**
     * Récupère le nombre total de villes correspondant aux filtres.
     */
    public function countFiltered(string $nom): int
    {
        $qb = $this->createFilteredQueryBuilder($nom)
            ->select('COUNT(v.id)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Ville[]
     */
    public function findFiltered(string $nom, int $limit = 10, int $offset = 0): array
    {
        $qb = $this->createFilteredQueryBuilder($nom)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
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
