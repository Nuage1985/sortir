<?php

namespace App\Models;

use App\Enum\EtatSortie;
use Symfony\Component\Validator\Constraints as Assert;

class FiltreSortieApiDTO
{
    public function __construct(
        #[Assert\Choice(
            callback: [EtatSortie::class, 'values'],
            message: 'État "{{ value }}" invalide. Valeurs possibles : {{ choices }}.'
        )]
        public ?string $etat = null,

        #[Assert\Date(message: 'Format attendu : AAAA-MM-JJ')]
        public ?string $date = null,
    ) {}

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date ? \DateTimeImmutable::createFromFormat('!Y-m-d', $this->date) : null;
    }

    public function getEtat(): ?EtatSortie
    {
        if ($this->etat === null || $this->etat === '') {
            return null;
        }
        return EtatSortie::from($this->etat);
    }
}
