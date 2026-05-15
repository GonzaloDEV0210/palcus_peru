<?php
$ADMIN = dirname(__DIR__);
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$response = [
    'url_icono'     => getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png',
    'url_logo'      => getConfig('url_logo') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/v1778354037/vjypdweg16udzxoptdxz.png',
    'url_hero'         => getConfig('url_hero') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/hero-banner-mujer.jpg',
    'nombre_tienda'    => getConfig('nombre_tienda') ?: 'Palcus Peru',
    'top_announcement' => getConfig('top_announcement') ?: 'Envío gratis en compras mayores a S/200 · 100% Algodón Peruano'
];

echo json_encode($response);
exit;
