<?php

namespace App\Controller;

use App\Repository\CampusRepository;
use App\Service\SortieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sorties')]
#[IsGranted('ROLE_USER')]
class SortieController extends AbstractController
{

    public function __construct(
        private SortieService $sortieService,
        private CampusRepository $campusRepository,
    ) {}


    #[Route('/liste', name: 'liste', methods: ['GET'])]
    public function listSorties(Request $request) : Response
    {

        $participant = $this->getUser();
        $campusId = $request->query->get('campusId', $request->query->get('campus'));
        $campus = null;

        if ($campusId !== null && $campusId !== '') {
            $campus = $this->campusRepository->find((int) $campusId);
        }

        $sorties = $this->sortieService->getSortiesFiltered($participant, $campus);

        return $this->render('sorties/list.html.twig', [
            'sorties' => $sorties,
            'campusList' => $this->campusRepository->findAll(),
            //On affiche le campus par défaut
            'campusSelectionne' => $campus ?? $this->sortieService->getCampusByDefault($participant),
        ]);

    }

    //TODO ajouter le filtrage
    //TODO modifier
    //TODO publier
    //TODO annuler
    //TODO Se desister

}
