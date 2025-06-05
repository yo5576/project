<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';
require_once 'config/functions.php';

// Start secure session
start_secure_session();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user data
$user = get_user_by_id($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Get role-specific data
$roleData = [];
if ($user['role'] === 'voter') {
    $weredaName = get_wereda_name($user['role_specific_id']);
    $roleData['wereda'] = $weredaName;
    
    // Get voter ID
    $stmt = $pdo->prepare("SELECT voter_id FROM voters WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $voterData = $stmt->fetch();
    $roleData['voter_id'] = $voterData['voter_id'];
} else if ($user['role'] === 'candidate') {
    $candidateType = get_candidate_type_name($user['role_specific_id']);
    $roleData['candidate_type'] = $candidateType;
    
    // Get candidate details
    $stmt = $pdo->prepare("SELECT party, manifesto FROM candidates WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $candidateData = $stmt->fetch();
    $roleData['party'] = $candidateData['party'];
    $roleData['manifesto'] = $candidateData['manifesto'];
}

// Handle logout
if (isset($_POST['logout'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Election System</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="nav">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-brand">Election System</a>
            <ul class="nav-menu">
                <li>
                    <form method="POST" action="dashboard.php" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <button type="submit" name="logout" class="btn link">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="dashboard-grid">
            <!-- User Profile Card -->
            <div class="dashboard-card">
                <h3>Profile Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('F j, Y g:i a', strtotime($user['last_login'])) : 'N/A'; ?></p>
            </div>
            
            <!-- Role-specific Card -->
            <div class="dashboard-card">
                <h3><?php echo ucfirst($user['role']); ?> Details</h3>
                <?php if ($user['role'] === 'voter'): ?>
                    <p><strong>Voter ID:</strong> <?php echo htmlspecialchars($roleData['voter_id']); ?></p>
                    <p><strong>Wereda:</strong> <?php echo htmlspecialchars($roleData['wereda']); ?></p>
                <?php elseif ($user['role'] === 'candidate'): ?>
                    <p><strong>Candidate Type:</strong> <?php echo htmlspecialchars($roleData['candidate_type']); ?></p>
                    <?php if (!empty($roleData['party'])): ?>
                        <p><strong>Party:</strong> <?php echo htmlspecialchars($roleData['party']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($roleData['manifesto'])): ?>
                        <p><strong>Manifesto:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($roleData['manifesto'])); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="dashboard-card">
                <h3>Quick Actions</h3>
                <?php if ($user['role'] === 'voter'): ?>
                    <p>As a voter, you can:</p>
                    <ul>
                        <li>View your voter information</li>
                        <li>Update your profile</li>
                        <li>View election information</li>
                    </ul>
                <?php elseif ($user['role'] === 'candidate'): ?>
                    <p>As a candidate, you can:</p>
                    <ul>
                        <li>Update your manifesto</li>
                        <li>View election statistics</li>
                        <li>Manage your campaign</li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>