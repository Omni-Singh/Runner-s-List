<?php
// Validate a CSUB email address, return true if valid
function validate_csub_email(string $email): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return substr(strtolower($email), -9) === '@csub.edu';
}


// Validate password strength, must be >= 8 chars, at least one uppercase, one lowercase, one digit.
function validate_password_strength(string $pass): bool {
    return strlen($pass) >= 8
        && preg_match('/[a-z]/', $pass)
        && preg_match('/[A-Z]/', $pass)
        && preg_match('/\d/', $pass);
}
