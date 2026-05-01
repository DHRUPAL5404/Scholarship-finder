/**
 * Form Validation Script
 * Provides client-side form validation with real-time feedback
 */

// Password Strength Validation
function validatePassword(password) {
    const requirements = {
        minLength: password.length >= 8,
        hasNumber: /\d/.test(password),
        hasUpperCase: /[A-Z]/.test(password),
        hasLowerCase: /[a-z]/.test(password),
        hasSpecialChar: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
    };

    return requirements;
}

// Calculate password strength (0-5)
function getPasswordStrength(requirements) {
    let strength = 0;
    if (requirements.minLength) strength++;
    if (requirements.hasNumber) strength++;
    if (requirements.hasUpperCase) strength++;
    if (requirements.hasLowerCase) strength++;
    if (requirements.hasSpecialChar) strength++;
    return strength;
}

// Display password strength feedback
function displayPasswordFeedback(inputElement, feedbackElement) {
    const password = inputElement.value;
    const requirements = validatePassword(password);
    const strength = getPasswordStrength(requirements);

    let html = '<div class="password-feedback">';
    html += '<div class="password-strength">';
    html += '<span class="strength-label">Password Strength: </span>';

    // Strength bars
    for (let i = 0; i < 5; i++) {
        const barClass = i < strength ? 'filled' : 'empty';
        html += `<span class="strength-bar ${barClass}"></span>`;
    }

    if (strength <= 1) html += '<span class="strength-text weak">Weak</span>';
    else if (strength <= 2) html += '<span class="strength-text poor">Poor</span>';
    else if (strength <= 3) html += '<span class="strength-text fair">Fair</span>';
    else if (strength <= 4) html += '<span class="strength-text good">Good</span>';
    else html += '<span class="strength-text strong">Strong</span>';

    html += '</div>';

    // Requirements checklist
    html += '<div class="password-requirements">';
    html += `<div class="requirement ${requirements.minLength ? 'met' : 'unmet'}">
        <span class="icon">${requirements.minLength ? '✓' : '✗'}</span> At least 8 characters
    </div>`;
    html += `<div class="requirement ${requirements.hasNumber ? 'met' : 'unmet'}">
        <span class="icon">${requirements.hasNumber ? '✓' : '✗'}</span> At least 1 number
    </div>`;
    html += `<div class="requirement ${requirements.hasUpperCase ? 'met' : 'unmet'}">
        <span class="icon">${requirements.hasUpperCase ? '✓' : '✗'}</span> At least 1 uppercase letter
    </div>`;
    html += `<div class="requirement ${requirements.hasLowerCase ? 'met' : 'unmet'}">
        <span class="icon">${requirements.hasLowerCase ? '✓' : '✗'}</span> At least 1 lowercase letter
    </div>`;
    html += '<div class="requirement optional-info">Optional but recommended:</div>';
    html += `<div class="requirement ${requirements.hasSpecialChar ? 'met' : 'unmet'}">
        <span class="icon">${requirements.hasSpecialChar ? '✓' : '✗'}</span> Special character (!@#$%^&* etc)
    </div>`;
    html += '</div></div>';

    feedbackElement.innerHTML = html;
}

// Validate integer range (e.g., marks 0-100)
function validateRange(value, min, max) {
    const num = parseInt(value, 10);
    return !isNaN(num) && num >= min && num <= max;
}

// Validate positive number (e.g., income)
function validatePositive(value) {
    const num = parseFloat(value);
    return !isNaN(num) && num > 0;
}

// Validate email format
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validate mobile number (basic)
function validateMobile(mobile) {
    const mobileRegex = /^\d{10}$/;
    return mobileRegex.test(mobile.replace(/\D/g, ''));
}

// Display inline error
function showError(inputElement, message) {
    // Remove existing error if any
    const existingError = inputElement.parentElement.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }

    if (message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        inputElement.parentElement.appendChild(errorDiv);
        inputElement.classList.add('input-error');
    } else {
        inputElement.classList.remove('input-error');
    }
}

// Validate marks field
function validateMarks(value) {
    if (!value) return { valid: false, message: 'Marks are required' };
    if (!validateRange(value, 0, 100)) {
        return { valid: false, message: 'Marks must be between 0 and 100' };
    }
    return { valid: true, message: '' };
}

// Validate income field
function validateIncome(value) {
    if (!value) return { valid: false, message: 'Family income is required' };
    if (!validatePositive(value)) {
        return { valid: false, message: 'Family income must be a positive number' };
    }
    return { valid: true, message: '' };
}

// Validate age field
function validateAge(value) {
    if (!value) return { valid: false, message: 'Age is required' };
    const age = parseInt(value, 10);
    if (isNaN(age) || age < 1 || age > 120) {
        return { valid: false, message: 'Please enter a valid age (1-120)' };
    }
    return { valid: true, message: '' };
}

// Validate disability percentage
function validateDisabilityPercent(value) {
    if (!value) return { valid: true, message: '' };
    if (!validateRange(value, 0, 100)) {
        return { valid: false, message: 'Disability percentage must be between 0 and 100' };
    }
    return { valid: true, message: '' };
}

// Validate mobile field
function validateMobileField(value) {
    if (!value) return { valid: false, message: 'Mobile number is required' };
    if (!validateMobile(value)) {
        return { valid: false, message: 'Please enter a valid 10-digit mobile number' };
    }
    return { valid: true, message: '' };
}

// Validate email field
function validateEmailField(value) {
    if (!value) return { valid: false, message: 'Email is required' };
    if (!validateEmail(value)) {
        return { valid: false, message: 'Please enter a valid email address' };
    }
    return { valid: true, message: '' };
}

