import { Controller } from '@hotwired/stimulus';

/*
 * Contrôleur qui affiche les détails d'un lieu (adresse, ville, code postal, coordonnées GPS)
 * lorsqu'un lieu est sélectionné dans un menu déroulant.
 *
 * Utilisation dans Twig :
 *   <div data-controller="lieu-details" data-lieu-details-lieux-value='{{ lieuxData|json_encode|e('html_attr') }}'>
 *     <select data-lieu-details-target="select" ...>
 *     <div data-lieu-details-target="details" class="hidden">
 *       <dd data-lieu-details-target="rue">-</dd>
 *       <dd data-lieu-details-target="ville">-</dd>
 *       <dd data-lieu-details-target="codePostal">-</dd>
 *       <dd data-lieu-details-target="coordonnees">-</dd>
 *     </div>
 *   </div>
 */
export default class extends Controller {
    static targets = ['select', 'details', 'rue', 'ville', 'codePostal', 'coordonnees'];
    static values = { lieux: Object };

    connect() {
        // Stimulus appelle connect() avant que tous les targets soient nécessairement reliés
        if (!this.hasSelectTarget) {
            return;
        }
        // Afficher les détails du lieu déjà sélectionné au chargement (page d'édition)
        this.updateDetails();
        this.selectTarget.addEventListener('change', () => this.updateDetails());
    }

    updateDetails() {
        const lieuId = this.selectTarget.value;
        const lieux = this.lieuxValue;

        if (lieuId && lieux[lieuId]) {
            const data = lieux[lieuId];
            this.rueTarget.textContent = data.rue || '-';
            this.villeTarget.textContent = data.ville || '-';
            this.codePostalTarget.textContent = data.codePostal || '-';

            let coords = '';
            if (data.latitude && data.longitude) {
                coords = data.latitude + ', ' + data.longitude;
            }
            this.coordonneesTarget.textContent = coords || '-';

            this.detailsTarget.classList.remove('hidden');
        } else {
            this.detailsTarget.classList.add('hidden');
        }
    }
}
