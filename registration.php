<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NZ Capital Bank - Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <img src="./assets/logo.jpeg" alt="NZ Capital Bank" class="logo">
        <h2>Create Account</h2>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form id="registerForm" action="auth.php" method="post" onsubmit="return validateForm()">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Mobile Number:</label>
                <input type="tel" name="mobile" pattern="[0-9]{10}" required>
            </div>

            <div class="form-group">
                <label>Account Number:</label>
                <input type="text" name="account_number" pattern="[0-9]{12}" required>
            </div>

            <div class="form-group">
                <label>NIC Number:</label>
                <input type="text" name="nic" pattern="[0-9]{9}V" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>

            <button type="submit" class="btn-primary">
    <span class="btn-text">Register</span>
    <span class="btn-icon">→</span>
</button>
        </form>
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>

    <script src="script.js"></script>
</body>
</html>