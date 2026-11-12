<?php

namespace App\Repository;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Models\FiltreSortieDTO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
     * Construit le QueryBuilder commun avec les filtres appliqués.
     * Utilisé à la fois pour le comptage et la récupération des résultats.
     */
    private function createFilteredQueryBuilder(
        Campus $campus,
        Participant $participant,
        FiltreSortieDTO $filtres
    ): QueryBuilder {

        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.campus = :campus')
            ->andWhere('s.etat != :enCreation OR s.organisateur = :participant')
            ->andWhere('s.etat != :historisee')
            ->setParameter('campus', $campus)
            ->setParameter('enCreation', EtatSortie::EnCreation)
            ->setParameter('participant', $participant)
            ->setParameter('historisee', EtatSortie::Historisee)
            ->orderBy('s.dateHeureDebut', 'ASC')
            ->addOrderBy('s.id', 'ASC');

        $this->filterByState($qb, $filtres->etat);
        $this->filterByPeriod($qb, $filtres->getDateDebut(), $filtres->getDateFin());
        //Filtre le nom de la Sortie par un 'contient'
        if(!empty($filtres->nom)) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%'.$filtres->nom.'%');
        }
        // Sorties dont je suis l'organisateur
        if ($filtres->organisateur) {
            $qb->andWhere('s.organisateur = :participant');
        }
        // Sorties auxquelles je suis inscrit
        if ($filtres->inscrit) {
            $qb->andWhere(':participant MEMBER OF s.participants');
        }

        return $qb;
    }

    /**
     * Récupère le nombre total de sorties correspondant aux filtres.
     */
    public function countFilteredSortiesByParticipant(
        Campus $campus,
        Participant $participant,
        FiltreSortieDTO $filtres
    ): int {
        $qb = $this->createFilteredQueryBuilder($campus, $participant, $filtres)
            ->select('COUNT(s.id)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Liste toutes les sorties d'un campus suivant un filtre avec pagination
     * Le participant en paramètre est obligatoire afin d'afficher ses sorties en création si il est organisateur
     *
     * @return Sortie[]
     */
    public function findFilteredSortiesByParticipant(
        Campus $campus,
        Participant $participant,
        FiltreSortieDTO $filtres,
        int $limit = 10,
        int $offset = 0
    ): array {
        $qb = $this->createFilteredQueryBuilder($campus, $participant, $filtres)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Liste les sorties disponibles d'un Campus suivant une date et un Etat
     * États autorisé : Ouverte, Cloturé, Annulée, Historisée
     * États interdits : En cours, Terminée
     */

    public function findFilteredSorties(Campus $campus, ?EtatSortie $etat = null, ?\DateTimeImmutable $date = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.campus = :campus')
            ->andWhere('s.etat IN (:etatsAutorises)')
            ->setParameter('campus', $campus)
            ->setParameter('etatsAutorises', [
                EtatSortie::Ouverte,
                EtatSortie::Cloturee,
                EtatSortie::Annulee,
                EtatSortie::Historisee,
            ])
            ->orderBy('s.dateHeureDebut', 'ASC');

        $this->filterByState($qb, $etat);
        $this->filterByPeriod($qb, $date, $date); // sorties de ce jour-là

        return $qb->getQuery()->getResult();
    }

    /**
     * Fonctions communes au deux requêtes
     */

    private function filterByState(QueryBuilder $qb, ?EtatSortie $etat): void
    {
        if ($etat) {
            $qb->andWhere('s.etat = :etat')
                ->setParameter('etat', $etat);
        }
    }

    private function filterByPeriod(QueryBuilder $qb, ?\DateTime $debut, ?\DateTime $fin): void
    {
        if ($debut) {
            $qb->andWhere('s.dateHeureDebut >= :debut')
                ->setParameter('debut', $debut);
        }
        if ($fin) {
            $qb->andWhere('s.dateHeureDebut < :fin')
                ->setParameter('fin', $fin->setTime(23, 59, 59));
        }
    }

}
