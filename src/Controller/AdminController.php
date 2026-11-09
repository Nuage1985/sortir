<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\AdminVillesType;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly VilleRepository $villeRepository,
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

        if ($nom !== '') {
            $villes = $this->villeRepository->findByNom($nom);
        } else {
            $villes = $this->villeRepository->findAll();
        }

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

        $this->addFlash('success', 'Ville supprimée avec succès !');
        return $this->redirectToRoute('admin_cities_list');
    }
}
