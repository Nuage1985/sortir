<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ImporterCSVService
{
    public function __construct(
        private readonly CampusRepository            $campusRepository,
        private readonly ParticipantRepository       $participantRepository,
        private readonly EntityManagerInterface      $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}


    /** @return array{importes:int, erreurs:string[]} */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getPathname(), 'r');
        $entetes = fgetcsv($handle, separator: ',');

        $importes = 0;
        $erreurs = [];
        $ligne = 1;
        $campusCache = [];

        while (($data = fgetcsv($handle, separator: ',')) !== false) {
            $ligne++;
            if (count($data) !== count($entetes)) {
                $erreurs[] = "Ligne $ligne : nombre de colonnes incorrect";
                continue;
            }
            $row = array_combine($entetes, $data);

            if ($this->participantRepository->findOneBy(['mail' => $row['mail']])) {
                $erreurs[] = "Ligne $ligne : le mail {$row['mail']} existe déjà";
                continue;
            }

            $campus = $campusCache[$row['campus']]
                ??= $this->campusRepository->findOneBy(['nom' => $row['campus']]);
            if (!$campus) {
                $erreurs[] = "Ligne $ligne : campus « {$row['campus']} » introuvable";
                continue;
            }

            $p = new Participant();
            $p->setPseudo($row['pseudo']);
            $p->setNom($row['nom']);
            $p->setPrenom($row['prenom']);
            $p->setTelephone($row['telephone'] ?: null);
            $p->setMail($row['mail']);
            $p->setAdmin((bool) $row['admin']);
            $p->setActif((bool) $row['actif']);
            $p->setCampus($campus);
            $p->setPassword($this->passwordHasher->hashPassword($p, $row['password']));

            $this->entityManager->persist($p);
            $importes++;
        }
        fclose($handle);
        $this->entityManager->flush();

        return ['importes' => $importes, 'erreurs' => $erreurs];
    }
}