// Validate required field
function validateRequired(value, fieldName) {
    if (!value || value.trim() === '') {
        return { valid: false, message: `${fieldName} is required` };
    }
    return { valid: true, message: '' };
}

// Validate select/dropdown field
function validateSelect(value, fieldName) {
    if (!value || value === '') {
        return { valid: false, message: `Please select a ${fieldName}` };
    }
    return { valid: true, message: '' };
}

// Setup real-time validation on form input
function setupFormValidation(formSelector) {
    const form = document.querySelector(formSelector);
    if (!form) return;

    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        // Setup password field with real-time strength feedback
        if (input.type === 'password' && input.name === 'password') {
            const feedbackDiv = document.createElement('div');
            feedbackDiv.id = 'password-feedback';
            feedbackDiv.className = 'password-feedback-container';
            input.parentElement.appendChild(feedbackDiv);

            input.addEventListener('input', () => {
                displayPasswordFeedback(input, feedbackDiv);
            });

            // Validate on blur
            input.addEventListener('blur', () => {
                const requirements = validatePassword(input.value);
                if (!requirements.minLength || !requirements.hasNumber) {
                    showError(input, 'Password must be at least 8 characters with at least 1 number');
                } else {
                    showError(input, '');
                }
            });
        }

        // Setup marks field
        if (input.name === 'marks') {
            input.addEventListener('blur', () => {
                const validation = validateMarks(input.value);
                showError(input, validation.message);
            });

            input.addEventListener('input', () => {
                const validation = validateMarks(input.value);
                if (validation.message) {
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });
        }

        // Setup income field
        if (input.name === 'family_income') {
            input.addEventListener('blur', () => {
                const validation = validateIncome(input.value);
                showError(input, validation.message);
            });

            input.addEventListener('input', () => {
                const validation = validateIncome(input.value);
                if (input.value && !validation.valid) {
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });
        }

        // Setup age field
        if (input.name === 'age') {
            input.addEventListener('blur', () => {
                const validation = validateAge(input.value);
                showError(input, validation.message);
            });

            input.addEventListener('input', () => {
                const validation = validateAge(input.value);
                if (input.value && !validation.valid) {
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });
        }

        // Setup disability percent field
        if (input.name === 'disability_percent') {
            input.addEventListener('blur', () => {
                const validation = validateDisabilityPercent(input.value);
                showError(input, validation.message);
            });

            input.addEventListener('input', () => {
                const validation = validateDisabilityPercent(input.value);
                if (input.value && !validation.valid) {
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });
        }

        // Setup mobile field
        if (input.name === 'mobile') {
            input.addEventListener('blur', () => {
                const validation = validateMobileField(input.value);
                showError(input, validation.message);
            });

            input.addEventListener('input', () => {
                const validation = validateMobileField(input.value);
                if (input.value && !validation.valid) {
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });
        }

        // Setup email field
        if (input.type === 'email') {
            input.addEventListener('blur', () => {
                const validation = validateEmailField(input.value);
                showError(input, validation.message);
            });

            input.addEventListener('input', () => {
                const validation = validateEmailField(input.value);
                if (input.value && !validation.valid) {
                    input.classList.add('input-error');
                } else {
                    input.classList.remove('input-error');
                }
            });
        }

        // Setup text field (name, etc)
        if (input.type === 'text' && (input.name === 'name' || input.name === 'full_name')) {
            input.addEventListener('blur', () => {
                const fieldName = input.name === 'full_name' ? 'Full name' : 'Name';
                const validation = validateRequired(input.value, fieldName);
                showError(input, validation.message);
            });
        }

        // Setup select fields
        if (input.tagName === 'SELECT') {
            const fieldName = input.getAttribute('data-field-name') || input.name;
            input.addEventListener('blur', () => {
                const validation = validateSelect(input.value, fieldName);
                showError(input, validation.message);
            });
        }
    });

    // Form submit validation
form.addEventListener('submit', (e) => {
        // DEBUG BYPASS FOR PROFILE FORM
        if(form.classList.contains('profile-form')) {
            console.log('🔓 Profile form validation BYPASSED');
            return; // Allow submit
        }
        
        let isValid = true;
        
        inputs.forEach(input => {
            let validation = { valid: true, message: '' };


            if (input.type === 'password' && input.name === 'password') {
                const requirements = validatePassword(input.value);
                if (!input.value) {
                    validation = { valid: false, message: 'Password is required' };
                } else if (!requirements.minLength || !requirements.hasNumber) {
                    validation = { valid: false, message: 'Password must be at least 8 characters with at least 1 number' };
                }
            } else if (input.name === 'marks') {
                validation = validateMarks(input.value);
            } else if (input.name === 'family_income') {
                validation = validateIncome(input.value);
            } else if (input.name === 'age') {
                validation = validateAge(input.value);
            } else if (input.name === 'disability_percent' && input.value) {
                validation = validateDisabilityPercent(input.value);
            } else if (input.name === 'mobile') {
                validation = validateMobileField(input.value);
            } else if (input.type === 'email') {
                validation = validateEmailField(input.value);
            } else if ((input.name === 'name' || input.name === 'full_name') && input.type === 'text') {
                const fieldName = input.name === 'full_name' ? 'Full name' : 'Name';
                validation = validateRequired(input.value, fieldName);
            } else if (input.tagName === 'SELECT') {
                const fieldName = input.getAttribute('data-field-name') || input.name;
                validation = validateSelect(input.value, fieldName);
            }

            if (!validation.valid) {
                isValid = false;
                showError(input, validation.message);
            } else {
                showError(input, '');
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });
}

// Initialize validation when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Auto-setup for all forms with class 'validated-form' or any form
    setupFormValidation('form');
});
