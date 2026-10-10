import assert from 'node:assert/strict';
import test from 'node:test';
import { evaluatePasswordStrength } from '../resources/js/password-strength.js';

test('strength progresses as password requirements are met', () => {
    assert.deepEqual(evaluatePasswordStrength(''), {
        requirements: { length: false, uppercase: false, lowercase: false, number: false, special: false },
        score: 0,
        label: 'Enter a password',
    });
    assert.equal(evaluatePasswordStrength('password').label, 'Weak');
    assert.equal(evaluatePasswordStrength('Password').label, 'Medium');
    assert.equal(evaluatePasswordStrength('Password1!').label, 'Strong');
    assert.equal(evaluatePasswordStrength('Password1!').score, 5);
});

test('a missing requirement prevents a strong rating', () => {
    for (const [password, requirement] of [
        ['Aa1!bbb', 'length'],
        ['password1!', 'uppercase'],
        ['PASSWORD1!', 'lowercase'],
        ['Password!', 'number'],
        ['Password1', 'special'],
    ]) {
        const strength = evaluatePasswordStrength(password);

        assert.equal(strength.requirements[requirement], false);
        assert.equal(strength.label, 'Medium');
        assert.equal(strength.score, 4);
    }
});

test('counts Unicode characters and recognizes the server-supported character classes', () => {
    assert.equal(evaluatePasswordStrength('Äbcdef١!').label, 'Strong');
    assert.equal(evaluatePasswordStrength('Abcdef1😀').label, 'Strong');
    assert.equal(evaluatePasswordStrength('Aa1😀bbb').requirements.length, false);
});
