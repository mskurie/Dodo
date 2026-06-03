<?php
// ajax/generate_math_captcha.php
session_start();
require_once '../includes/math_captcha.php';

header('Cache-Control: no-cache, must-revalidate');
header('Content-Type: application/json');

$captcha = MathCaptcha::generate();

echo json_encode([
    'question' => $captcha['question']
]);
exit;
?>