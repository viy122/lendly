export function evaluatePasswordStrength(password) {
    const requirements = {
        length: [...password].length >= 8,
        uppercase: /\p{Lu}/u.test(password),
        lowercase: /\p{Ll}/u.test(password),
        number: /\p{N}/u.test(password),
        special: /[\p{Z}\p{S}\p{P}]/u.test(password),
    };
    // shortcut: rates policy completion; use an entropy estimator if stronger guidance is needed.
    const score = Object.values(requirements).filter(Boolean).length;

    return {
        requirements,
        score,
        label: !password ? 'Enter a password' : score === 5 ? 'Strong' : score >= 3 ? 'Medium' : 'Weak',
    };
}
