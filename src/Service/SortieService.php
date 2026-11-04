<?php

namespace App\Service;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;

interface SortieService
{
    public function getCampusByDefault(Participant $participant): ?Campus;

    public function getSortiesFiltered(
        Participant $participant,
        ?Campus $campus = null,
        array $filtres = []
    ): array;

    public function isVisibleForParticipant(Sortie $sortie, Participant $participant): bool;


    public function getSortieDetails (int $id);
}
