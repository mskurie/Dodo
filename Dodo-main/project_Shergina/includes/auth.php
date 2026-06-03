<?php
// includes/auth.php - Полноценная версия для продакшена
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    /**
     * Конструктор класса Auth
     * Инициализирует подключение к базе данных
     */
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Авторизация пользователя
     * @param string $username Логин или email
     * @param string $password Пароль
     * @return array ['success' => bool, 'message' => string, 'role' => string]
     */
    public function login($username, $password) {
        // Подготовка запроса для поиска пользователя по логину или email
        $stmt = $this->conn->prepare("SELECT id, username, password_hash, full_name, role, is_active FROM staff WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Проверка активности аккаунта
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Аккаунт деактивирован. Обратитесь к администратору.'];
            }
            
            // Проверка пароля
            if (password_verify($password, $user['password_hash'])) {
                // Установка сессионных переменных
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Обновление времени последнего входа
                $update = $this->conn->prepare("UPDATE staff SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();
                
                return [
                    'success' => true, 
                    'message' => 'Вход выполнен успешно', 
                    'role' => $user['role']
                ];
            }
        }
        return ['success' => false, 'message' => 'Неверный логин или пароль'];
    }
    
    /**
     * Проверка, авторизован ли пользователь
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Требование авторизации для доступа к странице
     * Если пользователь не авторизован - перенаправляет на страницу входа
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../login.php');
            exit();
        }
    }
    
    /**
     * Проверка, является ли пользователь администратором
     * @return bool
     */
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Проверка, является ли пользователь менеджером
     * @return bool
     */
    public function isManager() {
        return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager');
    }
    
    /**
     * Получение данных текущего пользователя
     * @return array|null
     */
    public function getUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
    
    /**
     * Выход из системы
     * Уничтожает сессию и перенаправляет на главную
     */
    public function logout() {
        // Очистка всех данных сессии
        $_SESSION = array();
        
        // Удаление cookie сессии
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Уничтожение сессии
        session_destroy();
        
        // Перенаправление на главную
        header('Location: ../index.php');
        exit();
    }
    
    /**
     * Регистрация нового сотрудника (только для администраторов)
     * @param string $username Логин
     * @param string $email Email
     * @param string $password Пароль
     * @param string $full_name Полное имя
     * @param string $phone Телефон
     * @return array ['success' => bool, 'message' => string]
     */
    public function register($username, $email, $password, $full_name = '', $phone = '') {
        // Проверка прав доступа
        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Недостаточно прав для регистрации новых сотрудников'];
        }
        
        // Валидация входных данных
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Заполните обязательные поля'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Некорректный email'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Пароль должен быть не менее 6 символов'];
        }
        
        // Проверка на существующего пользователя
        $check = $this->conn->prepare("SELECT id FROM staff WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Пользователь с таким логином или email уже существует'];
        }
        
        // Хеширование пароля
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'waiter'; // По умолчанию новые сотрудники получают роль официанта
        
        // Вставка нового пользователя
        $stmt = $this->conn->prepare("INSERT INTO staff (username, email, password_hash, full_name, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssss", $username, $email, $password_hash, $full_name, $phone, $role);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Сотрудник успешно зарегистрирован'];
        } else {
            return ['success' => false, 'message' => 'Ошибка регистрации: ' . $this->conn->error];
        }
    }
    
    /**
     * Получение списка всех сотрудников (только для администраторов)
     * @return array
     */
    public function getAllStaff() {
        if (!$this->isAdmin()) {
            return [];
        }
        
        $result = $this->conn->query("
            SELECT id, username, full_name, email, phone, role, is_active, created_at, last_login 
            FROM staff 
            ORDER BY role, full_name
        ");
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Изменение роли сотрудника (только для администраторов)
     * @param int $user_id ID пользователя
     * @param string $new_role Новая роль
     * @return bool
     */
    public function changeUserRole($user_id, $new_role) {
        if (!$this->isAdmin()) {
            return false;
        }
        
        $allowed_roles = ['admin', 'manager', 'waiter'];
        if (!in_array($new_role, $allowed_roles)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("UPDATE staff SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Активация/деактивация сотрудника (только для администраторов)
     * @param int $user_id ID пользователя
     * @param bool $activate Активировать или деактивировать
     * @return bool
     */
    public function setUserActive($user_id, $activate = true) {
        if (!$this->isAdmin()) {
            return false;
        }
        
        $status = $activate ? 1 : 0;
        $stmt = $this->conn->prepare("UPDATE staff SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $user_id);
        
        return $stmt->execute();
    }
}
?>