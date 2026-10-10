import './bootstrap';
import './map';
import './calendar';
import { evaluatePasswordStrength } from './password-strength';

document.addEventListener('alpine:init', () => {
    Alpine.data('passwordStrength', (wire, model) => ({
        get strength() {
            return evaluatePasswordStrength(wire.get(model));
        },
    }));
});
