<?php

namespace App\Service;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;

interface SortieService
{
    public function getCampusByDefault(Participant $participant): ?Campus;

    public function getSortiesFiltered(
        Participant $participant,
        ?Campus $campus = null,
        array $filtres = []
    ): array;

    public function isVisibleForParticipant(Sortie $sortie, Participant $participant): bool;

    /**
     * Vérifie que l'utilisateur est bien l'organisateur de la sortie.
     * Retourne true si l'utilisateur est l'organisateur, false sinon.
     */
    public function ensureIsAuthor(Sortie $sortie, Participant $participant): bool;

    /**
     * Retourne les données des lieux pour le JavaScript (adresse, ville, coordonnées).
     * Format : [id => ['rue' => ..., 'ville' => ..., 'codePostal' => ..., 'latitude' => ..., 'longitude' => ...]]
     */
    public function getLieuxData(): array;

}
