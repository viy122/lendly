import './bootstrap';
import './map';
import './calendar';
import './admin-rental-detail';
import { evaluatePasswordStrength } from './password-strength';

document.addEventListener('alpine:init', () => {
    Alpine.data('passwordStrength', (wire, model) => ({
        get strength() {
            return evaluatePasswordStrength(wire.get(model));
        },
    }));
});
