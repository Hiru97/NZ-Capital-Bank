<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'register') {
        handleRegistration();
    } elseif ($_POST['action'] == 'login') {
        handleLogin();
    }
}

function handleRegistration() {
    global $conn;

    $data = [
        ':username' => $_POST['username'],
        ':full_name' => $_POST['full_name'],
        ':email' => $_POST['email'],
        ':mobile' => $_POST['mobile'],
        ':nic' => $_POST['nic'],
        ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
    ];

    try {
        $conn->beginTransaction();

        // Insert user
        $sql = "INSERT INTO users (username, full_name, email, mobile, nic, password) 
                VALUES (:username, :full_name, :email, :mobile, :nic, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        
        // Get new user ID
        $user_id = $conn->lastInsertId();
        
        // Generate account number
        $account_number = 'NZ' . str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
        
        // Create account
        $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number) VALUES (?, ?)");
        $stmt->execute([$user_id, $account_number]);
        
        $conn->commit();
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: index.php");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        
        $error = "Registration failed: ";
        if ($e->errorInfo[1] == 1062) {
            $error = "Duplicate entry: ";
            if (strpos($e->getMessage(), 'username') !== false) {
                $error .= "Username already exists!";
            } elseif (strpos($e->getMessage(), 'email') !== false) {
                $error .= "Email already exists!";
            } elseif (strpos($e->getMessage(), 'nic') !== false) {
                $error .= "NIC already exists!";
            }
        } else {
            $error .= $e->getMessage();
        }
        
        $_SESSION['error'] = $error;
        header("Location: registration.php");
        exit();
    }
}

function handleLogin() {
    global $conn;

    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Fetch user and account information
        $sql = "SELECT users.*, accounts.account_number 
                FROM users
                LEFT JOIN accounts ON users.id = accounts.user_id
                WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Store user data in session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'account_number' => $user['account_number'] ?? 'N/A' // Handle missing account number
            ];
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid username or password!";
            header("Location: index.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Login failed: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}
?>