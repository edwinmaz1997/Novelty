<?php
/**
 * Migraciones de base de datos. Se ejecutan solas al cargar cualquier página
 * cuando la versión guardada en ajustes.db_version es menor a DB_VERSION.
 */
const DB_VERSION = 5;

const AJUSTES_V2 = [
    'color_principal'   => '#005A50',
    'logo'              => 'assets/img/logo.png',
    'logo_oscuro'       => 'assets/img/logo-blanco.png',
    'icono'             => 'assets/img/icono.png',
    'favicon'           => 'assets/img/favicon.png',
    'hero_titulo'       => 'Una nueva',
    'hero_titulo_color' => 'experiencia.',
    'hero_subtitulo'    => 'Vehículos seminuevos de agencia e importados, con facilidades de pago. Escribe lo que buscas o filtra por marca y año.',
    'hero_imagen'       => 'assets/img/hero.jpg',
    'servicios_titulo'  => 'Te acompañamos en cada paso',
    'servicio1_titulo'  => 'Venta',
    'servicio1_texto'   => 'Apoyo en la venta de tu vehículo, toma de fotografías y vídeos para promocionarlo en nuestra página web y redes sociales.',
    'servicio2_titulo'  => 'Compra',
    'servicio2_texto'   => 'Compramos tu vehículo de acuerdo a nuestros requerimientos y a la vez, facilitarte el proceso de venta.',
    'servicio3_titulo'  => 'Consignación',
    'servicio3_texto'   => 'Contamos con el espacio y la seguridad para recibir tu vehículo.',
    'marcas_titulo'     => 'Nuestras Marcas',
    'marcas_subtitulo'  => 'Venta, consignación y compra de vehículos semi-nuevos de agencia e importados.',
    'mostrar_marcas'    => '1',
];

/** Valores de ejemplo que se reemplazan por los datos reales de Novelty Motors. */
const AJUSTES_REEMPLAZO = [
    'negocio_nombre' => ['Novelty Autos', 'Novelty Motors'],
    'eslogan'        => ['Vehículos seminuevos con confianza', 'Una nueva experiencia'],
    'telefono'       => ['+502 0000-0000', '2233-9309'],
    'email'          => ['', 'hola@noveltymotors.com'],
    'direccion'      => ['Guatemala', 'Plaza Comercial Punto 15, Local 12. Vista Hermosa I, Zona 15'],
    'horario'        => ['Lunes a sábado · 8:00 a 18:00', 'Lunes a viernes de 9:00 am a 5:00 pm y sábados de 9:00 am a 12:00 pm'],
    'facebook'       => ['', 'https://www.facebook.com/noveltymotorsguatemala'],
    'instagram'      => ['', 'https://www.instagram.com/noveltymotors'],
    'tiktok'         => ['', 'https://www.tiktok.com/@noveltymotors'],
];

/** slug => [archivo, orden] */
const MARCAS_LOGOS = [
    'toyota'        => 1, 'bmw' => 2, 'mercedes-benz' => 3, 'audi' => 4, 'porsche' => 5,
    'land-rover'    => 6, 'volvo' => 7, 'jeep' => 8, 'nissan' => 9, 'chevrolet' => 10,
];

function migrar(): void
{
    $v = (int)ajuste('db_version', '1');
    if ($v >= DB_VERSION) {
        return;
    }
    $pdo = db();
    if ($v < 2) {
        migrar_v2($pdo);
    }
    if ($v < 5) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS etiquetas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(60) NOT NULL,
            popular TINYINT(1) NOT NULL DEFAULT 0,
            orden INT NOT NULL DEFAULT 0,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_etiqueta (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $ins = $pdo->prepare('INSERT IGNORE INTO etiquetas (nombre, orden) VALUES (?, ?)');
        foreach (ETIQUETAS_SUGERIDAS as $i => $t) {
            $ins->execute([$t, $i + 1]);
        }
        foreach (q_all("SELECT etiquetas FROM vehiculos WHERE etiquetas IS NOT NULL AND etiquetas <> ''") as $r) {
            foreach (etiquetas_lista($r['etiquetas']) as $t) {
                $ins->execute([$t, 100]);
            }
        }
    }
    if ($v < 4) {
        q("INSERT IGNORE INTO ajustes (clave, valor) VALUES ('mapa_coords', '')");
    }
    if ($v < 3) {
        // Imagen principal de la portada
        q("UPDATE ajustes SET valor = 'assets/img/hero.jpg' WHERE clave = 'hero_imagen' AND (valor = '' OR valor IS NULL)");
    }
    q('INSERT INTO ajustes (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', ['db_version', (string)DB_VERSION]);
    ajustes(true);
}

function migrar_v2(PDO $pdo): void
{
    // v2: logos de marcas, identidad visual y textos editables
    $cols = array_column(q_all('SHOW COLUMNS FROM marcas'), 'Field');
    if (!in_array('logo', $cols, true)) {
        $pdo->exec('ALTER TABLE marcas ADD logo VARCHAR(160) NULL, ADD en_portada TINYINT(1) NOT NULL DEFAULT 0, ADD orden INT NOT NULL DEFAULT 0');
    }
    $pdo->prepare('INSERT IGNORE INTO marcas (nombre, slug) VALUES (?, ?)')->execute(['Volvo', 'volvo']);
    $volvo = (int)q_val("SELECT id FROM marcas WHERE slug = 'volvo'");
    foreach (['XC40', 'XC60', 'XC90', 'S60', 'S90'] as $m) {
        q('INSERT IGNORE INTO modelos (marca_id, nombre) VALUES (?, ?)', [$volvo, $m]);
    }
    foreach (MARCAS_LOGOS as $slug => $orden) {
        q('UPDATE marcas SET logo = ?, en_portada = 1, orden = ? WHERE slug = ? AND logo IS NULL', ['assets/img/marcas/' . $slug . '.png', $orden, $slug]);
    }

    $ins = $pdo->prepare('INSERT IGNORE INTO ajustes (clave, valor) VALUES (?, ?)');
    foreach (AJUSTES_V2 as $k => $v) {
        $ins->execute([$k, $v]);
    }
    $up = $pdo->prepare('UPDATE ajustes SET valor = ? WHERE clave = ? AND (valor = ? OR valor IS NULL)');
    $ins2 = $pdo->prepare('INSERT IGNORE INTO ajustes (clave, valor) VALUES (?, ?)');
    foreach (AJUSTES_REEMPLAZO as $k => $par) {
        $ins2->execute([$k, $par[1]]);
        $up->execute([$par[1], $k, $par[0]]);
    }
}
