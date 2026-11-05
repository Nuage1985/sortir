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

    #[Route('/creer', name: 'sortie_creer', methods: ['GET', 'POST'])]
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

        return $this->render('sorties/create.html.twig', [
            'sortie' => $sortie,
            'sortieForm' => $sortieForm,
            'lieuxData' => $this->sortieService->getLieuxData(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'sortie_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editSortie(Sortie $sortie, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur connecté est bien l'organisateur de la sortie
        $user = $this->getUser();
        if (!$this->sortieService->ensureIsAuthor($sortie, $user)) {
            throw $this->createAccessDeniedException('Seul l\'organisateur peut modifier cette sortie.');
        }

        // Récupérer le campus (champ disabled, non soumis)
        $campus = $sortie->getCampus();

        // Créer le formulaire pré-rempli avec les données de la sortie
        $sortieForm = $this->createForm(SortieType::class, $sortie);

        // Traiter le formulaire
        $sortieForm->handleRequest($request);

        // Si le formulaire est correctement soumis
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            // Le champ campus est disabled, il n'est pas soumis : on le réaffecte
            $sortie->setCampus($campus);

            // Vérifier quel bouton a été cliqué
            if ($request->request->has('sortieForm[publier]')) {
                $sortie->setEtat(EtatSortie::Ouverte);
                $this->addFlash('success', 'Sortie publiée avec succès !');
            } else {
                $this->addFlash('success', 'Sortie mise à jour avec succès !');
            }

            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('main_home');
        }

        return $this->render('sorties/edit.html.twig', [
            'sortie' => $sortie,
            'sortieForm' => $sortieForm,
            'lieuxData' => $this->sortieService->getLieuxData(),
        ]);
    }

    #[Route('/{id}/supprimer/', name: 'sortie_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function delete(Sortie $sortie, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Vérifier que l'utilisateur connecté est bien l'organisateur de la sortie
        $user = $this->getUser();
        if (!$this->sortieService->ensureIsAuthor($sortie, $user)) {
            throw $this->createAccessDeniedException('Seul l\'organisateur peut supprimer cette sortie.');
        }

        // Voir le twig de la page edit pour la création du token, ici vérification de sa validité
        $tokenIsValid = $this->isCsrfTokenValid('delete-token-' . $sortie->getId(), $request->query->get('token'));

        if ($tokenIsValid) {

            $entityManager->remove($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'Sortie supprimée avec succès');

            return $this->redirectToRoute('main_home');
        }
        $this->addFlash('error', 'Cette sortie ne peut être supprimée');
        return $this->redirectToRoute('main_home');
    }

    // TODO publier
    // TODO annuler
    // TODO Se desister
}
