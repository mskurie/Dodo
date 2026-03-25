<?php
// config/database.php
define('DB_HOST', '134.90.167.42');
define('DB_PORT', 10306);
define('DB_USER', 'Shergina');
define('DB_PASS', '5*.peCVF9Vhe_yie');
define('DB_NAME', 'project_Shergina');
define('DB_CHARSET', 'utf8mb4');

function getConnection() {
    static $connection = null;
    
    if ($connection === null) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($connection->connect_error) {
            die("Ошибка подключения к БД: " . $connection->connect_error);
        }
        
        $connection->set_charset(DB_CHARSET);
    }
    
    return $connection;
}
?>