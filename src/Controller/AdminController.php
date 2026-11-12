<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Ville;
use App\Form\AdminCampusType;
use App\Form\AdminProfilesType;
use App\Form\AdminVillesType;
use App\Models\FiltreParticipantDTO;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly VilleRepository $villeRepository,
        private readonly CampusRepository $campusRepository,
        private readonly ParticipantRepository $participantRepository,
    )
    {
    }

    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    public function dashBoard(): Response
    {
        return $this->render('admin/dashboard.html.twig', [

        ]);
    }

    #[Route('/cities-list', name: 'admin_cities_list', methods: ['GET', 'POST'])]
    public function citiesList(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Filtre, recherche par nom
        $nom = trim((string)$request->query->get('nom', ''));
        $page = max(1, (int)$request->query->get('page', 1));

        // Nombre de résultats par page
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Récupérer les villes filtrées avec pagination
        $villes = $this->villeRepository->findFiltered($nom, $limit, $offset);

        // Compter le nombre total de résultats pour la pagination
        $total = $this->villeRepository->countFiltered($nom);
        $totalPages = max(1, (int)ceil($total / $limit));

        // Créer une nouvelle ville
        $ville = new Ville();

        // Créer le formulaire
        $addCityForm = $this->createForm(AdminVillesType::class, $ville);

        // Traiter le formulaire
        $addCityForm->handleRequest($request);

        // Si le formulaire est correctement soumis
        if ($addCityForm->isSubmitted() && $addCityForm->isValid()) {

            $entityManager->persist($ville);
            $entityManager->flush();

            // Préparation du message de redirection
            $this->addFlash('success', 'Ville ajoutée !');

            // Redirection vers la page de détail du profil
            return $this->redirectToRoute('admin_cities_list');
        }

        return $this->render('admin/villes_list.html.twig', [
            'villes' => $villes,
            'nom' => $nom,
            'addCityForm' => $addCityForm,
            'nonDeletableCities' => array_filter(
                array_map(fn($v) => $v->getId(), $villes),
                fn($id) => $this->villeRepository->hasLieuUsedInSortie($id)
            ),
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    #[Route('/city-edit/{id}', name: 'admin_city_edit', methods: ['GET', 'POST'])]
    public function cityEdit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $ville = $this->villeRepository->find($id);

        if (!$ville) {
            throw $this->createNotFoundException('Ville introuvable.');
        }

        $form = $this->createForm(AdminVillesType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Ville modifiée avec succès !');
            return $this->redirectToRoute('admin_cities_list');
        }

        return $this->render('admin/city_edit.html.twig', [
            'form' => $form,
            'ville' => $ville,
        ]);
    }

    #[Route('/city-delete/{id}', name: 'admin_city_delete', methods: ['POST'])]
    public function cityDelete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_city_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_cities_list');
        }

        $ville = $this->villeRepository->find($id);

        if (!$ville) {
            throw $this->createNotFoundException('Ville introuvable.');
        }

        if ($this->villeRepository->hasLieuUsedInSortie($id)) {
            $this->addFlash('error', 'Cette ville ne peut pas être supprimée car un de ses lieux est utilisé dans une sortie.');
            return $this->redirectToRoute('admin_cities_list');
        }

        $entityManager->remove($ville);
        $entityManager->flush();

        $this->addFlash('success', 'Ville supprimée !');
        return $this->redirectToRoute('admin_cities_list');
    }

    #[Route('/campus-list', name: 'admin_campus_list', methods: ['GET', 'POST'])]
    public function campusList(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Filtre, recherche par nom
        $nom = trim((string)$request->query->get('nom', ''));
        $page = max(1, (int)$request->query->get('page', 1));

        // Nombre de résultats par page
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Récupérer les campus filtrés avec pagination
        $campuses = $this->campusRepository->findFiltered($nom, $limit, $offset);

        // Compter le nombre total de résultats pour la pagination
        $total = $this->campusRepository->countFiltered($nom);
        $totalPages = max(1, (int)ceil($total / $limit));

        // Créer une nouvelle ville
        $campus = new Campus();

        // Créer le formulaire
        $addCampusForm = $this->createForm(AdminCampusType::class, $campus);

        // Traiter le formulaire
        $addCampusForm->handleRequest($request);

        // Si le formulaire est correctement soumis
        if ($addCampusForm->isSubmitted() && $addCampusForm->isValid()) {

            $entityManager->persist($campus);
            $entityManager->flush();

            // Préparation du message de redirection
            $this->addFlash('success', 'Campus ajoutée !');

            // Redirection vers la page de détail du profil
            return $this->redirectToRoute('admin_campus_list');
        }

        // Vue
        return $this->render('admin/campus_list.html.twig', [
            'campuses' => $campuses,
            'nom' => $nom,
            'addCampusForm' => $addCampusForm,
            'nonDeletableCampuses' => array_filter(
                array_map(fn($c) => $c->getId(), $campuses),
                fn($id) => $this->campusRepository->hasCampusUsedInSortie($id)
            ),
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    #[Route('/campus-edit/{id}', name: 'admin_campus_edit', methods: ['GET', 'POST'])]
    public function campusEdit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $campus = $this->campusRepository->find($id);

        if (!$campus) {
            throw $this->createNotFoundException('Campus introuvable.');
        }

        $editCampusForm = $this->createForm(AdminCampusType::class, $campus, [
            'include_submit' => false,
        ]);
        $editCampusForm->handleRequest($request);

        if ($editCampusForm->isSubmitted() && $editCampusForm->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Campus modifié avec succès !');
            return $this->redirectToRoute('admin_campus_list');
        }

        return $this->render('admin/campus_edit.html.twig', [
            'editCampusForm' => $editCampusForm,
            'campus' => $campus,
        ]);
    }

    #[Route('/campus-delete/{id}', name: 'admin_campus_delete', methods: ['POST'])]
    public function campusDelete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_campus_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_campus_list');
        }

        $campus = $this->campusRepository->find($id);

        if (!$campus) {
            throw $this->createNotFoundException('Campus introuvable.');
        }

        if ($this->campusRepository->hasCampusUsedInSortie($id)) {
            $this->addFlash('error', 'Ce campus ne peut pas être supprimée car il est associé à une sortie.');
            return $this->redirectToRoute('admin_campus_list');
        }

        $entityManager->remove($campus);
        $entityManager->flush();

        $this->addFlash('success', 'Campus supprimée !');
        return $this->redirectToRoute('admin_campus_list');
    }

    #[Route('/profiles-list', name: 'admin_profiles_list', methods: ['GET'])]
    public function usersList(Request $request): Response
    {
        // Construire le DTO de filtres depuis les paramètres GET
        $page = max(1, (int)$request->query->get('page', 1));

        $filtres = new FiltreParticipantDTO(
            nom: trim((string)$request->query->get('nom', '')),
            prenom: trim((string)$request->query->get('prenom', '')),
            pseudo: trim((string)$request->query->get('pseudo', '')),
            actif: $request->query->get('actif') === '1' ? true : ($request->query->get('actif') === '0' ? false : null),
            page: $page,
        );

        // Nombre de résultats par page
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Récupérer les participants filtrés avec pagination
        $participants = $this->participantRepository->findFiltered($filtres, $limit, $offset);

        // Compter le nombre total de résultats pour la pagination
        $total = $this->participantRepository->countFiltered($filtres);
        $totalPages = max(1, (int)ceil($total / $limit));

        return $this->render('admin/profiles_list.html.twig', [
            'participants' => $participants,
            'filtres' => $filtres,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    #[Route('/profile-create', name: 'admin_profile_create', methods: ['GET', 'POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager,
                               UserPasswordHasherInterface $passwordHasher): Response
    {
        // Créer un nouvel utilisateur (Participant)
        $participant = new Participant();

        // Créer le formulaire
        $profilCreateForm = $this->createForm(AdminProfilesType::class, $participant, [
            'include_actif' => false,
        ]);

        // Traiter le formulaire
        $profilCreateForm->handleRequest($request);

        // Si le formulaire est correctement soumis
        if ($profilCreateForm->isSubmitted() && $profilCreateForm->isValid()) {

            // Hasher le mot de passe (obligatoire à la création !)
            $plainPassword = $profilCreateForm->get('password')->getData();
            if (!empty($plainPassword)) {
                $hashed = $passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashed);
            }

            $entityManager->persist($participant);
            $entityManager->flush();

            // Préparation message flash
            $this->addFlash('success', 'Nouveau profil créé');

            // Redirection vers la liste des utilisateurs
            return $this->redirectToRoute('admin_profiles_list');
        }

        return $this->render('admin/profile_create.html.twig', [
            'profilCreateForm' => $profilCreateForm,
        ]);
    }

    #[Route('/profile-edit/{id}', name: 'admin_profile_edit', methods: ['GET', 'POST'])]
    public function editUser(int $id, Request $request, EntityManagerInterface $entityManager,
                             UserPasswordHasherInterface $passwordHasher): Response
    {
        // Récupérer le profil à éditer
        $participant = $this->participantRepository->find($id);

        if (!$participant) {
            throw $this->createNotFoundException('Profil introuvable.');
        }

        // Créer le formulaire avec le champ "actif"
        $profilEditForm = $this->createForm(AdminProfilesType::class, $participant, [
            'include_actif' => true,
        ]);

        // Traiter le formulaire
        $profilEditForm->handleRequest($request);

        // Si le formulaire est correctement soumis
        if ($profilEditForm->isSubmitted() && $profilEditForm->isValid()) {

            // Hasher le mot de passe, si un nouveau mot de passe a été fourni
            $plainPassword = $profilEditForm->get('password')->getData();
            if (!empty($plainPassword)) {
                $hashed = $passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashed);
            }

            $entityManager->flush();

            // Message flash et redirection
            $this->addFlash('success', 'Profil modifié avec succès !');

            return $this->redirectToRoute('admin_profiles_list');
        }

        return $this->render('admin/profile_edit.html.twig', [
            'profilEditForm' => $profilEditForm,
            'participant' => $participant,
        ]);
    }
}
