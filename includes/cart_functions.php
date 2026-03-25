<?php
// includes/cart_functions.php

function initCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function addToCart($dish_id, $quantity = 1) {
    initCart();
    if (isset($_SESSION['cart'][$dish_id])) {
        $_SESSION['cart'][$dish_id] += $quantity;
    } else {
        $_SESSION['cart'][$dish_id] = $quantity;
    }
    return true;
}

function removeFromCart($dish_id) {
    if (isset($_SESSION['cart'][$dish_id])) {
        unset($_SESSION['cart'][$dish_id]);
        return true;
    }
    return false;
}

function updateCartQuantity($dish_id, $quantity) {
    if ($quantity <= 0) {
        return removeFromCart($dish_id);
    }
    $_SESSION['cart'][$dish_id] = $quantity;
    return true;
}

function clearCart() {
    $_SESSION['cart'] = [];
}

function getCartItems($conn) {
    initCart();
    $items = [];
    $total = 0;
    
    if (!empty($_SESSION['cart'])) {
        $ids = implode(',', array_keys($_SESSION['cart']));
        $result = $conn->query("SELECT * FROM dishes WHERE id IN ($ids) AND is_available = 1");
        
        if ($result) {
            while ($dish = $result->fetch_assoc()) {
                $quantity = $_SESSION['cart'][$dish['id']];
                $subtotal = $dish['price'] * $quantity;
                $total += $subtotal;
                
                $items[] = [
                    'id' => $dish['id'],
                    'name' => $dish['name'],
                    'price' => $dish['price'],
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'image' => $dish['image'] ?? null
                ];
            }
        }
    }
    
    return ['items' => $items, 'total' => $total];
}

function getCartCount() {
    initCart();
    return array_sum($_SESSION['cart']);
}
?>