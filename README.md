# Zantus Life Science — Appointment Form (Validation)

This project adds a beginner-friendly, validated patient registration & appointment form for the hospital website.

Quick status
- Tasks implemented: HTML form (full set of fields), CSS styling for form, validation utility, live (debounced) validation, password strength meter, validation icons, submit prevention, prevent-paste on confirm password.
- Remaining optional enhancements: auto-format card numbers, card-type detection, loading spinner integration (can be added quickly if needed), unit tests.

Files you should know
- `appointments.html` — contains the appointment form (all requested fields and error span IDs).
- `form-styles.css` — CSS for the form (validation icons, password meter, error/success states).
- `form-validation.js` — reusable validator functions exposed as `window.FormValidator`:
  - validateEmail(email)
  - validatePassword(password)
  - validateCardNumber(cardNumber)  // Luhn
  - validatePhone(phone)
  - validatePin(pin)
  - validateExpiry(monthValue)
  - validateCVV(cvv)
  - validateFutureDate(dateStr)
- `form-script.js` — wiring and UI helpers (setError/setSuccess behavior, debounced input listeners, password meter, prevent paste, submit handling). This file uses the validators above and does not change their logic.

How to run
1. Open `appointments.html` in your browser (double-click or open by Live Server).
2. Ensure the following scripts are loaded (they are included near the bottom of `appointments.html`):
   ```html
   <script src="form-validation.js"></script>
   <script src="form-script.js"></script>
   ```
3. Interact with the form. The submit button is disabled until required fields are valid. On submit a simulated confirmation alert is shown.

Validation overview (high level)
- Required fields are checked and will show an error message and a red icon (✗) when invalid.
- Email uses a simple regex.
- Password must be at least 8 characters and include upper/lowercase, number, and special char (checked by `validatePassword`). Password strength meter shows weak→strong.
- Confirm password must match the password.
- Phone expects 10 digits starting 6–9 (Indian mobile format).
- PIN expects 6 digits.
- Age must be a number greater than 0.
- Appointment date must be today or in the future.
- Card number is validated with the Luhn algorithm.
- Expiry disallows past months.
- CVV must be 3 or 4 digits.

Developer notes / key functions
- `window.FormValidator.validateEmail(email)` — returns boolean.
- `window.FormValidator.validatePassword(password)` — returns boolean; used by `form-script.js`.
- `window.FormValidator.validateCardNumber(cardNumber)` — implements Luhn algorithm.
- UI helpers in `form-script.js`:
  - `setError(el, message)` — marks field with `.error`, sets error span text, and places ✗ in the `#<id>Icon` element if present.
  - `setSuccess(el)` — marks field `.success`, clears message and places ✓ in icon.

Test checklist (manual)
Fill the table when you run the tests in your browser.

| Test case | Expected result | Actual result | Status |
|---|---:|---|---:|
| All valid inputs (happy path) | Submit enabled; alert shown; form resets |  | |
| Invalid email (e.g. user@bad) | emailError shown; submit disabled |  | |
| Weak password (short) | passwordError shown; meter weak; submit disabled |  | |
| Passwords mismatch | confirmPasswordError shown; submit disabled |  | |
| Phone wrong (e.g. 12345) | phoneError shown |  | |
| PIN wrong (5 digits) | pinError shown |  | |
| Age negative or 0 | ageError shown |  | |
| Appointment date in past | appointmentDateError shown |  | |
| Card invalid (Luhn fail) | cardNumberError shown |  | |
| Expiry in past | expiryError shown |  | |
| CVV not 3/4 digits | cvvError shown |  | |

Debugging tips
- Open browser DevTools → Console to see script errors.
- Network tab: verify `form-validation.js` and `form-script.js` are loaded (no 404s).
- If you edit `form-script.js` or `form-validation.js`, refresh the page and clear cache (Ctrl+F5) to ensure the browser picks up changes.
