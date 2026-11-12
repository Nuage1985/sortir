<?php

namespace App\Tests\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SortieControllerTest extends WebTestCase
{
    public function testAccessDeniedIfUserNotConnected(): void
    {
        // Arrange //
        $client = static::createClient();

        // Act //

        $client->request('GET', '/sorties/creer');

        // Assert //

        // On vérifie qu'on obtient une redirection si l'utilisateur n'est pas connecté
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        // On vérifie qu'on est bien redirigé vers la page de login
        $client->followRedirect();
        $this->assertRouteSame('main_home');
    }

    public function testAccessAuthorizedIfUserConnected(): void
    {
        // Arrange //

        $client = static::createClient();

        // on récupère le repository des participants (users) dans le container
        $participantRepository = static::getContainer()->get(ParticipantRepository::class);

        // on recherche un utilisateur selon son email (ici l'administrateur).
        $user = $participantRepository->findOneBy(['mail' => 'admin@eni.fr']);

        // Act //

        // on simule la connexion
        $client->loginUser($this->getUser());
        $client->request('GET', '/sorties/liste');

        // Assert //

        // On vérifie qu'on obtient bien l'accès
        $this->assertResponseIsSuccessful();
        // On vérifie l'affichage du tableau, visible que si l'utilisateur est connecté.
        $this->assertSelectorExists('table');
    }
    private function getUser(): Participant
    {
        // on récupère le repository des utilisateurs dans le container
        $userRepository = static::getContainer()->get(ParticipantRepository::class);

        // on recherche un utilisateur selon son email (ici l'administrateur).
        return $userRepository->findOneBy(['mail' => 'admin@eni.fr']);
    }

    public function testFormIsInvalidIfTitleHaveTooSmallLength(): void
    {
        // Arrange //

        $client = static::createClient();

        // on simule la connexion
        $client->loginUser($this->getUser());
        $client->request('GET', '/sorties/creer');

        // Act //

        // On simule la soumission du formulaire
        $client->submitForm("Enregistrer", [
            'sortie[nom]' => 'T',
            'sortie[dateHeureDebut]'    => '2026-12-01 17:00',
            'sortie[duree]'             => '120',
            'sortie[dateLimiteInscription]' => '2026-11-30',
            'sortie[nbInscriptionMax]'  => '6',
            'sortie[lieu]'              => '1',
            'sortie[infosSortie]'       => 'Description test lorem ipsum dolor sit amet',
        ]);

        // Assert //

        // On vérifie que la soumission a bien réalisé un retour vers le formulaire
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
    }
}
