<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Enum\EtatSortie;
use App\Service\SortieStatusService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    private const ACTIVITES = [
        'Balade', 'Visite guidée', 'Pique-nique', 'Happy Hour', 'Footing',
        'Soirée Karaoké', 'Expo', 'Concert', 'Photo walk', 'Kart électrique',
    ];

    private const CAMPUS_PAR_VILLE = [
        'Rennes'         => 'Rennes',
        'Nantes'         => 'Nantes',
        'Quimper'        => 'Quimper',
        'Marseille'      => 'Marseille',
        'Paris'          => 'Paris',
        'Pointe-à-Pitre' => 'Point à Pitre',
        'Le Gosier'      => 'Point à Pitre',
    ];

    public function __construct(
        private readonly SortieStatusService $sortieStatusService,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        //Paramètres de génération des fixtures

        /*Date Mini : date a partir de laquelle les sorties sont créé.
         * Si la date est antérieur a aujourd'hui, des sorties Terminées seront créé
         * Si la date est antérieur de 1 mois a aujourd'hui, des sorties Historisée seront créé
         * Date Maxi : date jusqu'a laquelle les sorties sont créé.
         */
        $dateMini = new \DateTime('-2 months');
        $dateMaxi = new \DateTime('+2 months');
        $nbSortie = 100;
        $nbSortieParLieu = 2;   // un lieu accueille au plus N sorties (à des dates différentes)
        $tauxBrouillon = 10;    // % de sorties "En création" (uniquement dans le futur)
        $tauxAnnulation = 5;    // % de sorties "Annulée"     (uniquement dans le futur)

        //il peut y avoir plusieurs sorties dans un même lieu a des dates différentes
        //quand une sortie est rattachée a un campus, le lieu y est rataché également
        //il n'y a que les participants d'un campus qui peuvent s'inscrire a la sortie du même campus.

        $faker = \Faker\Factory::create('fr_FR');
        $slugger = new AsciiSlugger();
        $maintenant = new \DateTimeImmutable();

        // 1. On assigne les lieux aux campus (via la ville du lieu)
        //    [ 'Rennes' => [Lieu, Lieu, …], 'Point à Pitre' => [...], … ]

        $lieuxParCampus = [];

        $lieuxList = $manager->getRepository(Lieu::class)->findAll();

        foreach ( $lieuxList as $lieu) {
            $nomVille = $lieu->getVille()->getNom();
            $nomCampus = self::CAMPUS_PAR_VILLE[$nomVille]
                ?? throw new \LogicException(sprintf('La ville "%s" n\'est rattachée à aucun campus (CAMPUS_PAR_VILLE).', $nomVille));

            $lieuxParCampus[$nomCampus][] = $lieu;
        }


        // 2. Réserve chaque lieu y figure $nbSortieParLieu fois,
        //    avec son campus et les participants actifs de ce campus

        $reserve = [];

        foreach ($manager->getRepository(Campus::class)->findAll() as $campus) {

            // on récupère les lieux du campus
            $lieux = $lieuxParCampus[$campus->getNom()] ?? [];

            // on récupère les participants actifs du campus
            $participants = $manager->getRepository(Participant::class)
                ->findBy(['campus' => $campus, 'actif' => true]);

            // si pas de lieu ou personne dans ce campus, on passe au campus suivant
            if (!$participants || !$lieux) {
                continue;
            }

            //pour chaque lieu on crée une réservation (futur sortie)
            foreach ($lieux as $lieu) {
                for ($k = 0; $k < $nbSortieParLieu; $k++) {
                    $reserve[] = [$campus, $lieu, $participants];
                }
            }
        }

        // mélange pour que les sorties gardées soient réparties sur tous les campus
        shuffle($reserve);
        $reserve = array_slice($reserve, 0, $nbSortie);


        // 3. Génération des sorties

        foreach ($reserve as $n => [$campus, $lieu, $participants]) {

            $dateDebut = $faker->dateTimeBetween($dateMini, $dateMaxi); // \DateTime (mutable)

            // clone : sinon modify() changerait aussi $dateDebut
            // "-" : la limite d'inscription est AVANT le début
            $dateLimite = (clone $dateDebut)->modify('-'.$faker->numberBetween(1, 7).' days');

            $organisateur = $faker->randomElement($participants);

            $nbInscritMax = $faker->numberBetween(4, 20);

            $sortie = new Sortie();
            $sortie->setNom($faker->randomElement(self::ACTIVITES).' – '.$lieu->getNom())
                ->setDateHeureDebut($dateDebut)
                ->setDuree($faker->randomElement([60, 90, 120, 180, 240]))
                ->setDateLimiteInscription($dateLimite)
                ->setNbInscriptionMax($nbInscritMax)
                ->setInfosSortie($faker->paragraph(2))
                ->setLieu($lieu)
                ->setCampus($campus)              // campus du lieu
                ->setOrganisateur($organisateur); // participant du même campus

            $manager->persist($sortie);
            $this->addReference('sortie-'.$n, $sortie);

            $futur = $dateDebut > $maintenant;
            $tirage = $faker->numberBetween(1, 100);

            // Brouillon : pas d'inscrits
            if ($futur && $tirage <= $tauxBrouillon) {
                $sortie->setEtat(EtatSortie::EnCreation);
                continue;
            }

            // Inscrits : participants du même campus, sans l'organisateur
            $nbInscrits = $faker->numberBetween(0, min($nbInscritMax, count($participants)));
            if ($nbInscrits > 0) {
                foreach ($faker->randomElements($participants, $nbInscrits) as $inscrit) {
                    $sortie->addParticipant($inscrit);
                }
            }

            // Modification de l'état
            if ($futur && $tirage <= $tauxBrouillon + $tauxAnnulation) {
                $sortie->setEtat(EtatSortie::Annulee);
                $sortie->setMotifAnnulation($faker->sentence());
            } else {
                // état de départ : refreshStatus() calcule ensuite le vrai état selon les dates
                $sortie->setEtat(EtatSortie::Ouverte);
            }
        }

        // 4. Scénarios fixes pour tester la visibilité des brouillons
        //    participant-0 = user@eni.fr, participant-1 = admin@eni.fr (même campus)

        $user = $this->getReference('participant-0', Participant::class);
        $admin = $this->getReference('participant-1', Participant::class);
        $lieuUser = $lieuxParCampus[$user->getCampus()->getNom()][0];

        $manager->persist($this->brouillon('Brouillon de user (visible par lui seul)', $user, $lieuUser));
        $manager->persist($this->brouillon('Brouillon de l\'admin (invisible pour user)', $admin, $lieuUser));

        $manager->flush();

        $this->sortieStatusService->refreshStatus();
        $manager->flush(); // au cas où refreshStatus() ne flush pas lui-même
    }

    private function brouillon(string $nom, Participant $organisateur, Lieu $lieu): Sortie
    {
        $sortie = new Sortie();
        $sortie->setNom($nom)
            ->setDateHeureDebut(new \DateTime('+3 weeks 14:00'))
            ->setDuree(90)
            ->setDateLimiteInscription(new \DateTime('+2 weeks 23:59'))
            ->setNbInscriptionMax(10)
            ->setInfosSortie('Sortie en cours de création.')
            ->setLieu($lieu)
            ->setCampus($organisateur->getCampus())
            ->setOrganisateur($organisateur);
        $sortie->setEtat(EtatSortie::EnCreation);

        return $sortie;
    }

    public function getDependencies(): array
    {
        return [
            CampusFixtures::class,
            ParticipantFixtures::class,
            LieuFixtures::class,
        ];
    }
}
