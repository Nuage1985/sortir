<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Service\FileUploaderService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;


class ParticipantFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly FileUploaderService         $fileUploaderService)
    {
    }

    public function getDependencies(): array
    {
        return [
            CampusFixtures::class,
        ];
    }

    public function load(ObjectManager $manager,): void
    {
        $slugger = new AsciiSlugger();
        $faker = \Faker\Factory::create('fr_FR');

        //Paramètres de génération des fixtures
        $nbParticipantParCampus = 10;
        $nbAdminTotal = 2;
        $nbParticipantInactifTotal = 5;

        $mailsUtilises = [];
        $hash = null;
        $n = 0; // compteur global des participants

        //Récupération des Campus
        $campusList = $manager->getRepository(Campus::class)->findAll();
        foreach ($campusList as $campus) {
            for ($i = 0; $i < $nbParticipantParCampus; $i++, $n++) {
                $participant = new Participant();
                $participant
                    ->setNom($faker->lastName())
                    ->setPrenom($faker->firstName())
                    ->setPseudo($participant->getPrenom() . mb_substr($participant->getNom(), 0, 3))
                    ->setTelephone($faker->optional(0.5)->numerify('0#########'))
                    ->setCampus($campus);

                //Si c'est le premier participant
                if ($n === 0) {
                    $mail = 'user@eni.fr';
                    $admin = false;
                    $actif = true;

                    //Si il reste des admin a distribuer
                } elseif ($n <= $nbAdminTotal) {
                    $mail = $this->createMail('admin', $n);
                    $admin = true;
                    $actif = true;

                    //Si il reste des participant inactif à distribuer
                } elseif ($n <= $nbAdminTotal + $nbParticipantInactifTotal) {
                    $mail = $this->createMail('inactif', $n - $nbAdminTotal);
                    $admin = false;
                    $actif = false;
                } else {

                    //Sinon on crée un participant non admin et actif
                    $mail = $slugger->slug($participant->getNom() . '-' . $participant->getPrenom())->lower() . '@eni.fr';
                    $admin = false;
                    $actif = true;
                }

                //On vérifie si le mail issue du couple prenom-nom n'est pas déjà dans le tableau des mails utilisé.
                if (isset($mailsUtilises[$mail])) {
                    $mail = str_replace('@', '-' . $n . '@', $mail);
                }
                //on stock le mail comme utilisé
                $mailsUtilises[$mail] = true;

                $participant
                    ->setMail($mail)
                    ->setAdmin($admin)
                    ->setActif($actif);

                // le hachage est lent : on le calcule une seule fois et on le réutilise
                $hash ??= $this->passwordHasher->hashPassword($participant, 'password');
                $participant->setPassword($hash);

                //ajout de l'avatar
                $uploadedFile = $this->createAvatar($participant->getPseudo(), 'toon-head', 180);
                $newFilename = $this->fileUploaderService->upload($uploadedFile);
                $participant->setFilename($newFilename);

                $manager->persist($participant);
                $this->addReference('participant-' . $n, $participant);
            }
        }

        $manager->flush();
    }

    function createMail(string $prefixe, int $k): string
    {
        if ($k > 1) {
            return $prefixe . $k . '@eni.fr';
        }

        return $prefixe . '@eni.fr';
    }

    private function createAvatar(?string $seed = null, string $style = 'adventurer-neutral', int $size = 200): UploadedFile
    {
        //style : avataaars-neutral, toon-head
        $uniqueSeed = $seed ?? uniqid();
        $url = sprintf(
            'https://api.dicebear.com/9.x/%s/png?seed=%s&size=%d',
            $style,
            urlencode($uniqueSeed),
            $size
        );

        $content = @file_get_contents($url);
        if ($content === false) {
            throw new \RuntimeException('Impossible de télécharger l\'avatar : ' . $url);
        }

        // enregistrement dans un fichier temporaire
        $tmp = tempnam(sys_get_temp_dir(), 'fixture_');
        file_put_contents($tmp, $content);

        return new UploadedFile(
            $tmp,
            'avatar.png',
            'image/png',
            null,
            true // mode test, indispensable : sinon move() refuse le fichier
        );
    }
}
