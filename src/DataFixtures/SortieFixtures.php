<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Enum\EtatSortie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            CampusFixtures::class,
            ParticipantFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {

        $participant1 = $this->getReference('participant-marie', Participant::class);
        $participant2 = $this->getReference('participant-paul', Participant::class);

        $villeRennes = new Ville();
        $villeRennes->setNom('Rennes')
            ->setCodePostal('35000');
        $manager->persist($villeRennes);

        $lieuRennes = new Lieu();
        $lieuRennes->setNom('Parc de la République')
            ->setRue('Rue de la République')
            ->setLatitude(48.1147)
            ->setLongitude(-1.6794)
            ->setVille($villeRennes);
        $manager->persist($lieuRennes);

        $sortie1 = new Sortie();
        $sortie1->setNom('Balade à Rennes')
            ->setDateHeureDebut(new \DateTime('2026-11-18 18:30:00'))
            ->setDuree(120)
            ->setDateLimiteInscription(new \DateTime('2026-11-15 23:59:00'))
            ->setNbInscriptionMax(12)
            ->setInfosSortie('Découverte du centre historique et du parc.')
            ->setLieu($lieuRennes)
            ->setCampus($this->getReference('campus-rennes', Campus::class))
            ->setOrganisateur($participant1);
        $sortie1->setEtat(EtatSortie::Ouverte);
        $manager->persist($sortie1);

        $villeNantes = new Ville();
        $villeNantes->setNom('Nantes')
            ->setCodePostal('44000');
        $manager->persist($villeNantes);

        $lieuNantes = new Lieu();
        $lieuNantes->setNom('Île de Nantes')
            ->setRue('Quai de la Fosse')
            ->setLatitude(47.2145)
            ->setLongitude(-1.5512)
            ->setVille($villeNantes);
        $manager->persist($lieuNantes);

        $sortie2 = new Sortie();
        $sortie2->setNom('Sortie kayak')
            ->setDateHeureDebut(new \DateTime('2026-11-20 09:00:00'))
            ->setDuree(180)
            ->setDateLimiteInscription(new \DateTime('2026-11-17 18:00:00'))
            ->setNbInscriptionMax(8)
            ->setInfosSortie('Session découverte en rivière avec matériel fourni.')
            ->setLieu($lieuNantes)
            ->setCampus($this->getReference('campus-nantes', Campus::class))
            ->setOrganisateur($participant2);
        $sortie2->setEtat(EtatSortie::Ouverte);
        $manager->persist($sortie2);

        // Brouillon de Marie, campus Rennes : visible uniquement par Marie
        $sortie3 = new Sortie();
        $sortie3->setNom('Brouillon de Marie')
            ->setDateHeureDebut(new \DateTime('2026-12-05 14:00:00'))
            ->setDuree(90)
            ->setDateLimiteInscription(new \DateTime('2026-12-01 23:59:00'))
            ->setNbInscriptionMax(10)
            ->setInfosSortie('Sortie en cours de création.')
            ->setLieu($lieuRennes)
            ->setCampus($this->getReference('campus-rennes', Campus::class))
            ->setOrganisateur($participant1);
        $sortie3->setEtat(EtatSortie::EnCreation);
        $manager->persist($sortie3);

        // Brouillon de Paul, campus Rennes : invisible pour Marie
        $sortie4 = new Sortie();
        $sortie4->setNom('Brouillon de Paul')
            ->setDateHeureDebut(new \DateTime('2026-12-10 10:00:00'))
            ->setDuree(120)
            ->setDateLimiteInscription(new \DateTime('2026-12-07 23:59:00'))
            ->setNbInscriptionMax(6)
            ->setInfosSortie('Brouillon non publié.')
            ->setLieu($lieuRennes)
            ->setCampus($this->getReference('campus-rennes', Campus::class))
            ->setOrganisateur($participant2);
        $sortie4->setEtat(EtatSortie::EnCreation);
        $manager->persist($sortie4);

        // Sortie ouverte sur un autre campus, sans inscrit : doit apparaître seulement avec ?campus=<id Quimper>
        $sortie5 = new Sortie();
        $sortie5->setNom('Randonnée côtière')
            ->setDateHeureDebut(new \DateTime('2026-11-25 10:00:00'))
            ->setDuree(240)
            ->setDateLimiteInscription(new \DateTime('2026-11-22 23:59:00'))
            ->setNbInscriptionMax(15)
            ->setInfosSortie('Sortie sur le littoral.')
            ->setLieu($lieuRennes) // lieu réutilisé par commodité
            ->setCampus($this->getReference('campus-quimper', Campus::class))
            ->setOrganisateur($participant2);
        $sortie5->setEtat(EtatSortie::Ouverte);
        $manager->persist($sortie5);



        $sortie1->addParticipant($participant1);
        $sortie1->addParticipant($participant2);

        $sortie2->addParticipant($participant1);
        $sortie2->addParticipant($participant2);

        $manager->flush();
    }
}
