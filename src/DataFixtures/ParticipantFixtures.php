<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new Participant();
        $user->setNom('Dupont')
            ->setPrenom('Marie')
            ->setMail('marie@eni.fr')
            ->setAdmin(false)
            ->setActif(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        $admin = new Participant();
        $admin->setNom('Martin')
            ->setPrenom('Paul')
            ->setMail('admin@eni.fr')
            ->setAdmin(true)
            ->setActif(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        $manager->flush();
    }
}
