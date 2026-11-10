<?php

namespace App\Models;

use App\Enum\EtatSortie;
use Symfony\Component\Validator\Constraints as Assert;

class FiltreSortieApiDTO
{
    public function __construct(
        public ?EtatSortie $etat = null,

        #[Assert\Date(message: 'Format attendu : AAAA-MM-JJ')]
        public ?string $date = null,
    ) {}

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date ? \DateTimeImmutable::createFromFormat('!Y-m-d', $this->date) : null;
    }
}
