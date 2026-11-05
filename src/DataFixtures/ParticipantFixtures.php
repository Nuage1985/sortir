<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Participant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function getDependencies(): array
    {
        return [
            CampusFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Profils de départ pour le dév
        $user = new Participant();
        $user->setPseudo('MarieD_44')
            ->setNom('Dupont')
            ->setPrenom('Marie')
            ->setMail('marie@eni.fr')
            ->setAdmin(false)
            ->setActif(true)
            ->setCampus($this->getReference('campus-rennes', Campus::class));
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);
        $this->addReference('participant-marie', $user);

        $admin = new Participant();
        $admin->setPseudo('CoachAdmin')
            ->setNom('Martin')
            ->setPrenom('Paul')
            ->setMail('admin@eni.fr')
            ->setAdmin(true)
            ->setActif(true)
            ->setCampus($this->getReference('campus-nantes', Campus::class));
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);
        $this->addReference('participant-paul', $admin);

        // Profils avec faker, 10 participants par campus.
        $faker = \Faker\Factory::create('fr_FR');

        $campusReferences = [
            'campus-rennes',
            'campus-nantes',
            'campus-quimper',
            'campus-marseille',
            'campus-paris',
            'campus-point-a-pitre',
        ];

        foreach ($campusReferences as $campusRef) {
            for ($i = 0; $i < 10; $i++) {
                $participant = new Participant();
                $participant
                    ->setPseudo($faker->unique()->userName())
                    ->setNom($faker->lastName())
                    ->setPrenom($faker->firstName())
                    ->setMail($faker->unique()->safeEmail())
                    ->setTelephone($faker->optional(0.5)->numerify('0#########'))
                    ->setAdmin(false)
                    ->setActif(true)
                    ->setCampus($this->getReference($campusRef, Campus::class));
                $participant->setPassword($this->passwordHasher->hashPassword($participant, 'password'));
                $manager->persist($participant);
            }
        }

        $manager->flush();
    }
}
