<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Models\FiltreParticipantDTO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.mail = :identifier OR p.pseudo = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Construit le QueryBuilder commun avec les filtres appliqués.
     * Utilisé à la fois pour le comptage et la récupération des résultats.
     */
    private function createFilteredQueryBuilder(FiltreParticipantDTO $filtres): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.campus', 'c')
            // On exclut les admins de la recherche
            ->andWhere('p.admin = false');

        if ($filtres->nom !== '') {
            $qb->andWhere('p.nom LIKE :nom')
                ->setParameter('nom', '%' . $filtres->nom . '%');
        }

        if ($filtres->prenom !== '') {
            $qb->andWhere('p.prenom LIKE :prenom')
                ->setParameter('prenom', '%' . $filtres->prenom . '%');
        }

        if ($filtres->pseudo !== '') {
            $qb->andWhere('p.pseudo LIKE :pseudo')
                ->setParameter('pseudo', '%' . $filtres->pseudo . '%');
        }

        if ($filtres->actif !== null) {
            $qb->andWhere('p.actif = :actif')
                ->setParameter('actif', $filtres->actif);
        }

        $qb->orderBy('p.nom', 'ASC');

        return $qb;
    }

    /**
     * Récupère le nombre total de participants correspondant aux filtres.
     */
    public function countFiltered(FiltreParticipantDTO $filtres): int
    {
        $qb = $this->createFilteredQueryBuilder($filtres)
            ->select('COUNT(p.id)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Participant[]
     */
    public function findFiltered(FiltreParticipantDTO $filtres, int $limit = 10, int $offset = 0): array
    {
        $qb = $this->createFilteredQueryBuilder($filtres)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Participant[] Returns an array of Participant objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Participant
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
