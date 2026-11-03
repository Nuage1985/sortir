<?php

namespace App\Enum;

enum EtatSortie: string
{
    case EnCreation = "EnCreation";
    case Ouverte = "Ouverte";
    case Cloturee = "Cloturee";
    case EnCours = "EnCours";
    case Terminee = "Terminee";
    case Annulee = "Annulee";
    case Historisee = "Historise";


// Libellé lisible a utiliser dans Twig : sortie.etat.libelle
    public function libelle(): string
    {
        return match ($this) {
            self::EnCreation => 'En Creation',
            self::Ouverte => 'Ouverte',
            self::Cloturee => 'Clôturée',
            self::EnCours => 'En cours',
            self::Terminee => 'Terminée',
            self::Annulee => 'Annulée',
            self::Historisee => 'Historisée',
        };
    }
}
