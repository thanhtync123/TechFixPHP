<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for local debug

// DB config (shared)
$dbPath = __DIR__ . '/../../config/db.php';
if (!file_exists($dbPath)) {
    error_log("DB config not found: $dbPath");
    die('Database configuration missing.');
}
require_once $dbPath;

/* Helper to support projects using PDO or MySQLi (same style as other pages) */
function has_pdo() { return isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO; }
function has_mysqli() { return (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) || (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli); }
function get_mysqli() {
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) return $GLOBALS['conn'];
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) return $GLOBALS['mysqli'];
    return null;
}

$errors = [];
$old = ['Name'=>'','Phone'=>'','Address'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['Name'] ?? '');
    $phone = trim($_POST['Phone'] ?? '');
    $password = $_POST['Password'] ?? '';
    $address = trim($_POST['Address'] ?? '');
    $role = 'customer';

    $old['Name'] = htmlspecialchars($name, ENT_QUOTES);
    $old['Phone'] = htmlspecialchars($phone, ENT_QUOTES);
    $old['Address'] = htmlspecialchars($address, ENT_QUOTES);

    // Basic validation
    if ($name === '') $errors[] = 'Vui lòng nhập tên.';
    if ($phone === '') $errors[] = 'Vui lòng nhập số điện thoại.';
    if ($password === '' || strlen($password) < 6) $errors[] = 'Mật khẩu phải ít nhất 6 ký tự.';

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Check if phone exists
        $exists = false;
        if (has_pdo()) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
            $stmt->execute([$phone]);
            $exists = (bool) $stmt->fetchColumn();
        } elseif (has_mysqli()) {
            $mysqli = get_mysqli();
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
        } else {
            $errors[] = 'No database connection available.';
        }

        if ($exists) {
            $errors[] = 'Số điện thoại đã được sử dụng.';
        } else {
            // Insert user
            $inserted = false;
            if (has_pdo()) {
                $stmt = $pdo->prepare("INSERT INTO users (name, phone, password, address, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $inserted = $stmt->execute([$name, $phone, $hashed, $address, $role]);
            } elseif (has_mysqli()) {
                $mysqli = get_mysqli();
                $stmt = $mysqli->prepare("INSERT INTO users (name, phone, password, address, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('sssss', $name, $phone, $hashed, $address, $role);
                $inserted = $stmt->execute();
                if ($stmt) $stmt->close();
            }

            if ($inserted) {
                header('Location: /TechFixPHP/login.php?registered=1');
                exit;
            } else {
                $errors[] = 'Đăng ký thất bại. Vui lòng thử lại.';
            }
        }
    }
}

// Prepare toast message for client-side
$toastMessage = '';
$toastType = 'info';
if (!empty($errors)) {
    $toastMessage = implode(' ', $errors);
    $toastType = 'error';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - TECHFIX</title>
    <link href="/TechFixPHP/assets/css/register.css" rel="stylesheet" />
    <style>
        /* minimal fallback styling for toast (kept small) */
        .toast { position: fixed; bottom: 20px; right: 20px; background: #333; color: #fff; padding: 1rem 1.5rem; border-radius: 8px; opacity: 0; transition: opacity 0.3s; z-index: 9999; }
        .toast.show { opacity: 1; }
    </style>
</head>
<body>

    <div class="register-page">
        <div class="particles">
            <script>
                const particlesContainer = document.querySelector('.particles');
                for (let i = 0; i < 30; i++) {
                    const p = document.createElement("div");
                    p.className = "particle";
                    const left = Math.floor(Math.random() * 100);
                    const top = Math.floor(Math.random() * 100);
                    const delay = (Math.random() * 15).toFixed(2);
                    p.style.left = left + "%";
                    p.style.top = top + "%";
                    p.style.animationDelay = delay + "s";
                    // Append to container
                    particlesContainer.appendChild(p);
                }
            </script>
        </div>
        
        <div class="register-card">
            <div class="register-header">
                <h2>Đăng Ký</h2>
                <p>Trải nghiệm dịch vụ tiện ích cùng TECHFIX</p>
            </div>

            <form id="registerForm" name="form_users" class="register-form" method="POST" novalidate>
                
                <div class="form-group icon-input">
                    <span class="icon"></span>
                    <input type="text" id="name" name="Name" placeholder="Tên người dùng" class="form-control" required value="<?= $old['Name'] ?>">
                    <span class="validation-message" data-valmsg-for="Name"></span>
                </div>

                <div class="form-group icon-input">
                    <span class="icon"></span>
                    <input type="text" id="phone" name="Phone" placeholder="Số điện thoại" class="form-control" required value="<?= $old['Phone'] ?>">
                    <span class="validation-message" data-valmsg-for="Phone"></span>
                </div>

                <div class="form-group icon-input">
                    <span class="icon"></span>
                    <input type="password" id="password" name="Password" placeholder="Mật khẩu" class="form-control" required>
                    <span class="validation-message" data-valmsg-for="Password"></span>
                </div>

                <div class="form-group icon-input">
                    <span class="icon"></span>
                    <textarea id="address" name="Address" placeholder="Địa chỉ" class="form-control" style="max-height: 100px; overflow-y: auto;"><?= $old['Address'] ?></textarea>
                    <span class="validation-message" data-valmsg-for="Address"></span>
                </div>

                <button type="submit" class="btn-register">Đăng Ký Ngay</button>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const toast = document.getElementById("toast");
        function showToast(message, type = "info") {
            toast.textContent = message;
            toast.style.background = type === "error" ? "#d9534f" :
                                     type === "success" ? "#28a745" :
                                     type === "warning" ? "#f0ad4e" : "#333";
            toast.classList.add("show");
            setTimeout(() => toast.classList.remove("show"), 4000);
        }

        // Show server-side messages (errors) if any
        <?php if ($toastMessage): ?>
            document.addEventListener('DOMContentLoaded', function(){ showToast(<?= json_encode($toastMessage) ?>, <?= json_encode($toastType) ?>); });
        <?php endif; ?>
    </script>

</body>
</html>