<?php

namespace App\Repository;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\EtatSortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    /**
     * Sorties d'un campus visibles par un participant :
     * - toutes les sorties non "EnCreation"
     * - les sorties "EnCreation" uniquement si le participant en est l'organisateur
     */
    public function findVisiblesByCampus(Campus $campus, Participant $participant): array
    {
        $qb = $this->createQueryBuilder('s')
        ->addSelect('o', 'p')
            ->leftJoin('s.organisateur', 'o')
            ->leftJoin('s.participants', 'p');

        return $qb
            ->andWhere('s.campus = :campus')
            ->andWhere($qb->expr()->orX(
                's.etat != :enCreation',
                's.organisateur = :participant'
            ))
            ->setParameter('campus', $campus)
            ->setParameter('enCreation', EtatSortie::EnCreation) // enum accepté si la colonne est de type enumType
            ->setParameter('participant', $participant)
            ->orderBy('s.dateHeureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }



}
