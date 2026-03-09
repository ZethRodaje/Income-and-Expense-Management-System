<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'FinanceDB');

if (session_status() === PHP_SESSION_NONE) session_start();

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
    $conn->set_charset('utf8mb4');
    return $conn;
}

function isLoggedIn() {
    return isset($_SESSION['userID']);
}

function requireLogin() {
    if (!isLoggedIn()) { header('Location: login.php'); exit(); }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $conn = getConnection();
    $id   = $_SESSION['userID'];
    // Ensure profile_photo column exists before querying it
    $conn->query("ALTER TABLE User ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");
    $stmt = $conn->prepare("SELECT u.userID, u.roleID, u.fullname, u.email, u.contact_number, u.address, IFNULL(u.profile_photo, '') as profile_photo, r.role_name FROM User u JOIN Role r ON u.roleID=r.roleID WHERE u.userID=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close(); $conn->close();
    return $row;
}

// Get all businesses for the current user
function getUserBusinesses($userID) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM Business WHERE userID=? ORDER BY created_at ASC");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close(); $conn->close();
    return $rows;
}

// Get the currently active business (stored in session)
function getActiveBusiness($userID) {
    $businesses = getUserBusinesses($userID);
    if (empty($businesses)) return null;

    // If session has a valid businessID for this user, use it
    if (isset($_SESSION['activeBizID'])) {
        foreach ($businesses as $b) {
            if ($b['businessID'] == $_SESSION['activeBizID']) return $b;
        }
    }

    // Default to first business and store in session
    $_SESSION['activeBizID'] = $businesses[0]['businessID'];
    return $businesses[0];
}

// Switch active business (validates ownership)
function switchBusiness($userID, $bizID) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM Business WHERE businessID=? AND userID=?");
    $stmt->bind_param("ii", $bizID, $userID);
    $stmt->execute();
    $biz = $stmt->get_result()->fetch_assoc();
    $stmt->close(); $conn->close();
    if ($biz) {
        $_SESSION['activeBizID'] = $bizID;
        return true;
    }
    return false;
}

// Legacy alias so existing pages using getBusiness() still work
function getBusiness($userID) {
    return getActiveBusiness($userID);
}
?>
