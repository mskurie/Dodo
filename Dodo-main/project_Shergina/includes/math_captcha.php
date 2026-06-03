<?php
// includes/math_captcha.php
session_start();

class MathCaptcha {
    
    public static function generate() {
        $num1 = rand(1, 20);
        $num2 = rand(1, 20);
        $operations = ['+', '-', '*'];
        $operation = $operations[array_rand($operations)];
        
        switch ($operation) {
            case '+':
                $result = $num1 + $num2;
                $symbol = '+';
                break;
            case '-':
                if ($num1 < $num2) {
                    $temp = $num1;
                    $num1 = $num2;
                    $num2 = $temp;
                }
                $result = $num1 - $num2;
                $symbol = '-';
                break;
            case '*':
                $result = $num1 * $num2;
                $symbol = '×';
                break;
            default:
                $result = $num1 + $num2;
                $symbol = '+';
        }
        
        $_SESSION['captcha_result'] = $result;
        $_SESSION['captcha_created'] = time();
        
        return [
            'question' => "$num1 $symbol $num2 = ?",
            'result' => $result
        ];
    }
    
    public static function verify($answer) {
        // Проверяем, что капча не старше 5 минут
        if (!isset($_SESSION['captcha_created']) || (time() - $_SESSION['captcha_created']) > 300) {
            return false;
        }
        
        $correct_result = $_SESSION['captcha_result'] ?? null;
        
        // Приводим оба значения к целому числу для сравнения
        $answer_int = intval($answer);
        $correct_int = intval($correct_result);
        
        return ($answer_int === $correct_int);
    }
    
    public static function clear() {
        unset($_SESSION['captcha_result']);
        unset($_SESSION['captcha_created']);
    }
}
?>