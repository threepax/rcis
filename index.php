<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$host = 'localhost';
$dbname = 'u3100249_data';
$username = 'u3100249_admin';
$password = 'kotgamer547';

$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'login') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "SELECT u.id, u.username, r.name AS role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.password = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            echo json_encode(['success' => true, 'message' => 'Авторизация успешна']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверный логин или пароль']);
        }
        exit;
    } elseif ($action === 'register') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином или email уже существует']);
            mysqli_stmt_close($stmt);
            exit;
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "SELECT id FROM roles WHERE name = 'user'");
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $role = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$role) {
            echo json_encode(['success' => false, 'message' => 'Ошибка: роль пользователя не найдена']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $password, $role['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Регистрация успешна. Теперь вы можете войти']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка регистрации: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
        exit;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>support sys</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto+Serif:300,400,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <div class="login-title">support sys</div>
        <div class="login-tabs">
            <button class="tab active" data-tab="login">Вход</button>
            <button class="tab" data-tab="register">Регистрация</button>
            <div class="tab-slider"></div>
        </div>
        <div class="tab-content">
            <form id="login-form" class="login-form form-login active">
                <input type="text" name="username" placeholder="Логин" class="login-input" required>
                <input type="password" name="password" placeholder="Пароль" class="login-input" required>
                <button type="submit" class="login-btn">Войти</button>
            </form>
            <form id="register-form" class="login-form form-register">
                <input type="text" name="username" placeholder="Логин" class="login-input" required>
                <input type="email" name="email" placeholder="E-mail" class="login-input" required>
                <input type="password" name="password" placeholder="Пароль" class="login-input" required>
                <button type="submit" class="login-btn">Зарегистрироваться</button>
            </form>
        </div>
    </div>
    <script src="script.js"></script>
    <footer class="footer">2025 codding fest</footer>
</body>
</html>
<?php mysqli_close($conn); ?>
