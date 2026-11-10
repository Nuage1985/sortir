<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Enum\EtatSortie;
use App\Form\AnnulerSortieType;
use App\Form\SortieType;
use App\Models\FiltreSortieDTO;
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
    )
    {
    }


    #[Route('/liste', name: 'liste', methods: ['GET'])]
    public function listSorties(Request $request): Response
    {

        $participant = $this->getUser();
        $campusId = $request->query->get('campusId', $request->query->get('campus'));
        $campus = null;

        if ($campusId !== null && $campusId !== '') {
            $campus = $this->campusRepository->find((int)$campusId);
        }

        // Récupération des filtres du formulaire (valeurs interprétées par le service)
        $q = $request->query;
        $filtres = new FiltreSortieDTO(
            nom:          trim((string) $q->get('nom', '')),
            dateDebut:    $q->get('dateDebut') ?: null,
            dateFin:      $q->get('dateFin') ?: null,
            organisateur: $q->getBoolean('organisateur'),
            inscrit:      $q->getBoolean('inscrit'),
            etat:         $q->getBoolean('terminees') ? EtatSortie::Terminee : null,
        );

        $sorties = $this->sortieService->getSortiesFiltered($participant, $filtres, $campus);

        return $this->render('main/home.html.twig', [
            'sorties' => $sorties,
            'campusList' => $this->campusRepository->findAll(),
            //On affiche le campus par défaut
            'campusSelectionne' => $campus ?? $this->sortieService->getCampusByDefault($participant),
            'filtres' => $filtres,
        ]);

    }

    #[Route('/detail/{id}', name: 'sortie_detail', methods: ['GET'])]
    public function displaySortieDetail(int $id): Response
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
            // Le champ campus est disabled, il n'est pas soumis : on le réaffecte
            $sortie->setCampus($campus);
            $sortie->setOrganisateur($user);

            // Vérifier quel bouton a été cliqué
            if ($sortieForm->get('publier')->isClicked()) {
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
    public function editOuting(Sortie $sortie, Request $request, EntityManagerInterface $entityManager): Response
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
            if ($sortieForm->get('publier')->isClicked()) {
                $sortie->setEtat(EtatSortie::Ouverte);
                $this->addFlash('success', 'Sortie publiée avec succès !');
            } else {
                $sortie->setEtat(EtatSortie::EnCreation);
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

    #[Route('/{id}/publier', name: 'sortie_publier', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function publish(Sortie $sortie, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Vérifier que l'utilisateur connecté est bien l'organisateur de la sortie
        $user = $this->getUser();
        if (!$this->sortieService->ensureIsAuthor($sortie, $user)) {
            throw $this->createAccessDeniedException('Seul l\'organisateur peut publier cette sortie.');
        }

        // Voir le twig de la page home pour la création du token de publication, ici vérification de sa validité
        $tokenIsValid = $this->isCsrfTokenValid('publish-token-' . $sortie->getId(), $request->query->get('token'));

        if ($tokenIsValid) {
            $sortie->setEtat(EtatSortie::Ouverte);
            $entityManager->flush();

            $this->addFlash('success', 'Sortie publiée avec succès !');

            return $this->redirectToRoute('main_home');
        }

        $this->addFlash('error', 'Erreur lors de la publication de la sortie.');
        return $this->redirectToRoute('main_home');
    }

    #[Route('/{id}/annuler', name: 'sortie_annuler', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function annulerSortie(Sortie $sortie, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Vérifier que l'utilisateur connecté est bien l'organisateur de la sortie
        $user = $this->getUser();
        if (!$this->sortieService->ensureIsAuthor($sortie, $user)) {
            throw $this->createAccessDeniedException('Seul l\'organisateur peut annuler cette sortie.');
        }

        // Créer le formulaire avec le motif d'annulation
        $annulerSortieForm = $this->createForm(AnnulerSortieType::class, $sortie);

        // Traiter le formulaire
        $annulerSortieForm->handleRequest($request);

        // Si le formulaire est correctement soumis
        if ($annulerSortieForm->isSubmitted() && $annulerSortieForm->isValid()) {

            $sortie->setEtat(EtatSortie::Annulee);
            $entityManager->persist($sortie);
            $entityManager->flush();

            // Préparer le message avant la redirection
            $this->addFlash('success', 'Sortie annulée.');

            return $this->redirectToRoute('main_home');
        }

        return $this->render('sorties/cancel.html.twig', [
            'sortie' => $sortie,
            'annulerSortieForm' => $annulerSortieForm,
        ]);
    }

    #[Route('/{id}/publier', name: 'publier_sortie', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publishSortie(int $id, Request $request): Response
    {

        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('publish_sortie_' . $id, $token)) {
            $this->addFlash('error', 'Impossible de publier cette sortie.');
            throw $this->createAccessDeniedException();
        }

        $sortie = $this->sortieService->publishSortie($id);

        if (!$sortie) {
            $this->addFlash('error', 'Impossible de publier cette sortie.');
            throw $this->createNotFoundException('Sortie introuvable.');
        }

        $this->addFlash('success', 'Sortie publiée.');
        return $this->redirectToRoute('main_home');
    }

    #[Route('/{id}/inscrire', name: 'sortie_inscrire', methods: ['POST'])]
    public function singUp(Sortie $sortie, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('inscrire_sortie_' . $sortie->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('main_home');
        }

        if (!$this->sortieService->signUpSortie($sortie, $user)) {
            $this->addFlash('error', 'Impossible de s’inscrire à cette sortie.');
            return $this->redirectToRoute('main_home');
        }

        $this->addFlash('success', 'Inscription réussie.');
        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
    }

    #[Route('/{id}/desister', name: 'sortie_desister', methods: ['POST'])]
    public function desist(Sortie $sortie, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('desister_sortie_' . $sortie->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('main_home');
        }

        if (!$this->sortieService->desistParticipant($sortie, $user)) {
            $this->addFlash('error', 'Impossible de se désister, contactez un administrateur.');
            return $this->redirectToRoute('main_home');
        }

        $this->addFlash('success', 'Désistement enregistré. ');
        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
    }


}
