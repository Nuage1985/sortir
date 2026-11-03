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
        $manager->flush();
    }
}
