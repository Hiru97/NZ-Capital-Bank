<?php
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Fetch sender's account details
try {
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $sender_account = $stmt->fetch();

    if (!$sender_account) {
        throw new Exception("Your account was not found. Please contact support.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}

// Fetch transaction history
try {
    $stmt = $conn->prepare("SELECT * FROM transactions 
                          WHERE account_id = ?
                          ORDER BY created_at DESC");
    $stmt->execute([$sender_account['id']]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    $transactions = []; // If there's an error, set transactions to an empty array
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_account_number = trim($_POST['account_number']);
    $amount = (float)$_POST['amount'];
    $beneficiary_name = trim($_POST['beneficiary_name']);
    $purpose = trim($_POST['purpose']);
    $sender_remark = trim($_POST['sender_remark']);
    $beneficiary_remark = trim($_POST['beneficiary_remark']);

    // Validate input
    if (empty($recipient_account_number)) {
        $error = "Recipient account number is required.";
    } elseif (empty($beneficiary_name)) {
        $error = "Beneficiary name is required.";
    } elseif (empty($purpose)) {
        $error = "Purpose of transfer is required.";
    } elseif ($amount <= 0) {
        $error = "Amount must be greater than 0.";
    } else {
        try {
            $conn->beginTransaction();

            // Fetch recipient's account
            $stmt = $conn->prepare("SELECT * FROM accounts WHERE account_number = ?");
            $stmt->execute([$recipient_account_number]);
            $recipient_account = $stmt->fetch();

            // Check if recipient account exists
            if (!$recipient_account) {
                throw new Exception("Recipient account not found. Please check the account number.");
            }

            // Check if recipient is the same as sender
            if ($recipient_account['id'] === $sender_account['id']) {
                throw new Exception("You cannot transfer funds to your own account.");
            }

            // Check if sender has sufficient balance
            if ($amount > $sender_account['balance']) {
                throw new Exception("Insufficient funds. Your available balance is NZ$ " . number_format($sender_account['balance'], 2));
            }

            // Deduct amount from sender
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $sender_account['id']]);

            // Add amount to recipient
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $recipient_account['id']]);

            // Record transaction
            $stmt = $conn->prepare("INSERT INTO transactions 
                (account_id, type, amount, related_account, description, sender_remark, beneficiary_remark, beneficiary_name, purpose)
                VALUES (?, 'transfer', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $sender_account['id'],
                $amount,
                $recipient_account['account_number'],
                "Fund transfer to account {$recipient_account['account_number']}",
                $sender_remark,
                $beneficiary_remark,
                $beneficiary_name,
                $purpose
            ]);

            $conn->commit();
            $success = "Transfer successful! NZ$ " . number_format($amount, 2) . " has been sent to account {$recipient_account['account_number']}.";
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Transfer failed due to a system error. Please try again later.";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NZ Capital Bank - Transfer Funds</title>
    <link rel="stylesheet" href="dashboard.css"> <!-- Reuse the same CSS -->
</head>
<body>
    <nav>
        <div class="logo">
            <img src="./assets/logo.jpeg" alt="NZ Capital Bank">
            <h1 style="color: white;">NZ Capital Bank</h1>
        </div>
        <div class="nav-links">
            <span class="account-info">
                A/C: <?= htmlspecialchars($_SESSION['user']['account_number']) ?>
            </span>
            <a href="dashboard.php">Home</a>
            <a href="accounts.php">Accounts</a>
            <a href="transfer_full.php">Transfer</a>
            <a href="pay_bill.php">Bill Payment</a>
            <a href="card.php">Credit Card Payment</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="dashboard-grid">
        <!-- Transfer Funds Card -->
        <div class="card">
            <h2>Transfer Funds</h2>
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form action="transfer_full.php" method="POST">
                <div class="form-group">
                    <label for="account_number">Recipient Account Number:</label>
                    <input type="text" id="account_number" name="account_number" placeholder="Enter recipient's account number" required>
                </div>
                <div class="form-group">
                    <label for="beneficiary_name">Beneficiary Name:</label>
                    <input type="text" id="beneficiary_name" name="beneficiary_name" placeholder="Enter beneficiary's name" required>
                </div>
                <div class="form-group">
                    <label for="amount">Amount (NZ$):</label>
                    <input type="number" id="amount" name="amount" step="0.01" placeholder="Enter amount" required>
                </div>
                <div class="form-group">
                    <label for="purpose">Purpose of Transfer:</label>
                    <input type="text" id="purpose" name="purpose" placeholder="Enter purpose (e.g., Rent, Loan Repayment)" required>
                </div>
                <div class="form-group">
                    <label for="sender_remark">Your Remark:</label>
                    <textarea id="sender_remark" name="sender_remark" placeholder="Enter your remark (optional)"></textarea>
                </div>
                <div class="form-group">
                    <label for="beneficiary_remark">Beneficiary Remark:</label>
                    <textarea id="beneficiary_remark" name="beneficiary_remark" placeholder="Enter remark for beneficiary (optional)"></textarea>
                </div>
                <button type="submit" class="transfer-button">Transfer Funds</button>
            </form>
        </div>

        <!-- Sender Account Summary -->
        <div class="card">
            <h2>Your Account Summary</h2>
            <div class="details">
                <p><strong>Account Number:</strong> <?= htmlspecialchars($sender_account['account_number']) ?></p>
                <p><strong>Available Balance:</strong> NZ$ <?= number_format($sender_account['balance'], 2) ?></p>
            </div>

            <br>
            <br>
            <br>

        <!-- Transaction History -->
        <div class="card">
            <h2>Transaction History</h2>
            <?php if (!empty($transactions)): ?>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount (NZ$)</th>
                            <th>Related Account</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?= date('d M Y H:i', strtotime($txn['created_at'])) ?></td>
                                <td><?= ucfirst($txn['type']) ?></td>
                                <td><?= number_format($txn['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($txn['related_account']) ?></td>
                                <td><?= htmlspecialchars($txn['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No transactions found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>