wpl_sessions_cookies

Purpose
- Minimal lab demonstrating secure registration, login, session management and "remember me" cookies.
- Reuses the project's central DB connection at `../include/config.php`.

Files
- index.php — login page (copy of project's login UI; posts to login_action.php)
- registration.php — registration page (copy of project's registration UI; posts to register_action.php)
- register_action.php — secure registration (server-side validation and password hashing)
- login_action.php — secure login, session setup, optional remember-me token creation
- init.php — include this on protected pages to auto-login via remember cookie if present
- logout.php — clears session and removes remember token/cookie

Database
- The project uses MySQL and database name `hms`.
- This module requires an `auth_tokens` table (for remember-me tokens). Create it once using:

CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `selector` VARCHAR(64) NOT NULL,
  `token_hash` VARCHAR(128) NOT NULL,
  `expires` DATETIME NOT NULL,
  INDEX (`selector`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

Notes
- The code uses prepared statements via the project's `$con` (mysqli) connection from `../include/config.php`.
- For local development (HTTP), cookies are not marked secure=true. In production use Secure and SameSite attributes and HTTPS.
- Existing plaintext passwords in your DB: this module will re-hash a password automatically on successful login if it matches the plaintext stored value (best-effort migration). Newly registered users will have hashed passwords.

How to test
1. Ensure your MySQL `hms` DB is running and `include/config.php` works.
2. Create the `auth_tokens` table above if you plan to use "Remember me".
3. Open in browser: http://localhost/hospital_hms/wpl_sessions_cookies/ (login) or /registration.php to create an account.
4. Use "Remember me" on login to create a persistent cookie — then close the browser and reopen to verify auto-login.

Security reminders
- Use HTTPS and set cookie Secure flag in production.
- Consider limiting token lifespan and periodically rotating tokens.
- Store token_hash using a secure hash (we use sha256 here). Consider HMAC with server secret for stronger protection.

