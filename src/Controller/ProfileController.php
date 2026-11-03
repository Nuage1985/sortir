<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ProfileType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil')]
final class ProfileController extends AbstractController
{
    #[Route('/{id}', name: 'profile_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editProfile(Participant $participant, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérifier que l'utilisateur ne peut modifier que son propre profil
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($participant !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que votre propre profil.');
        }

        // Créer le formulaire
        $profilForm = $this->createForm(ProfileType::class, $participant);

        // Traiter le formulaire
        $profilForm->handleRequest($request);

        // Si le formulaire est correctement soumis
        if ($profilForm->isSubmitted() && $profilForm->isValid()) {

            // Hasher le mot de passe, si un nouveau mot de passe a été fourni
            $plainPassword = $profilForm->get('password')->getData();
            if (!empty($plainPassword)) {
                $hashed = $passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashed);
            }

            $entityManager->persist($participant);
            $entityManager->flush();

            // Préparation du message de redirection
            $this->addFlash('success', 'Profil mis à jour !');

            // Redirection vers la page de détail du profil
            return $this->redirectToRoute('profile_details', ['id' => $participant->getId()]);
        }
        return $this->render('profil/edit.html.twig', [
            'participant' => $participant,
            'profilForm' => $profilForm,
        ]);
    }

    #[Route('/{id}/details', name: 'profile_details', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show($id, ParticipantRepository $participantRepository): Response
    {
        // Chercher le cours dans la BDD, en fonction de l'id reçu en URL
        $profile = $participantRepository->find($id);

        return $this->render('profil/show.html.twig', [
            'participant' => $profile,
        ]);
    }
}
