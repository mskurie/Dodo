<?php
// includes/client_auth.php
require_once __DIR__ . '/../config/database.php';

class ClientAuth {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Регистрация нового посетителя
     */
    public function register($name, $email, $phone, $password) {
        // Проверка на существующего пользователя
        $check = $this->conn->prepare("SELECT id FROM clients WHERE email = ? OR phone = ?");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Пользователь с таким email или телефоном уже существует'];
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("
            INSERT INTO clients (full_name, email, phone, password_hash, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssss", $name, $email, $phone, $password_hash);
        
        if ($stmt->execute()) {
            $client_id = $stmt->insert_id;
            $this->loginById($client_id);
            return ['success' => true, 'message' => 'Регистрация успешна!'];
        } else {
            return ['success' => false, 'message' => 'Ошибка регистрации'];
        }
    }
    
    /**
     * Вход для посетителя
     */
    public function login($login, $password) {
        $stmt = $this->conn->prepare("
            SELECT id, full_name, email, phone, password_hash 
            FROM clients 
            WHERE email = ? OR phone = ?
        ");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($client = $result->fetch_assoc()) {
            if (password_verify($password, $client['password_hash'])) {
                $_SESSION['client_id'] = $client['id'];
                $_SESSION['client_name'] = $client['full_name'];
                $_SESSION['client_email'] = $client['email'];
                $_SESSION['client_logged_in'] = true;
                return true;
            }
        }
        return false;
    }
    
    /**
     * Вход по ID (после регистрации)
     */
    public function loginById($client_id) {
        $stmt = $this->conn->prepare("SELECT id, full_name, email FROM clients WHERE id = ?");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($client = $result->fetch_assoc()) {
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_name'] = $client['full_name'];
            $_SESSION['client_email'] = $client['email'];
            $_SESSION['client_logged_in'] = true;
            return true;
        }
        return false;
    }
    
    /**
     * Проверка авторизации
     */
    public function isLoggedIn() {
        return isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true;
    }
    
    /**
     * Получение данных текущего посетителя
     */
    public function getCurrentClient() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['client_id'],
                'name' => $_SESSION['client_name'],
                'email' => $_SESSION['client_email']
            ];
        }
        return null;
    }
    
    /**
     * Выход
     */
    public function logout() {
        unset($_SESSION['client_id']);
        unset($_SESSION['client_name']);
        unset($_SESSION['client_email']);
        unset($_SESSION['client_logged_in']);
    }
}
?>