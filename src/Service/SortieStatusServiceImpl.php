<?php

namespace App\Service;

use App\Service\SortieStatusService;

use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class SortieStatusServiceImpl implements SortieStatusService
{
    public function __construct(
        private SortieRepository       $sortieRepository,
        private EntityManagerInterface $entityManager
    ) {}


    /**
     * @throws \Exception
     */
    public function refreshStatus(): void
    {
        $now = new \DateTime();

        $sorties = $this->sortieRepository->findBy([
            'etat' => [
                EtatSortie::Ouverte,
                EtatSortie::Cloturee,
                EtatSortie::EnCours,
            ]
        ]);

        foreach ($sorties as $sortie) {

            $dateDebut = $sortie->getDateHeureDebut();
            $dateFin = clone $dateDebut;
            $dateFin->add(new \DateInterval('PT' . $sortie->getDuree() . 'M'));

            $dateHistorisee = clone $dateFin;
            $dateHistorisee->add(new \DateInterval('P30D'));

            if ($now >= $dateHistorisee) {
                $sortie->setEtat(EtatSortie::Historisee);
            } elseif ($now >= $dateFin) {
                $sortie->setEtat(EtatSortie::Terminee);
            } elseif ($now > $dateDebut) {
                $sortie->setEtat(EtatSortie::EnCours);
            }
        }

        $this->entityManager->flush();
    }
}
