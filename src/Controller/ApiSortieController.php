<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Enum\EtatSortie;
use App\Models\FiltreSortieApiDTO;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/api', format: 'json')]

final class ApiSortieController extends AbstractController
{
    #[Route('/campus/{id}/sorties', name: 'api_sortie_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function list(
        ?Campus $campus,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        ?FiltreSortieApiDTO $filtres,
        SortieRepository $sortieRepository,
    ): JsonResponse {
        if (!$campus) {
            return $this->json(['erreur' => 'Campus introuvable'], Response::HTTP_NOT_FOUND);
        }

        $filtres ??= new FiltreSortieApiDTO();

        $etat = $filtres->getEtat();
        if ($etat == EtatSortie::EnCours || $etat == EtatSortie::Terminee) {
            return $this->json(['erreur' => 'Etats interdits : EnCours, Terminée'], Response::HTTP_NOT_FOUND);
        }

        $sorties = $sortieRepository->findFilteredSorties($campus, $etat, $filtres->getDate());

        return $this->json($sorties, Response::HTTP_OK, [], ['groups' => 'sortie:read']);
    }

}
