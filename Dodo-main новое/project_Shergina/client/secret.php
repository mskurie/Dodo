<?php
session_start();

// Обработка формы
$result = null;
$comment = '';
$fatbas_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = floatval($_POST['weight'] ?? 0);
    $height = floatval($_POST['height'] ?? 0);
    $age = intval($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? 'male';
    
    // Расчёт BMR по формуле Миффлина-Сан Жеора
    if ($gender === 'male') {
        $bmr = 10 * $weight + 6.25 * $height - 5 * $age + 5;
    } else {
        $bmr = 10 * $weight + 6.25 * $height - 5 * $age - 161;
    }
    
    // Коэффициент активности (средний)
    $activity_factor = 1.55;
    $daily_calories = $bmr * $activity_factor;
    
    // Количество пицц (1 пицца = 2500 ккал)
    $pizzas_per_day = $daily_calories / 2500;
    
    // Проверка на "жирабаса"
    if ($weight > 50) {
        $fatbas_messages = [
            "ЖИРАБАС! 🍕 Но мы тебя любим!",
            "ЖИРАБАС! 😄 Пицца всё равно будет вкусной!",
            "ЖИРАБАС! 🤣 Зато счастливый!",
            "ЖИРАБАС! 💪 Сила есть — ума не надо!",
            "ЖИРАБАС! 🎉 Жизнь слишком коротка для диет!",
        ];
        $fatbas_message = $fatbas_messages[array_rand($fatbas_messages)];
    }
    
    // Смешные комментарии в зависимости от результата
    if ($pizzas_per_day < 1) {
        $comments = [
            "🍕 Менее одной пиццы в день? Ты настоящий аскет!",
            "🥗 Пицца — это не про тебя... или про очень маленькую!",
            "🤏 Одна пицца растянет на несколько дней. Экономия!",
            "🧘‍♂️ Дзен-мастер пиццы: ем мало, но со вкусом!",
        ];
    } elseif ($pizzas_per_day < 2) {
        $comments = [
            "🍕 Одна пицца в день — идеальный баланс!",
            "😎 Ты в зоне комфорта пиццемана!",
            "🎯 Золотая середина: не голодаешь и не переедаешь!",
            "👌 Стабильность — признак мастерства!",
        ];
    } elseif ($pizzas_per_day < 3) {
        $comments = [
            "🍕🍕 Две пиццы? Уважаю аппетит!",
            "🔥 Настоящий ценитель пиццы!",
            "💪 Твой организм знает, что ему нужно!",
            "🎉 Жизнь хороша, когда в ней есть место второй пицце!",
        ];
    } else {
        $comments = [
            "🍕🍕🍕 ТРИ ПИЦЦЫ?! Ты легенда!",
            "🚀 Пиццевая машина запущена на полную!",
            "🏆 Чемпион мира по поглощению пиццы!",
            "🌟 Твой желудок — портал в другое измерение!",
            "🎪 Цирк шапито: человек-пиццепад!",
        ];
    }
    
    $comment = $comments[array_rand($comments)];
    
    $result = [
        'weight' => $weight,
        'height' => $height,
        'age' => $age,
        'gender' => $gender,
        'bmr' => round($bmr),
        'daily_calories' => round($daily_calories),
        'pizzas_per_day' => round($pizzas_per_day, 2)
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍕 Секретный калькулятор пиццы</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #FFFFFF; min-height: 100vh; }
        
        /* Навигация */
        .navbar { background-color: #E31E24; color: white; padding: 1rem 0; box-shadow: 0 4px 12px rgba(227,30,36,0.2); }
        .container { max-width: 800px; margin: 0 auto; padding: 0 20px; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 900; color: white; text-decoration: none; }
        .logo span { font-weight: 400; font-size: 0.9rem; margin-left: 5px; opacity: 0.9; }
        .btn-home { display: inline-block; padding: 10px 24px; background: white; color: #E31E24; border-radius: 40px; font-weight: 700; text-decoration: none; transition: all 0.3s; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-home:hover { transform: scale(1.05); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
        
        /* Герой */
        .hero { background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%); color: white; padding: 60px 0 80px; text-align: center; border-radius: 0 0 50px 50px; margin-bottom: 50px; }
        .hero h1 { font-size: 2.8rem; font-weight: 900; margin-bottom: 15px; }
        .hero-emoji { font-size: 4rem; margin-bottom: 20px; animation: bounce 2s infinite; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
        .hero p { font-size: 1.1rem; opacity: 0.95; max-width: 600px; margin: 0 auto; line-height: 1.6; }
        
        /* Форма */
        .calculator-card { background: white; border-radius: 32px; padding: 40px; box-shadow: 0 8px 32px rgba(227,30,36,0.15); border: 2px solid #E31E24; margin-bottom: 40px; }
        .card-title { font-size: 1.8rem; font-weight: 800; color: #E31E24; text-align: center; margin-bottom: 10px; }
        .card-subtitle { text-align: center; color: #757575; margin-bottom: 30px; font-size: 1rem; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-label { display: block; font-weight: 700; color: #1A1A1A; margin-bottom: 8px; font-size: 0.95rem; }
        .form-input { width: 100%; padding: 16px 20px; border: 2px solid #E0E0E0; border-radius: 16px; font-size: 1rem; font-weight: 500; transition: all 0.3s; }
        .form-input:focus { outline: none; border-color: #E31E24; box-shadow: 0 0 0 4px rgba(227,30,36,0.1); }
        .form-input::placeholder { color: #BDBDBD; }
        
        .gender-options { display: flex; gap: 15px; }
        .gender-option { flex: 1; }
        .gender-radio { display: none; }
        .gender-label { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 20px; background: #F8F8F8; border: 2px solid #E0E0E0; border-radius: 16px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .gender-radio:checked + .gender-label { background: #E31E24; color: white; border-color: #E31E24; }
        .gender-radio:focus + .gender-label { box-shadow: 0 0 0 4px rgba(227,30,36,0.2); }
        
        .btn-calculate { width: 100%; padding: 18px; background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%); color: white; border: none; border-radius: 16px; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: all 0.3s; box-shadow: 0 6px 16px rgba(227,30,36,0.3); }
        .btn-calculate:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(227,30,36,0.4); }
        .btn-calculate:active { transform: translateY(-1px); }
        
        /* Результаты */
        .result-card { background: linear-gradient(135deg, #FFF5F5 0%, #FFF 100%); border-radius: 24px; padding: 35px; border: 2px solid #E31E24; margin-bottom: 30px; animation: slideIn 0.5s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .fatbas-banner { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #1A1A1A; padding: 20px 25px; border-radius: 20px; text-align: center; margin-bottom: 25px; box-shadow: 0 4px 16px rgba(255,215,0,0.3); }
        .fatbas-text { font-size: 1.4rem; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; }
        
        .result-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .result-item { background: white; padding: 20px; border-radius: 16px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .result-icon { font-size: 2rem; margin-bottom: 10px; }
        .result-value { font-size: 1.6rem; font-weight: 900; color: #E31E24; }
        .result-label { font-size: 0.85rem; color: #757575; margin-top: 5px; text-transform: uppercase; letter-spacing: 1px; }
        
        .pizza-result { background: linear-gradient(135deg, #E31E24 0%, #C8102E 100%); color: white; padding: 30px; border-radius: 20px; text-align: center; margin-bottom: 25px; box-shadow: 0 8px 24px rgba(227,30,36,0.3); }
        .pizza-emoji { font-size: 4rem; margin-bottom: 15px; }
        .pizza-value { font-size: 3rem; font-weight: 900; line-height: 1.2; }
        .pizza-label { font-size: 1rem; opacity: 0.9; margin-top: 10px; }
        
        .comment-box { background: white; padding: 25px; border-radius: 20px; border-left: 5px solid #E31E24; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .comment-text { font-size: 1.2rem; font-weight: 600; color: #1A1A1A; line-height: 1.5; }
        
        .footer-note { text-align: center; color: #BDBDBD; font-size: 0.85rem; margin-top: 30px; }
        
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .result-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 2rem; }
            .calculator-card { padding: 25px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="logo">ДОДО<span>пицца</span></a>
            <a href="../index.php" class="btn-home">🏠 На главную</a>
        </div>
    </nav>
    
    <div class="hero">
        <div class="container">
            <div class="hero-emoji">🍕</div>
            <h1>Секретный калькулятор</h1>
            <p>Узнай, сколько пиццы ты можешь съесть за день<br>и остаться в форме! (или нет 😏)</p>
        </div>
    </div>
    
    <div class="container">
        <div class="calculator-card">
            <h2 class="card-title">🔥 Калькулятор пиццы</h2>
            <p class="card-subtitle">Введи свои данные и узнай свою пиццевую норму!</p>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">⚖️ Вес (кг)</label>
                        <input type="number" name="weight" class="form-input" placeholder="70" min="1" max="300" required value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">📏 Рост (см)</label>
                        <input type="number" name="height" class="form-input" placeholder="175" min="50" max="250" required value="<?php echo htmlspecialchars($_POST['height'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">🎂 Возраст (лет)</label>
                        <input type="number" name="age" class="form-input" placeholder="25" min="1" max="120" required value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">👤 Пол</label>
                        <div class="gender-options">
                            <div class="gender-option">
                                <input type="radio" name="gender" value="male" id="male" class="gender-radio" <?php echo ($_POST['gender'] ?? 'male') === 'male' ? 'checked' : ''; ?>>
                                <label for="male" class="gender-label">👨 Мужской</label>
                            </div>
                            <div class="gender-option">
                                <input type="radio" name="gender" value="female" id="female" class="gender-radio" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'checked' : ''; ?>>
                                <label for="female" class="gender-label">👩 Женский</label>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-calculate">🍕 Рассчитать норму пиццы!</button>
            </form>
        </div>
        
        <?php if ($result): ?>
            <div class="result-card">
                <?php if ($fatbas_message): ?>
                    <div class="fatbas-banner">
                        <div class="fatbas-text"><?php echo $fatbas_message; ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="result-grid">
                    <div class="result-item">
                        <div class="result-icon">🔥</div>
                        <div class="result-value"><?php echo $result['bmr']; ?></div>
                        <div class="result-label">Базовый обмен</div>
                    </div>
                    <div class="result-item">
                        <div class="result-icon">⚡</div>
                        <div class="result-value"><?php echo $result['daily_calories']; ?></div>
                        <div class="result-label">Ккал в день</div>
                    </div>
                    <div class="result-item">
                        <div class="result-icon">📊</div>
                        <div class="result-value"><?php echo $result['weight']; ?> кг</div>
                        <div class="result-label">Твой вес</div>
                    </div>
                </div>
                
                <div class="pizza-result">
                    <div class="pizza-emoji">🍕</div>
                    <div class="pizza-value"><?php echo $result['pizzas_per_day']; ?></div>
                    <div class="pizza-label">пиццы в день</div>
                </div>
                
                <div class="comment-box">
                    <div class="comment-text"><?php echo $comment; ?></div>
                </div>
            </div>
        <?php endif; ?>
        
        <p class="footer-note">🤫 Это секретная страница! Не рассказывай никому...</p>
    </div>
</body>
</html>
