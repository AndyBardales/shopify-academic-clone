<?php
/**
 * ShopCMS - Punto de Entrada Directo al Panel de Administración
 * Permite acceder a /admin/ directamente sin depender de reglas complejas del servidor web
 */

// Si no viene ninguna subruta definida, apuntar a 'admin'
$route = $_GET['route'] ?? '';
if (empty($route) || $route === 'admin') {
    $_GET['route'] = 'admin';
} else {
    // Si viene una subruta relativa dentro de /admin/ (ej: /admin/products)
    $_GET['route'] = 'admin/' . ltrim($route, '/');
}

// Cargar el núcleo de ShopCMS
require_once dirname(__DIR__) . '/index.php';
