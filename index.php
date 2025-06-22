<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MiniChat Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/fontawesome.min.css" rel="stylesheet" />
    <link href="assets/css/auth.css" rel="stylesheet" />
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-comments"></i>
            </div>
            <h1 class="login-title">MiniChat</h1>
            <p class="login-subtitle">Secure messaging with end-to-end encryption</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="api/login.php" novalidate id="loginForm">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input id="username" name="username" class="form-control" required placeholder="Enter your username" />
                <i class="fas fa-user input-icon"></i>
                <div id="usernameError" class="invalid-feedback">
                    Must start with a letter and contain only letters, numbers, hyphens, and underscores
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input id="password" name="password" type="password" class="form-control" required placeholder="Enter your password" />
                <i class="fas fa-lock input-icon"></i>
                <div id="passwordRequirements" class="password-strength" style="display: none;">
                    <div class="requirement" id="req-length">
                        <span class="req-icon">○</span> At least 8 characters
                    </div>
                    <div class="requirement" id="req-letter">
                        <span class="req-icon">○</span> At least one letter
                    </div>
                    <div class="requirement" id="req-digit">
                        <span class="req-icon">○</span> At least one digit
                    </div>
                    <div class="requirement" id="req-special">
                        <span class="req-icon">○</span> At least one special character
                    </div>
                </div>
                <div id="passwordError" class="invalid-feedback">
                    Password does not meet requirements
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt me-2"></i>
                Sign In
            </button>
        </form>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function isValidUsername(username) {
            return /^[a-zA-Z][a-zA-Z0-9_-]{2,}$/.test(username);
        }

        function isValidPassword(password) {
            return password.length >= 8 && 
                   /[0-9]/.test(password) && 
                   /[a-zA-Z]/.test(password) && 
                   /[^a-zA-Z0-9]/.test(password);
        }

        function checkPasswordStrength(password) {
            const requirements = {
                length: password.length >= 8,
                letter: /[a-zA-Z]/.test(password),
                digit: /[0-9]/.test(password),
                special: /[^a-zA-Z0-9]/.test(password)
            };

            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(`req-${req}`);
                if (element) {
                    element.className = `requirement ${requirements[req] ? 'met' : 'unmet'}`;
                    const icon = element.querySelector('.req-icon');
                    if (icon) {
                        icon.textContent = requirements[req] ? '✓' : '○';
                    }
                }
            });

            return requirements;
        }

        const usernameInput = document.getElementById('username');
        const usernameError = document.getElementById('usernameError');
        const passwordInput = document.getElementById('password');
        const passwordError = document.getElementById('passwordError');
        const passwordRequirements = document.getElementById('passwordRequirements');
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');

        // Real-time username validation
        usernameInput.addEventListener('input', function() {
            const username = this.value.trim();
            if (username && !isValidUsername(username)) {
                this.classList.add('is-invalid');
                usernameError.style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                usernameError.style.display = 'none';
            }
        });

        // Real-time password validation
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length > 0) {
                passwordRequirements.style.display = 'block';
                const requirements = checkPasswordStrength(password);
                
                if (isValidPassword(password)) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    passwordError.style.display = 'none';
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    passwordError.style.display = 'block';
                }
            } else {
                passwordRequirements.style.display = 'none';
                this.classList.remove('is-valid', 'is-invalid');
                passwordError.style.display = 'none';
            }
        });

        // Form submission validation
        loginForm.addEventListener('submit', function(e) {
            const username = usernameInput.value.trim();
            const password = passwordInput.value;
            
            let hasError = false;
            
            if (!isValidUsername(username)) {
                usernameInput.classList.add('is-invalid');
                usernameError.style.display = 'block';
                hasError = true;
            }
            
            if (!isValidPassword(password)) {
                passwordInput.classList.add('is-invalid');
                passwordError.style.display = 'block';
                passwordRequirements.style.display = 'block';
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
                if (!isValidUsername(username)) {
                    usernameInput.focus();
                } else if (!isValidPassword(password)) {
                    passwordInput.focus();
                }
            } else {
                // Show loading state
                loginBtn.classList.add('btn-loading');
                loginBtn.disabled = true;
            }
        });

        // Add some interactive animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>