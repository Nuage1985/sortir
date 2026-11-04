<?php

namespace App\Service;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Repository\SortieRepository;

class SortieServiceImpl implements SortieService
{

    public function __construct(
        private SortieRepository $sortieRepository
    ) {
    }

    public function getCampusByDefault(Participant $participant): ?Campus
    {
        return $participant->getCampus();
    }

    public function getSortiesFiltered(Participant $participant, ?Campus $campus = null, array $filtres = []): array
    {
        //Rappel : === est un opérateur strict, meme valeur, même type
        if ($campus === null) {
            $campus = $this->getCampusByDefault($participant);
        }

        if ($campus === null) {
            return []; //La requête attend un objet Campus non null, donc on lui retourne une valeur vide pour eviter l'erreur
        }

        return $this->sortieRepository->findFilteredSorties($campus, $participant, $filtres);
    }
    public function isVisibleForParticipant(Sortie $sortie, Participant $participant): bool
    {
        if($sortie->getEtat() !== EtatSortie::EnCreation){
            return true;
        }

        return $sortie ->getOrganisateur()
            && $sortie->getOrganisateur()->getId() === $participant->getId();
    }


}
