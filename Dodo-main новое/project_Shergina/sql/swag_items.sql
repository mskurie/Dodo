-- ============================================
-- GANGSTA PIZZA - SWAG ITEMS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS `swag_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT SWAG ITEMS
-- ============================================

INSERT INTO `swag_items` (`name`, `description`, `price`, `image`, `is_available`) VALUES
('🔫 Пистолет', 'Крутой пистолет для настоящих гангста', 9999.00, 'gun.png', 1),
('🎒 Мешок для Похищений', 'Прочный мешок для особых случаев', 2999.00, 'bag.png', 1),
('🔧 Лом', 'Универсальный инструмент для любых задач', 1499.00, 'crowbar.png', 1),
('📱 Флиппер', 'Телефон для настоящих боссов', 4999.00, 'flipper.png', 1),
('⛓️ Цепь', 'Золотая цепь для стильных', 7999.00, 'chain.png', 1),
('🧢 Кепка', 'Кепка с логотипом банды', 1999.00, 'cap.png', 1),
('🔪 Нож', 'Опасный нож для защиты', 3499.00, 'knife.png', 1),
('💣 Дымовая шашка', 'Для эффектного выхода', 999.00, 'smoke.png', 1);
