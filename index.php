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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $response['message'] = 'Пожалуйста, заполните все поля';
        } else {
            if ($username === 'admin' && $password === 'admin') {
                $_SESSION['user'] = $username;
                $response['success'] = true;
                $response['message'] = 'Успешный вход';
            } else {
                $response['message'] = 'Неверное имя пользователя или пароль';
            }
        }
    } elseif ($action === 'register') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($password) || empty($confirmPassword)) {
            $response['message'] = 'Пожалуйста, заполните все поля';
        } elseif ($password !== $confirmPassword) {
            $response['message'] = 'Пароли не совпадают';
        } else {
            $response['success'] = true;
            $response['message'] = 'Регистрация успешна';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">ВХОД</h1>
        <div class="login-tabs">
            <button class="tab" data-tab="login">Вход</button>
            <button class="tab" data-tab="register">Регистрация</button>
            <div class="tab-slider"></div>
        </div>
        <div class="tab-content">
            <form id="login-form" class="login-form form-login active">
                <input type="text" class="login-input" name="username" placeholder="Имя пользователя" required>
                <input type="password" class="login-input" name="password" placeholder="Пароль" required>
                <button type="submit" class="login-btn">Войти</button>
            </form>
            <form id="register-form" class="login-form form-register">
                <input type="text" class="login-input" name="username" placeholder="Имя пользователя" required>
                <input type="password" class="login-input" name="password" placeholder="Пароль" required>
                <input type="password" class="login-input" name="confirm_password" placeholder="Подтвердите пароль" required>
                <button type="submit" class="login-btn">Зарегистрироваться</button>
            </form>
        </div>
    </div>
    <footer class="footer">
        <p>© 2024 Все права защищены</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
