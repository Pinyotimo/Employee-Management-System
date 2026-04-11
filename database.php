<?php
session_start();
$dataFile = 'database.json';

// Initialize Database if it doesn't exist
if (!file_exists($dataFile)) {
    $initialData = [
        'payments' => [],
        'users' => [
            [
                'id' => 1,
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'name' => 'Administrator',
                'company_id' => 1,
                'company_name' => 'Default Company'
            ]
        ]
    ];
    file_put_contents($dataFile, json_encode($initialData, JSON_PRETTY_PRINT));
}

// Load Database
$db = json_decode(file_get_contents($dataFile), true);

if (!is_array($db) || !isset($db['users']) || !is_array($db['users'])) {
    $db = ['users' => [], 'payments' => []];
}

if (!isset($db['payments']) || !is_array($db['payments'])) {
    $db['payments'] = [];
}

foreach ($db['users'] as &$user) {
    $user = normalizeUser($user);
}
unset($user);

foreach ($db['payments'] as &$payment) {
    $payment = normalizePayment($payment);
}
unset($payment);

// Function to save changes
function saveDb($db) {
    global $dataFile;

    if (isset($db['users']) && is_array($db['users'])) {
        foreach ($db['users'] as &$user) {
            $user = normalizeUser($user);
        }
        unset($user);
    }

    if (!isset($db['payments']) || !is_array($db['payments'])) {
        $db['payments'] = [];
    }

    foreach ($db['payments'] as &$payment) {
        $payment = normalizePayment($payment);
    }
    unset($payment);

    file_put_contents($dataFile, json_encode($db, JSON_PRETTY_PRINT));
}

function normalizeUser(array $user): array {
    $defaults = [
        'id' => 0,
        'username' => '',
        'password' => '',
        'email' => '',
        'employee_number' => '',
        'age' => 0,
        'years_experience' => 0,
        'name' => '',
        'role' => 'employee',
        'company_id' => 0,
        'company_name' => '',
        'hourly_rate' => 0,
        'task' => 'No task assigned yet.',
        'status' => 'idle',
        'clock_in_time' => null,
        'break_started_at' => null,
        'accumulated_break_seconds' => 0,
        'pending_hours' => 0,
        'approved_pay' => 0,
        'password_reset_token_hash' => '',
        'password_reset_expires_at' => 0
    ];

    return array_merge($defaults, $user);
}

function normalizePayment(array $payment): array {
    $defaults = [
        'id' => '',
        'transaction_id' => '',
        'employee_id' => 0,
        'employee_name' => '',
        'company_id' => 0,
        'hours_paid' => 0,
        'hourly_rate' => 0,
        'amount' => 0,
        'payment_mode' => 'Bank Transfer',
        'created_at' => time(),
        'approved_by' => ''
    ];

    return array_merge($defaults, $payment);
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): void {
    header("Location: {$location}");
    exit;
}

function findUserIndexById(array $db, $id): ?int {
    foreach ($db['users'] as $index => $user) {
        if ((string)$user['id'] === (string)$id) {
            return $index;
        }
    }

    return null;
}

function findUserByUsername(array $db, string $username): ?array {
    foreach ($db['users'] as $user) {
        if (strcasecmp($user['username'], $username) === 0) {
            return $user;
        }
    }

    return null;
}

function findUserIndexByResetToken(array $db, string $token): ?int {
    foreach ($db['users'] as $index => $user) {
        $hash = (string)($user['password_reset_token_hash'] ?? '');
        $expiresAt = (int)($user['password_reset_expires_at'] ?? 0);

        if ($hash !== '' && $expiresAt >= time() && password_verify($token, $hash)) {
            return $index;
        }
    }

    return null;
}

function clearPasswordResetToken(array &$db, int $userIndex): void {
    $db['users'][$userIndex]['password_reset_token_hash'] = '';
    $db['users'][$userIndex]['password_reset_expires_at'] = 0;
}

function generatePasswordResetToken(array &$db, int $userIndex): string {
    $token = bin2hex(random_bytes(32));
    $db['users'][$userIndex]['password_reset_token_hash'] = password_hash($token, PASSWORD_DEFAULT);
    $db['users'][$userIndex]['password_reset_expires_at'] = time() + 3600;

    return $token;
}

function passwordMatches(array $user, string $password): bool {
    $storedPassword = (string)($user['password'] ?? '');

    if ($storedPassword === '') {
        return false;
    }

    if (password_get_info($storedPassword)['algo'] !== null) {
        return password_verify($password, $storedPassword);
    }

    return hash_equals($storedPassword, $password);
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function generateTransactionId(): string {
    return 'TXN-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('YmdHis');
}

function formatDuration(int $seconds): string {
    $seconds = max(0, $seconds);
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $remainingSeconds = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
}

function paymentModes(): array {
    return [
        'Bank Transfer',
        'Cash',
        'Mobile Money',
        'Cheque'
    ];
}

function workedSecondsForUser(array $user, ?int $referenceTime = null): int {
    $referenceTime = $referenceTime ?? time();
    $clockInTime = (int)($user['clock_in_time'] ?? 0);

    if ($clockInTime <= 0) {
        return 0;
    }

    $breakSeconds = (int)($user['accumulated_break_seconds'] ?? 0);
    if (($user['status'] ?? 'idle') === 'paused' && !empty($user['break_started_at'])) {
        $breakSeconds += max(0, $referenceTime - (int)$user['break_started_at']);
    }

    return max(0, $referenceTime - $clockInTime - $breakSeconds);
}

// Handle global Logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}
?>
