import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'loader', 'upload'];

    submit() {
        if (!this.element.checkValidity()) {
            return;
        }
        this.uploadTarget.classList.add('hidden');      // cache le champ fichier
        this.loaderTarget.classList.remove('hidden');   // affiche l'alerte + la barre
        this.buttonTarget.disabled = true;
        this.buttonTarget.textContent = 'Import en cours…';
    }
}
