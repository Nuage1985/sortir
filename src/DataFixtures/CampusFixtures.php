<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;


class CampusFixtures extends Fixture
{
    private const CAMPUS = [
        'Rennes',
        'Nantes',
        'Quimper',
        'Marseille',
        'Paris',
        'Point à Pitre',
    ];

    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();

        foreach (self::CAMPUS as $nom) {
            $campus = new Campus();
            $campus->setNom($nom);
            $manager->persist($campus);

            // "Point à Pitre" donne "campus-point-a-pitre"
            $this->addReference('campus-'.$slugger->slug($nom)->lower(), $campus);
        }

        $manager->flush();
    }
}
