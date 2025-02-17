<?php
require 'config.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$sender_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_account = $_POST['account_number'];
    $amount = (float)$_POST['amount'];
    
    try {
        $conn->beginTransaction();
        
        // Get sender account
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
        $stmt->execute([$sender_id]);
        $sender_account = $stmt->fetch();
        
        // Check sender balance
        if ($sender_account['balance'] < $amount) {
            throw new Exception("Insufficient funds");
        }
        
        // Get recipient account
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE account_number = ?");
        $stmt->execute([$recipient_account]);
        $recipient_account = $stmt->fetch();
        
        if (!$recipient_account) {
            throw new Exception("Recipient account not found");
        }
        
        // Update balances
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $sender_account['id']]);
        
        $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $recipient_account['id']]);
        
        // Record transaction
        $stmt = $conn->prepare("INSERT INTO transactions 
            (account_id, type, amount, related_account, description)
            VALUES (?, 'transfer', ?, ?, 'Fund transfer')");
        $stmt->execute([
            $sender_account['id'],
            $amount,
            $recipient_account['account_number']
        ]);
        
        $conn->commit();
        $_SESSION['success'] = "Transfer successful!";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: dashboard.php");
    exit();
}