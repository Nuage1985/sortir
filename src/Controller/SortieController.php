<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly SortieService    $sortieService,
        private readonly CampusRepository $campusRepository,
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

        // Récupération des filtres du formulaire (valeurs interprétées par le service)
        $filtres = [
            'nom'            => trim((string) $request->query->get('nom', '')),
            'dateDebut'      => $request->query->get('dateDebut'),
            'dateFin'        => $request->query->get('dateFin'),
            // Une case non cochée n'est pas envoyée : on convertit donc en booléen
            'organisateur'   => $request->query->getBoolean('organisateur'),
            'inscrit'        => $request->query->getBoolean('inscrit'),
            'nonInscrit'     => $request->query->getBoolean('nonInscrit'),
            'terminees'      => $request->query->getBoolean('terminees'),
            ];

        $sorties = $this->sortieService->getSortiesFiltered($participant, $campus, $filtres);

        return $this->render('main/home.html.twig', [
            'sorties' => $sorties,
            'campusList' => $this->campusRepository->findAll(),
            //On affiche le campus par défaut
            'campusSelectionne' => $campus ?? $this->sortieService->getCampusByDefault($participant),
            'filtres' => $filtres,
        ]);

    }

    #[Route('/detail/{id}', name: 'sortie_detail', methods: ['GET'])]
    public function displaySortieDetail(int $id, SortieService $sortieService, Request $request): Response
    {
        $sortie = $this->sortieService->getSortieDetails($id);

        if (!$sortie) {
            throw $this->createNotFoundException('Sortie introuvable.');
        }

        return $this->render('sorties/sortie_detail.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/creer', name: 'sorties_creer', methods: ['GET', 'POST'])]
    public function createOuting(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer une nouvelle sortie
        $sortie = new Sortie();

        // Initialiser le campus depuis l'utilisateur connecté
        $user = $this->getUser();
        $campus = $user->getCampus();
        $sortie->setCampus($campus);
        $sortie->setOrganisateur($user);

        // Créer le formulaire
        $sortieForm = $this->createForm(SortieType::class, $sortie);

        // Traiter le formulaire
        $sortieForm->handleRequest($request);

        // Si le formulaire est correctement soumis
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            // Le champ campus est disabled, il n'est pas soumis : on le ré-affecte
            $sortie->setCampus($campus);
            $sortie->setOrganisateur($user);

            // Vérifier quel bouton a été cliqué
            if ($request->request->has('sortieForm[publier]')) {
                $sortie->setEtat(EtatSortie::Ouverte);
                $this->addFlash('success', 'Sortie publiée avec succès !');
            } else {
                $sortie->setEtat(EtatSortie::EnCreation);
                $this->addFlash('success', 'Sortie créée avec succès !');
            }

            $entityManager->persist($sortie);
            $entityManager->flush();

            // Redirection vers la page d'accueil
            return $this->redirectToRoute('main_home');
        }

        // Récupérer les lieux avec leurs détails pour le JavaScript
        $lieux = $entityManager->getRepository(\App\Entity\Lieu::class)->findAll();
        $lieuxData = [];
        foreach ($lieux as $lieu) {
            $lieuxData[$lieu->getId()] = [
                'rue' => $lieu->getRue(),
                'ville' => $lieu->getVille() ? $lieu->getVille()->getNom() : '',
                'codePostal' => $lieu->getVille() ? $lieu->getVille()->getCodePostal() : '',
                'latitude' => $lieu->getLatitude(),
                'longitude' => $lieu->getLongitude(),
            ];
        }

        return $this->render('sorties/create.html.twig', [
            'sortie' => $sortie,
            'sortieForm' => $sortieForm,
            'lieuxData' => $lieuxData,
        ]);
    }

    // TODO modifier
    // TODO publier
    // TODO annuler
    // TODO Se desister
}
