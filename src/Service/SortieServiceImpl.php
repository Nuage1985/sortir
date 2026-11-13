<?php

namespace App\Service;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Models\FiltreSortieDTO;
use App\Repository\LieuRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class SortieServiceImpl implements SortieService
{

    public function __construct(
        private SortieRepository $sortieRepository,
        private LieuRepository    $lieuRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getCampusByDefault(Participant $participant): ?Campus
    {
        return $participant->getCampus();
    }

    public function getSortiesFiltered(Participant $participant, FiltreSortieDTO $filtres, ?Campus $campus = null, int $limit = 10, int $offset = 0): array
    {
        //Rappel : === est un opérateur strict, meme valeur, même type
        if ($campus === null) {
            $campus = $this->getCampusByDefault($participant);
        }

        if ($campus === null) {
            return []; //La requête attend un objet Campus non null, donc on lui retourne une valeur vide pour éviter l'erreur
        }

        return $this->sortieRepository->findFilteredSortiesByParticipant($campus, $participant, $filtres, $limit, $offset);
    }

    public function countSortiesFiltered(Participant $participant, FiltreSortieDTO $filtres, ?Campus $campus = null): int
    {
        if ($campus === null) {
            $campus = $this->getCampusByDefault($participant);
        }

        if ($campus === null) {
            return 0;
        }

        return $this->sortieRepository->countFilteredSortiesByParticipant($campus, $participant, $filtres);
    }
    public function isVisibleForParticipant(Sortie $sortie, Participant $participant): bool
    {
        if($sortie->getEtat() !== EtatSortie::EnCreation){
            return true;
        }

        return $sortie ->getOrganisateur()
            && $sortie->getOrganisateur()->getId() === $participant->getId();
    }

    public function ensureIsAuthor(Sortie $sortie, Participant $participant): bool
    {
        return $sortie->getOrganisateur()?->getId() === $participant->getId();
    }

    public function getLieuxData(): array
    {
        $lieux = $this->lieuRepository->findAll();
        $lieuxData = [];
        foreach ($lieux as $lieu) {
            $lieuxData[$lieu->getId()] = [
                'nom' => $lieu->getNom(),
                'rue' => $lieu->getRue(),
                'ville' => $lieu->getVille() ? $lieu->getVille()->getNom() : '',
                'codePostal' => $lieu->getVille() ? $lieu->getVille()->getCodePostal() : '',
                'latitude' => $lieu->getLatitude(),
                'longitude' => $lieu->getLongitude(),
            ];
        }
        return $lieuxData;
    }

    public function getSortieDetails(int $id): ?Sortie
    {
        return $this->sortieRepository->find($id);
    }

    public function publishSortie(int $id)
    {
        $sortie = $this->sortieRepository->find($id);

        if (!$sortie) {
            return null;
        }


        // On vérifie l'état de la Sortie, seul une sortie en création peut être ouverte
        if ($sortie->getEtat() === EtatSortie::EnCreation) {
            $sortie->setEtat(EtatSortie::Ouverte);
            $this->entityManager->flush(); //On envoie les Upsert/Delete dans la base
        }

        return $sortie;
    }

    public function signUpSortie(Sortie $sortie, Participant $participant): bool
    {
        if($sortie->getParticipants()->contains($participant)){
            return false;
        }
        if($sortie->getEtat()!=EtatSortie::Ouverte){
            return false;
        }

        if($sortie->getParticipants()->count()>= $sortie->getNbInscriptionMax()){
            return false;
        }

        $sortie->addParticipant($participant);
        $this->entityManager->flush();
        return true;
    }

    public function desistParticipant(Sortie $sortie, Participant $participant): bool
    {
        if(!$sortie->getParticipants()->contains($participant)){
            return false;
        }
        if($sortie->getEtat()!=EtatSortie::Ouverte){
            return false;
        }
        $sortie->removeParticipant($participant);
        $this->entityManager->flush();
        return true;
    }

}
