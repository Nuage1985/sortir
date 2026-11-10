<?php

namespace App\Models;

use App\Enum\EtatSortie;
use Symfony\Component\Validator\Constraints as Assert;

class FiltreSortieDTO
{
    public function __construct(
        public string $nom = '',

        #[Assert\Date(message: 'Format attendu : AAAA-MM-JJ')]
        public ?string $dateDebut = null,

        #[Assert\Date(message: 'Format attendu : AAAA-MM-JJ')]
        public ?string $dateFin = null,

        public bool $organisateur = false,
        public bool $inscrit = false,
        public ?EtatSortie $etat = null,
    ) {}

    public function getDateDebut(): ?\DateTime
    {
        return $this->toDate($this->dateDebut);
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->toDate($this->dateFin);
    }

    private function toDate(?string $valeur): ?\DateTime
    {
        return $valeur ? \DateTime::createFromFormat('!Y-m-d', $valeur) : null;
    }
}
