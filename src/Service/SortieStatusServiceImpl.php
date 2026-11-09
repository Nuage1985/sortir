<?php

namespace App\Service;

use App\Service\SortieStatusService;

use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;

class SortieStatusServiceImpl implements SortieStatusService
{
    public function __construct(
        private readonly SortieRepository $sortieRepository,
        private readonly EntityManagerInterface $entityManager
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
            $dateFin = clone $sortie->getDateHeureDebut();
            $dateFin->add(new \DateInterval('PT' . $sortie->getDuree() . 'M'));

            if ($now >= $dateFin) {
                $sortie->setEtat(EtatSortie::Terminee);
            }
        }

        $this->entityManager->flush();
    }
}
