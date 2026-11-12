<?php

namespace App\Repository;

use App\Entity\Campus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campus>
 */
class CampusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campus::class);
    }

    public function findByNom(string $nom): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Construit le QueryBuilder commun avec les filtres appliqués.
     */
    private function createFilteredQueryBuilder(string $nom): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        if ($nom !== '') {
            $qb->andWhere('c.nom LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        $qb->orderBy('c.nom', 'ASC');

        return $qb;
    }

    /**
     * Récupère le nombre total de campus correspondant aux filtres.
     */
    public function countFiltered(string $nom): int
    {
        $qb = $this->createFilteredQueryBuilder($nom)
            ->select('COUNT(c.id)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Campus[]
     */
    public function findFiltered(string $nom, int $limit = 10, int $offset = 0): array
    {
        $qb = $this->createFilteredQueryBuilder($nom)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    public function hasCampusUsedInSortie(int $campusId): bool
    {
        $count = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->innerJoin('c.sorties', 's')
            ->andWhere('c.id = :campusId')
            ->setParameter('campusId', $campusId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$count > 0;
    }

//    /**
//     * @return Campus[] Returns an array of Campus objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Campus
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
