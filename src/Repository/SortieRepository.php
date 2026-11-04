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

//    /**
//     * Sorties d'un campus visibles par un participant :
//     * - toutes les sorties non "EnCreation"
//     * - les sorties "EnCreation" uniquement si le participant en est l'organisateur
//     */
//    public function findVisiblesByCampus(Campus $campus, Participant $participant): array
//    {
//        $qb = $this->createQueryBuilder('s')
//        ->addSelect('o', 'p')
//            ->leftJoin('s.organisateur', 'o')
//            ->leftJoin('s.participants', 'p');
//
//        return $qb
//            ->andWhere('s.campus = :campus')
//            ->andWhere($qb->expr()->orX(
//                's.etat != :enCreation',
//                's.organisateur = :participant'
//            ))
//            ->setParameter('campus', $campus)
//            ->setParameter('enCreation', EtatSortie::EnCreation) // enum accepté si la colonne est de type enumType
//            ->setParameter('participant', $participant)
//            ->orderBy('s.dateHeureDebut', 'ASC')
//            ->getQuery()
//            ->getResult();
//    }

    /**
     * Sorties visibles d'un campus, filtrées (Cf : Maquette).
     * Visible par un partipant :
     * - Toutes sorties != "En Création"
     * - Sorties "En Création" visibles si le participant est organisateur
     */
    public function findFilteredSorties(Campus $campus, Participant $participant, array $filtres = []): array
    {


        /**
         * SELECT s.*,
         * o.id AS organisateur_id, o.nom AS organisateur_nom, o.prenom AS organisateur_prenom,
         * p.id AS inscrit_id, p.nom AS inscrit_nom, p.prenom AS inscrit_prenom
         * FROM sortie s
         * LEFT JOIN participant o        ON o.id = s.organisateur_id
         * LEFT JOIN participant_sortie ps ON ps.sortie_id = s.id
         * LEFT JOIN participant p        ON p.id = ps.participant_id
         * WHERE s.campus_id = {{$campus}}
         * AND (s.etat != 'EnCreation' OR s.organisateur_id = {{$participant}} (Regle de visibilite)
         */
        $qb = $this->createQueryBuilder('s')
            ->addSelect('o', 'p')
            ->leftJoin('s.organisateur', 'o')
            ->leftJoin('s.participants', 'p')
            ->andWhere('s.campus = :campus')
            ->andWhere($this->getEntityManager()->getExpressionBuilder()->orX(
                's.etat != :enCreation',
                's.organisateur = :participant'
            ))
            ->setParameter('campus', $campus)
            ->setParameter('enCreation', EtatSortie::EnCreation) // enum accepté si la colonne est de type enumType
            ->setParameter('participant', $participant)
            ->orderBy('s.dateHeureDebut', 'ASC');



            //AND s.nom LIKE '%nom%'
            if(!empty($filtres['nom'])) {
                $qb->andWhere('s.nom LIKE :nom')
                    ->setParameter('nom', '%'.$filtres['nom'].'%');
            }

            /*
             * AND s.date_heure_debut >= {{'Dated de début définie'}}
                AND s.date_heure_debut <= {{'Date de fin définie'}}
            */
            // Entre [date début] et [date fin]
            if (!empty($filtres['dateDebut'])) {
                $debut = \DateTime::createFromFormat('Y-m-d', $filtres['dateDebut']);
                if ($debut) {
                    $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                        ->setParameter('dateDebut', $debut->setTime(0, 0, 0));
                }
            }

            if (!empty($filtres['dateFin'])) {
                $fin = \DateTime::createFromFormat('Y-m-d', $filtres['dateFin']);
                if ($fin) {
                    // Fin de journée incluse
                    $qb->andWhere('s.dateHeureDebut <= :dateFin')
                        ->setParameter('dateFin', $fin->setTime(23, 59, 59));
                }
            }

            //AND s.organisateur_id = {{$participant}}
            // Sorties dont je suis l'organisateur
            if (!empty($filtres['organisateur'])) {
                $qb->andWhere('s.organisateur = :participant');
            }

            // Inscrit / non inscrit : On bloque la possibilité de cocher les deux dans la pager twig
            // MEMBER OF = WHERE NOT EXISTS (
            //    SELECT 1 FROM sortie_participant sp
            //    WHERE sp.sortie_id = s.id AND sp.participant_id = :participant
            //)
            $inscrit = !empty($filtres['inscrit']);
            $nonInscrit = !empty($filtres['nonInscrit']);
            if ($inscrit && !$nonInscrit) {
                $qb->andWhere(':participant MEMBER OF s.participants');
            } elseif ($nonInscrit && !$inscrit) {
                $qb->andWhere(':participant NOT MEMBER OF s.participants');
            }

            //AND s.etat = 'Terminee'
            // Sorties terminées
            if (!empty($filtres['terminees'])) {
                $qb->andWhere('s.etat = :terminee')
                    ->setParameter('terminee', EtatSortie::Terminee);
            }

            return $qb->getQuery()->getResult();
    }

}
