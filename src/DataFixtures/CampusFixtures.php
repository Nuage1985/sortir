<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $campus1 = new Campus();
        $campus1->setNom('Rennes');
        $manager->persist($campus1);
        $this->addReference('campus-rennes', $campus1);

        $campus2 = new Campus();
        $campus2->setNom('Nantes');
        $manager->persist($campus2);
        $this->addReference('campus-nantes', $campus2);

        $campus3 = new Campus();
        $campus3->setNom('Quimper');
        $manager->persist($campus3);
        $this->addReference('campus-quimper', $campus3);

        $campus4 = new Campus();
        $campus4->setNom('Marseille');
        $manager->persist($campus4);
        $this->addReference('campus-marseille', $campus4);

        $campus5 = new Campus();
        $campus5->setNom('Paris');
        $manager->persist($campus5);
        $this->addReference('campus-paris', $campus5);

        $campus6 = new Campus();
        $campus6->setNom('Point à Pitre');
        $manager->persist($campus6);
        $this->addReference('campus-point-a-pitre', $campus6);

        $manager->flush();
    }
}
