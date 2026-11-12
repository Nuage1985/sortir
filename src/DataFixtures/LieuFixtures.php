<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LieuFixtures extends Fixture
{
    /**
     * 10 lieux réels par campus.
     * [nom, rue, code postal, ville, latitude, longitude]
     * Coordonnées approximatives (précision ~100 m), suffisantes pour une carte de démo.
     */
    private const LIEUX = [
        ['Parc du Thabor', 'Place Saint-Mélaine', '35000', 'Rennes', 48.1144, -1.6694],
        ['Les Champs Libres', '10 Cours des Alliés', '35000', 'Rennes', 48.1056, -1.6744],
        ['Marché des Lices', 'Place des Lices', '35000', 'Rennes', 48.1124, -1.6830],
        ['Parlement de Bretagne', 'Place du Parlement de Bretagne', '35000', 'Rennes', 48.1124, -1.6781],
        ['Le Liberté', '1 Esplanade Charles de Gaulle', '35000', 'Rennes', 48.1058, -1.6779],
        ['Parc des Gayeulles', 'Avenue des Gayeulles', '35700', 'Rennes', 48.1328, -1.6359],
        ['Opéra de Rennes', 'Place de la Mairie', '35000', 'Rennes', 48.1117, -1.6797],
        ['Musée des Beaux-Arts de Rennes', '20 Quai Émile Zola', '35000', 'Rennes', 48.1097, -1.6747],
        ['Roazhon Park', '111 Route de Lorient', '35000', 'Rennes', 48.1076, -1.7128],
        ['Parc Oberthür', 'Rue de Paris', '35000', 'Rennes', 48.1135, -1.6640],
        ['Château des ducs de Bretagne', '4 Place Marc Elder', '44000', 'Nantes', 47.2161, -1.5497],
        ['Les Machines de l\'île', 'Parc des Chantiers, Boulevard Léon Bureau', '44200', 'Nantes', 47.2066, -1.5641],
        ['Jardin des Plantes', 'Rue Stanislas Baudry', '44000', 'Nantes', 47.2194, -1.5426],
        ['Le Lieu Unique', '2 Quai Ferdinand Favre', '44000', 'Nantes', 47.2149, -1.5455],
        ['Passage Pommeraye', 'Passage Pommeraye', '44000', 'Nantes', 47.2131, -1.5604],
        ['Cathédrale Saint-Pierre-et-Saint-Paul', '7 Impasse Saint-Laurent', '44000', 'Nantes', 47.2181, -1.5508],
        ['Musée d\'arts de Nantes', '10 Rue Georges Clemenceau', '44000', 'Nantes', 47.2197, -1.5475],
        ['Stade de la Beaujoire', 'Route de Saint-Joseph', '44300', 'Nantes', 47.2558, -1.5254],
        ['Île de Versailles', 'Quai de Versailles', '44000', 'Nantes', 47.2245, -1.5535],
        ['Hangar à Bananes', '21 Quai des Antilles', '44200', 'Nantes', 47.2004, -1.5728],
        ['Cathédrale Saint-Corentin', 'Place Saint-Corentin', '29000', 'Quimper', 47.9958, -4.1026],
        ['Musée des Beaux-Arts de Quimper', '40 Place Saint-Corentin', '29000', 'Quimper', 47.9961, -4.1032],
        ['Musée départemental breton', '1 Rue du Roi Gradlon', '29000', 'Quimper', 47.9954, -4.1015],
        ['Jardin de la Retraite', 'Rue Élie Fréron', '29000', 'Quimper', 47.9975, -4.1013],
        ['Mont Frugy', 'Promenade du Mont Frugy', '29000', 'Quimper', 47.9935, -4.1030],
        ['Théâtre de Cornouaille', '1 Esplanade François Mitterrand', '29000', 'Quimper', 47.9932, -4.1083],
        ['Faïencerie Henriot', 'Rue Haute', '29000', 'Quimper', 47.9903, -4.1124],
        ['Place au Beurre', 'Place au Beurre', '29000', 'Quimper', 47.9973, -4.1045],
        ['Rue Kéréon', 'Rue Kéréon', '29000', 'Quimper', 47.9963, -4.1046],
        ['Les Halles Saint-François', 'Rue Astor', '29000', 'Quimper', 47.9958, -4.1060],
        ['Vieux-Port', 'Quai du Port', '13002', 'Marseille', 43.2951, 5.3740],
        ['Notre-Dame de la Garde', 'Rue Fort du Sanctuaire', '13006', 'Marseille', 43.2840, 5.3713],
        ['MuCEM', '7 Promenade Robert Laffont', '13002', 'Marseille', 43.2967, 5.3611],
        ['Le Panier', 'Place de Lenche', '13002', 'Marseille', 43.2988, 5.3666],
        ['Palais Longchamp', 'Boulevard Jardin Zoologique', '13004', 'Marseille', 43.3046, 5.3944],
        ['Orange Vélodrome', '3 Boulevard Michelet', '13008', 'Marseille', 43.2698, 5.3959],
        ['Parc Borély', 'Avenue du Parc Borély', '13008', 'Marseille', 43.2600, 5.3815],
        ['Vallon des Auffes', 'Rue du Vallon des Auffes', '13007', 'Marseille', 43.2851, 5.3500],
        ['La Friche la Belle de Mai', '41 Rue Jobin', '13003', 'Marseille', 43.3101, 5.3904],
        ['Cathédrale La Major', 'Place de la Major', '13002', 'Marseille', 43.2993, 5.3640],
        ['Musée du Louvre', 'Rue de Rivoli', '75001', 'Paris', 48.8606, 2.3376],
        ['Jardin du Luxembourg', 'Rue de Vaugirard', '75006', 'Paris', 48.8462, 2.3372],
        ['Tour Eiffel', '5 Avenue Anatole France', '75007', 'Paris', 48.8584, 2.2945],
        ['Parc des Buttes-Chaumont', '1 Rue Botzaris', '75019', 'Paris', 48.8809, 2.3828],
        ['Centre Pompidou', 'Place Georges-Pompidou', '75004', 'Paris', 48.8606, 2.3522],
        ['Canal Saint-Martin', 'Quai de Valmy', '75010', 'Paris', 48.8710, 2.3652],
        ['Basilique du Sacré-Cœur', '35 Rue du Chevalier de la Barre', '75018', 'Paris', 48.8867, 2.3431],
        ['Musée d\'Orsay', '1 Rue de la Légion d\'Honneur', '75007', 'Paris', 48.8600, 2.3266],
        ['Cité des Sciences et de l\'Industrie', '30 Avenue Corentin Cariou', '75019', 'Paris', 48.8957, 2.3878],
        ['Lac Daumesnil', 'Route de Ceinture du Lac Daumesnil', '75012', 'Paris', 48.8336, 2.4090],
        ['Mémorial ACTe', 'Rue Raspail', '97110', 'Pointe-à-Pitre', 16.2350, -61.5367],
        ['Place de la Victoire', 'Place de la Victoire', '97110', 'Pointe-à-Pitre', 16.2397, -61.5334],
        ['Marché Saint-Antoine', 'Rue Peynier', '97110', 'Pointe-à-Pitre', 16.2427, -61.5355],
        ['Musée Saint-John Perse', '9 Rue de Nozières', '97110', 'Pointe-à-Pitre', 16.2413, -61.5340],
        ['Musée Schœlcher', '24 Rue Peynier', '97110', 'Pointe-à-Pitre', 16.2425, -61.5370],
        ['Cathédrale Saint-Pierre-et-Saint-Paul', 'Rue Alexandre Isaac', '97110', 'Pointe-à-Pitre', 16.2412, -61.5326],
        ['Aquarium de la Guadeloupe', 'Place de la Marina', '97190', 'Le Gosier', 16.2240, -61.5160],
        ['Marina de Bas-du-Fort', 'Marina Bas-du-Fort', '97190', 'Le Gosier', 16.2225, -61.5268],
        ['Fort Fleur d\'Épée', 'Bas-du-Fort', '97190', 'Le Gosier', 16.2210, -61.5145],
        ['Plage de la Datcha', 'Boulevard Général de Gaulle', '97190', 'Le Gosier', 16.2063, -61.4930],
    ];
    public function load(ObjectManager $manager): void
    {
        $villes = [];
        $i = 0;

        foreach (self::LIEUX as $dataLieu => [$nom, $rue, $codePostal, $nomVille, $latitude, $longitude]) {

            $cleVille = $nomVille.'-'.$codePostal;

            //création de la ville si inexistante
            if (!isset($villes[$cleVille])) {
                $ville = new Ville();
                $ville->setNom($nomVille);
                $ville->setCodePostal($codePostal);
                $manager->persist($ville);
                $villes[$cleVille] = $ville;
            }

            $lieu = new Lieu();
            $lieu->setNom($nom);
            $lieu->setRue($rue);
            $lieu->setLatitude($latitude);
            $lieu->setLongitude($longitude);
            $lieu->setVille($villes[$cleVille]);
            $manager->persist($lieu);

            //on conserve la référence pour la sortie
            $this->addReference('lieu-'.$i, $lieu);
            $i ++;
        }

        $manager->flush();
    }


}
