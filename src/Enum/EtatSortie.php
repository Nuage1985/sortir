<?php

namespace App\Enum;

/*
 (*) sont déclenchées automatiquement par le système selon les dates renseignées à la création.
 EnCreation → Ouverte → *Cloturee → *EnCours → *Terminee → *Historisee
 └───────-----------------------─→ Annulee (depuis EnCreation, Ouverte ou Cloturee)
*/


enum EtatSortie: string
{
    //EnCréation   : Manuel (organisateur) — Brouillon invisible aux autre
    case EnCreation = "EnCreation";

    //Ouverte      : Manuel (publication) — Visible et accessible
    case Ouverte = "Ouverte";

    //Clôturée     : Automatique — Date limite d'inscription atteinte
    case Cloturee = "Cloturee";

    //En cours     : Automatique — Date/heure de début de l'activité atteinte
    case EnCours = "EnCours";

    //Passée       : Automatique — Date/heure de fin de l'activité atteinte
    case Terminee = "Terminee";

    //Annulée      : Manuel (organisateur ou administrateur) — Sortie annulée
    case Annulee = "Annulee";

    //Historisée   : Automatique — Date/heure de fin de l'activité + 1 mois atteinte
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
