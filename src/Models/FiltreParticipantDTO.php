<?php

namespace App\Models;

class FiltreParticipantDTO
{
    public function __construct(
        public string $nom = '',
        public string $prenom = '',
        public string $pseudo = '',
        public ?bool $actif = null,
        public int $page = 1,
    ) {}
}
