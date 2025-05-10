<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$host = 'localhost';
$dbname = 'u3100249_data';
$username = 'u3100249_admin';
$password = 'kotgamer547';

$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

$stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$username = $user['username'];
mysqli_stmt_close($stmt);

$requests = [];
$status_map = [
    'pending' => 'Ожидает',
    'in_progress' => 'В работе',
    'resolved' => 'Решена',
    'closed' => 'Закрыта'
];

if ($role === 'user') {
    $stmt = mysqli_prepare($conn, "SELECT id, title, description, status, created_at FROM requests WHERE user_id = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['status_display'] = $status_map[$row['status']] ?? $row['status'];
        $requests[] = $row;
    }
    mysqli_stmt_close($stmt);
} elseif ($role === 'admin') {
    $query = "SELECT r.id, r.title, r.description, r.status, r.created_at, u.username 
              FROM requests r 
              JOIN users u ON r.user_id = u.id 
              ORDER BY r.created_at DESC";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['status_display'] = $status_map[$row['status']] ?? $row['status'];
        $requests[] = $row;
    }
}

if ($role === 'admin' && isset($_GET['search_user'])) {
    $search = '%' . mysqli_real_escape_string($conn, $_GET['search_user']) . '%';
    $users = [];
    $stmt = mysqli_prepare($conn, "SELECT id, username, blocked FROM users WHERE username LIKE ?");
    mysqli_stmt_bind_param($stmt, "s", $search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    echo json_encode($users);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'create_request' && $role === 'user') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        if (!empty($title) && !empty($description)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO requests (user_id, title, description, status) VALUES (?, ?, ?, 'pending')");
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $title, $description);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'message' => 'Заявка создана']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка создания заявки']);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
        }
        exit;
    }

    if ($action === 'delete_request' && ($role === 'user' || $role === 'admin')) {
        $request_id = $_POST['request_id'];
        if ($role === 'user') {
            $stmt = mysqli_prepare($conn, "DELETE FROM requests WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $request_id, $user_id);
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM requests WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $request_id);
        }
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка удаления']);
        }
        mysqli_stmt_close($stmt);
        exit;
    }

    if ($action === 'update_status' && $role === 'admin') {
        $request_id = $_POST['request_id'];
        $new_status = $_POST['new_status'];
        $stmt = mysqli_prepare($conn, "UPDATE requests SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $request_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'display_status' => $status_map[$new_status] ?? $new_status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка обновления статуса']);
        }
        mysqli_stmt_close($stmt);
        exit;
    }

    if ($action === 'update_user' && $role === 'admin') {
        $user_id = $_POST['user_id'];
        $new_username = trim($_POST['username']);
        $blocked = isset($_POST['blocked']) ? 1 : 0;
        $query = "UPDATE users SET username = ?, blocked = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sii", $new_username, $blocked, $user_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($action === 'change_password' && $role === 'admin') {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['password'];
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $new_password, $user_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => $success]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>support sys</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto+Serif:300,400,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <div class="header-user">
                <span class="profile-username"><?php echo $role === 'admin' ? 'Админ' : htmlspecialchars($username); ?></span>
                <button class="logout-btn" title="Выйти" onclick="window.location.href='logout.php'">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 17L21 12L16 7" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12H9" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 19C7.58172 19 4 15.4183 4 11C4 6.58172 7.58172 3 12 3" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="header-title">support sys</div>
            <div class="header-empty"></div>
        </div>
    </header>
    <main class="dashboard-main">
        <div class="dashboard-wrapper dashboard-two-cols">
            <div class="dashboard-col dashboard-col-center">
                <div class="col-title col-title-top"><?php echo $role === 'admin' ? 'Все заявки' : 'История заявок'; ?></div>
                <div class="tickets-block">
                    <?php if (empty($requests)): ?>
                        <div class="ticket-item no-tickets">Нет заявок</div>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="ticket-item">
                                <div class="ticket-title"><?php echo htmlspecialchars($request['title']); ?></div>
                                <div class="ticket-desc"><?php echo htmlspecialchars($request['description']); ?></div>
                                <span class="ticket-status status-<?php echo $request['status']; ?>">
                                    <?php echo htmlspecialchars($request['status_display']); ?>
                                </span>
                                <span class="ticket-date"><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></span>
                                <?php if ($role === 'admin'): ?>
                                    <span class="ticket-user">Пользователь: <?php echo htmlspecialchars($request['username']); ?></span>
                                    <select class="status-select" data-id="<?php echo $request['id']; ?>">
                                        <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Ожидает</option>
                                        <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                                        <option value="resolved" <?php echo $request['status'] === 'resolved' ? 'selected' : ''; ?>>Решена</option>
                                        <option value="closed" <?php echo $request['status'] === 'closed' ? 'selected' : ''; ?>>Закрыта</option>
                                    </select>
                                    <button class="delete-btn admin-delete" data-id="<?php echo $request['id']; ?>">Удалить</button>
                                <?php else: ?>
                                    <button class="delete-btn" data-id="<?php echo $request['id']; ?>">Удалить</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($role === 'user'): ?>
                <div class="dashboard-col dashboard-col-right">
                    <div class="col-title col-title-top">Новая заявка</div>
                    <form class="form-block" id="create-request-form">
                        <input type="hidden" name="action" value="create_request">
                        <div class="form-group">
                            <input id="ticket-title" type="text" name="title" class="animated-input" placeholder=" " required>
                            <label for="ticket-title">Заголовок</label>
                        </div>
                        <div class="form-group">
                            <textarea id="ticket-desc" name="description" class="animated-input" placeholder=" " required></textarea>
                            <label for="ticket-desc">Описание</label>
                        </div>
                        <button type="submit" class="submit-btn">Отправить</button>
                    </form>
                </div>
            <?php elseif ($role === 'admin'): ?>
                <div class="dashboard-col dashboard-col-right">
                    <div class="col-title col-title-top">Пользователи</div>
                    <div class="form-block" id="admin-users-block">
                        <input type="text" id="user-search-input" placeholder="Поиск по нику..." autocomplete="off">
                        <div id="user-autocomplete-list"></div>
                        <div id="user-details-block"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="notification-container"></div>
    </main>
    <footer class="footer">2025 codding fest</footer>
    <script>
        function showNotification(message, type) {
            const container = document.querySelector('.notification-container');
            const existing = container.querySelector('.notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            container.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function setLoading(element, isLoading) {
            if (isLoading) {
                element.disabled = true;
                element.classList.add('loading');
            } else {
                element.disabled = false;
                element.classList.remove('loading');
            }
        }

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const requestId = button.getAttribute('data-id');
                if (confirm('Удалить заявку?')) {
                    setLoading(button, true);
                    try {
                        const response = await fetch('dashboard.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=delete_request&request_id=${requestId}`
                        });
                        const result = await response.json();
                        if (result.success) {
                            const ticketItem = button.closest('.ticket-item');
                            ticketItem.classList.add('fade-out');
                            setTimeout(() => {
                                ticketItem.remove();
                                if (document.querySelectorAll('.ticket-item').length === 0) {
                                    const ticketsBlock = document.querySelector('.tickets-block');
                                    ticketsBlock.innerHTML = '<div class="ticket-item no-tickets">Нет заявок</div>';
                                }
                            }, 300);
                            showNotification('Заявка удалена', 'success');
                        } else {
                            showNotification(result.message || 'Ошибка удаления', 'error');
                        }
                    } catch (error) {
                        console.error('Error deleting request:', error);
                        showNotification('Произошла ошибка при удалении заявки', 'error');
                    } finally {
                        setLoading(button, false);
                    }
                }
            });
        });

        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', async () => {
                const requestId = select.getAttribute('data-id');
                const newStatus = select.value;
                const ticketItem = select.closest('.ticket-item');
                const statusSpan = ticketItem.querySelector('.ticket-status');
                const originalStatus = statusSpan.className.match(/status-(\w+)/)[1];
                setLoading(select, true);
                try {
                    const response = await fetch('dashboard.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=update_status&request_id=${requestId}&new_status=${newStatus}`
                    });
                    const result = await response.json();
                    if (result.success) {
                        statusSpan.textContent = result.display_status;
                        statusSpan.className = `ticket-status status-${newStatus}`;
                        showNotification('Статус обновлен', 'success');
                    } else {
                        showNotification(result.message || 'Ошибка обновления статуса', 'error');
                        select.value = originalStatus;
                    }
                } catch (error) {
                    console.error('Error updating status:', error);
                    showNotification('Произошла ошибка при обновлении статуса', 'error');
                    select.value = originalStatus;
                } finally {
                    setLoading(select, false);
                }
            });
        });

        const createForm = document.getElementById('create-request-form');
        if (createForm) {
            createForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitButton = createForm.querySelector('.submit-btn');
                const titleInput = createForm.querySelector('#ticket-title');
                const descInput = createForm.querySelector('#ticket-desc');

                if (!titleInput.value.trim() || !descInput.value.trim()) {
                    showNotification('Пожалуйста, заполните все поля', 'error');
                    return;
                }

                setLoading(submitButton, true);
                try {
                    const formData = new FormData(createForm);
                    const response = await fetch('dashboard.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        titleInput.value = '';
                        descInput.value = '';
                        titleInput.dispatchEvent(new Event('input'));
                        descInput.dispatchEvent(new Event('input'));
                        showNotification(result.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification(result.message || 'Ошибка создания заявки', 'error');
                    }
                } catch (error) {
                    console.error('Error creating request:', error);
                    showNotification('Произошла ошибка при создании заявки', 'error');
                } finally {
                    setLoading(submitButton, false);
                }
            });
        }

        document.querySelectorAll('.ticket-item').forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            setTimeout(() => {
                item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 100);
        });

        const userSearchInput = document.getElementById('user-search-input');
        const userAutocompleteList = document.getElementById('user-autocomplete-list');
        const userDetailsBlock = document.getElementById('user-details-block');
        
        if (userSearchInput) {
            userSearchInput.addEventListener('input', async function() {
                const query = userSearchInput.value.trim();
                userAutocompleteList.innerHTML = '';
                if (!query) return;
                
                try {
                    const resp = await fetch('dashboard.php?search_user=' + encodeURIComponent(query));
                    const users = await resp.json();
                    
                    if (users.length === 0) {
                        userAutocompleteList.innerHTML = '<div style="color:#bbb;padding:6px 0;">Не найдено</div>';
                        return;
                    }
                    
                    const list = document.createElement('div');
                    list.className = 'autocomplete-list';
                    
                    users.forEach(user => {
                        const item = document.createElement('div');
                        item.textContent = user.username + (user.blocked ? ' (заблокирован)' : '');
                        item.className = 'autocomplete-item' + (user.blocked ? ' blocked' : '');
                        item.addEventListener('mousedown', e => {
                            e.preventDefault();
                            userSearchInput.value = user.username;
                            userAutocompleteList.innerHTML = '';
                            showUserDetails(user);
                        });
                        list.appendChild(item);
                    });
                    
                    userAutocompleteList.appendChild(list);
                } catch (error) {
                    console.error('Ошибка поиска:', error);
                    userAutocompleteList.innerHTML = '<div style="color:#ff3333;padding:6px 0;">Ошибка поиска</div>';
                }
            });
            
            userSearchInput.addEventListener('blur', function() {
                setTimeout(() => { userAutocompleteList.innerHTML = ''; }, 150);
            });
        }
        
        async function showUserDetails(user) {
            userDetailsBlock.innerHTML = '';
            const form = document.createElement('form');
            form.className = 'admin-user-form';
            form.innerHTML = `
                <div class="admin-user-title">Пользователь: <b>${user.username}</b></div>
                <input type="password" name="password" class="admin-password-input" placeholder="Новый пароль">
                <div class="button-container">
                    <button type="button" class="submit-btn admin-change-password">Сменить пароль</button>
                    <button type="button" class="submit-btn admin-block-user${user.blocked ? ' blocked' : ''}">
                        ${user.blocked ? 'Разблокировать' : 'Заблокировать'}
                    </button>
                </div>
            `;
            userDetailsBlock.appendChild(form);
            
            form.querySelector('.admin-change-password').onclick = async function() {
                const password = form.querySelector('input[name="password"]').value;
                if (!password) { 
                    showNotification('Введите новый пароль', 'error'); 
                    return; 
                }
                
                const formData = new URLSearchParams();
                formData.append('action', 'change_password');
                formData.append('user_id', user.id);
                formData.append('password', password);
                
                this.disabled = true;
                try {
                    const resp = await fetch('dashboard.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
                        body: formData
                    });
                    const data = await resp.json();
                    showNotification(data.success ? 'Пароль изменён' : 'Ошибка', data.success ? 'success' : 'error');
                    if (data.success) form.querySelector('input[name="password"]').value = '';
                } catch (error) {
                    console.error('Ошибка смены пароля:', error);
                    showNotification('Ошибка смены пароля', 'error');
                } finally {
                    this.disabled = false;
                }
            };
            
            form.querySelector('.admin-block-user').onclick = async function() {
                const formData = new URLSearchParams();
                formData.append('action', 'update_user');
                formData.append('user_id', user.id);
                formData.append('username', user.username);
                formData.append('blocked', user.blocked ? 0 : 1);
                
                this.disabled = true;
                try {
                    const resp = await fetch('dashboard.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
                        body: formData
                    });
                    const data = await resp.json();
                    
                    if (data.success) {
                        user.blocked = user.blocked ? 0 : 1;
                        this.textContent = user.blocked ? 'Разблокировать' : 'Заблокировать';
                        this.classList.toggle('blocked');
                        showNotification(user.blocked ? 'Пользователь заблокирован' : 'Пользователь разблокирован', 'success');
                    } else {
                        showNotification('Ошибка обновления', 'error');
                    }
                } catch (error) {
                    console.error('Ошибка обновления:', error);
                    showNotification('Ошибка обновления', 'error');
                } finally {
                    this.disabled = false;
                }
            };
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>