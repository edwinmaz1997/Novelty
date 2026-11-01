<?php
/** Datos iniciales: marcas/modelos, ajustes y vehículos de ejemplo. */

const SEED_MARCAS = [
    'Toyota'        => ['Hilux', 'Corolla', 'Yaris', 'RAV4', 'Prado', 'Land Cruiser', '4Runner', 'Fortuner', 'Tacoma', 'Tundra', 'Rush', 'Hiace', 'Camry', 'Highlander', 'Sequoia'],
    'Nissan'        => ['Frontier', 'Navara', 'Sentra', 'Versa', 'X-Trail', 'Kicks', 'Pathfinder', 'March', 'Murano', 'Patrol', 'Altima', 'Rogue'],
    'Honda'         => ['Civic', 'CR-V', 'Accord', 'HR-V', 'Fit', 'Pilot', 'Odyssey'],
    'Mazda'         => ['Mazda 2', 'Mazda 3', 'Mazda 6', 'CX-3', 'CX-30', 'CX-5', 'CX-9', 'BT-50'],
    'Mitsubishi'    => ['L200', 'Montero', 'Montero Sport', 'Outlander', 'ASX', 'Lancer', 'Mirage', 'Xpander'],
    'Hyundai'       => ['Accent', 'Elantra', 'Tucson', 'Santa Fe', 'Creta', 'Grand i10', 'Kona', 'H-1'],
    'Kia'           => ['Rio', 'Picanto', 'Sportage', 'Sorento', 'Seltos', 'Soul', 'Cerato', 'K5'],
    'Suzuki'        => ['Swift', 'Vitara', 'Grand Vitara', 'Jimny', 'Ertiga', 'S-Presso', 'Baleno'],
    'Isuzu'         => ['D-Max', 'MU-X', 'Trooper', 'Rodeo'],
    'Chevrolet'     => ['Spark', 'Aveo', 'Beat', 'Tracker', 'Trailblazer', 'Colorado', 'Silverado', 'Tahoe', 'Captiva', 'Equinox'],
    'Ford'          => ['Ranger', 'F-150', 'Explorer', 'Escape', 'Edge', 'Expedition', 'Bronco', 'Mustang'],
    'Volkswagen'    => ['Jetta', 'Golf', 'Tiguan', 'Amarok', 'Polo', 'Passat', 'Touareg'],
    'BMW'           => ['Serie 1', 'Serie 3', 'Serie 5', 'Serie 7', 'X1', 'X3', 'X5', 'X6'],
    'Mercedes-Benz' => ['Clase A', 'Clase C', 'Clase E', 'Clase S', 'GLA', 'GLC', 'GLE', 'Sprinter'],
    'Audi'          => ['A3', 'A4', 'A6', 'Q3', 'Q5', 'Q7', 'Q8'],
    'Jeep'          => ['Wrangler', 'Grand Cherokee', 'Cherokee', 'Compass', 'Renegade', 'Gladiator'],
    'Lexus'         => ['IS', 'ES', 'NX', 'RX', 'GX', 'LX'],
    'Subaru'        => ['Impreza', 'Forester', 'Outback', 'XV', 'WRX'],
    'Land Rover'    => ['Defender', 'Discovery', 'Range Rover', 'Range Rover Sport', 'Range Rover Evoque'],
    'Dodge'         => ['Ram 1500', 'Durango', 'Charger', 'Challenger', 'Journey'],
    'Porsche'       => ['Cayenne', 'Macan', '911', 'Panamera'],
    'Volvo'         => ['XC40', 'XC60', 'XC90', 'S60', 'S90'],
];

const SEED_AJUSTES = [
    'negocio_nombre' => 'Novelty Motors',
    'eslogan'        => 'Una nueva experiencia',
    'whatsapp'       => '',
    'telefono'       => '2233-9309',
    'email'          => 'hola@noveltymotors.com',
    'direccion'      => 'Plaza Comercial Punto 15, Local 12. Vista Hermosa I, Zona 15',
    'horario'        => 'Lunes a viernes de 9:00 am a 5:00 pm y sábados de 9:00 am a 12:00 pm',
    'moneda'         => 'Q',
    'comision_pct'   => '5',
    'facebook'       => 'https://www.facebook.com/noveltymotorsguatemala',
    'instagram'      => 'https://www.instagram.com/noveltymotors',
    'tiktok'         => 'https://www.tiktok.com/@noveltymotors',
];

function seed_catalogos(PDO $pdo): void
{
    $insM = $pdo->prepare('INSERT IGNORE INTO marcas (nombre, slug) VALUES (?, ?)');
    $getM = $pdo->prepare('SELECT id FROM marcas WHERE nombre = ?');
    $insMo = $pdo->prepare('INSERT IGNORE INTO modelos (marca_id, nombre) VALUES (?, ?)');
    foreach (SEED_MARCAS as $marca => $modelos) {
        $insM->execute([$marca, slugify($marca)]);
        $getM->execute([$marca]);
        $id = (int)$getM->fetchColumn();
        foreach ($modelos as $m) {
            $insMo->execute([$id, $m]);
        }
    }
}

function seed_ajustes(PDO $pdo): void
{
    $st = $pdo->prepare('INSERT IGNORE INTO ajustes (clave, valor) VALUES (?, ?)');
    foreach (SEED_AJUSTES as $k => $v) {
        $st->execute([$k, $v]);
    }
}

function seed_demo(PDO $pdo, int $usuarioId): void
{
    $demo = [
        // marca, modelo, version, año, km, proc, color, trans, comb, motor, carroceria, tracción, precio, propietario, gastos, dias, destacado, etiquetas
        ['Toyota', 'Hilux', 'SRV 2.8 Turbo Diésel', 2021, 48000, 'agencia', 'Blanco', 'automatica', 'diesel', '2.8L', 'Pick-up', '4x4', 335000, 310000, 2500, 12, 1, 'Único dueño, Servicios al día'],
        ['Toyota', 'Prado', 'TX-L', 2019, 72000, 'agencia', 'Negro', 'automatica', 'diesel', '3.0L', 'SUV', '4x4', 425000, 395000, 0, 34, 1, 'Full extras, Asientos de cuero'],
        ['Honda', 'CR-V', 'EX AWD', 2020, 41000, 'rodado', 'Rojo', 'automatica', 'gasolina', '1.5L Turbo', 'SUV', 'AWD', 189000, 172000, 3500, 21, 1, 'Sunroof, Cámara de reversa'],
        ['BMW', 'X5', 'xDrive40i M Sport', 2019, 58000, 'rodado', 'Gris', 'automatica', 'gasolina', '3.0L', 'SUV', 'AWD', 465000, 430000, 6000, 63, 1, 'Full extras, Pantalla'],
        ['Mazda', 'CX-5', 'Grand Touring', 2021, 36000, 'agencia', 'Rojo', 'automatica', 'gasolina', '2.5L', 'SUV', '4x2', 215000, 199000, 0, 8, 0, 'Único dueño'],
        ['Nissan', 'Frontier', 'LE', 2018, 96000, 'agencia', 'Plata', 'manual', 'diesel', '2.5L', 'Pick-up', '4x4', 182000, 168000, 1800, 95, 0, 'Llantas nuevas'],
        ['Hyundai', 'Tucson', 'GLS', 2020, 52000, 'rodado', 'Azul', 'automatica', 'gasolina', '2.0L', 'SUV', '4x2', 158000, 146000, 2000, 45, 0, 'Bluetooth'],
        ['Kia', 'Picanto', 'EX', 2022, 18000, 'agencia', 'Blanco', 'automatica', 'gasolina', '1.2L', 'Hatchback', '4x2', 98000, 90000, 0, 5, 0, 'Único dueño, Papeles al día'],
        ['Mercedes-Benz', 'Clase C', 'C300 AMG Line', 2018, 67000, 'rodado', 'Negro', 'automatica', 'gasolina', '2.0L Turbo', 'Sedán', '4x2', 238000, 215000, 4500, 72, 1, 'Asientos de cuero, Sunroof'],
        ['Toyota', 'Corolla', 'SE', 2019, 61000, 'rodado', 'Gris', 'automatica', 'gasolina', '1.8L', 'Sedán', '4x2', 128000, 117000, 1500, 27, 0, 'Recién importado'],
        ['Jeep', 'Wrangler', 'Rubicon', 2017, 84000, 'rodado', 'Verde', 'manual', 'gasolina', '3.6L', 'SUV', '4x4', 298000, 275000, 5000, 110, 0, 'Full extras'],
        ['Suzuki', 'Swift', 'GLX', 2021, 29000, 'agencia', 'Rojo', 'manual', 'gasolina', '1.2L', 'Hatchback', '4x2', 89000, 82000, 0, 15, 0, 'Servicios al día'],
    ];
    $idMarca = $pdo->prepare('SELECT id FROM marcas WHERE nombre = ?');
    $idModelo = $pdo->prepare('SELECT id FROM modelos WHERE marca_id = ? AND nombre = ?');
    $ins = $pdo->prepare('INSERT INTO vehiculos (marca_id, modelo_id, version, anio, kilometraje, procedencia, color, transmision,
        combustible, motor, carroceria, traccion, puertas, pasajeros, descripcion, etiquetas, precio_publicado, destacado, estado,
        tipo_ingreso, precio_propietario, gastos, fecha_ingreso, creado_por)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($demo as $d) {
        $idMarca->execute([$d[0]]);
        $ma = (int)$idMarca->fetchColumn();
        $idModelo->execute([$ma, $d[1]]);
        $mo = (int)$idModelo->fetchColumn();
        if (!$ma || !$mo) {
            continue;
        }
        $puertas = $d[10] === 'Pick-up' ? 4 : ($d[10] === 'Hatchback' ? 5 : 4);
        $pas = $d[10] === 'SUV' && in_array($d[1], ['Prado', 'X5'], true) ? 7 : 5;
        $desc = "Vehículo de ejemplo: {$d[0]} {$d[1]} {$d[2]} {$d[3]} en excelentes condiciones. Puedes editar o eliminar este registro desde el panel.";
        $ins->execute([$ma, $mo, $d[2], $d[3], $d[4], $d[5], $d[6], $d[7], $d[8], $d[9], $d[10], $d[11], $puertas, $pas, $desc, $d[17],
            $d[12], $d[16], 'disponible', 'consignacion', $d[13], $d[14], date('Y-m-d', strtotime('-' . $d[15] . ' days')), $usuarioId]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE vehiculos SET codigo = ? WHERE id = ?')->execute([codigo_vehiculo($id), $id]);
    }

    // Algunas ventas de meses anteriores para que los reportes tengan datos.
    $cli = $pdo->prepare('INSERT INTO clientes (nombre, telefono) VALUES (?, ?)');
    $vendidos = [
        // marca, modelo, año, km, precio pub, propietario, gastos, días en predio, vendido hace (días), precio final, comprador
        ['Toyota', 'RAV4', 2019, 55000, 215000, 196000, 2000, 25, 20, 210000, 'Carlos Méndez'],
        ['Nissan', 'Sentra', 2018, 70000, 99000, 89000, 1500, 40, 48, 97000, 'Ana López'],
        ['Mitsubishi', 'L200', 2020, 60000, 205000, 188000, 0, 18, 75, 201000, 'Transportes Ruiz'],
        ['Honda', 'Civic', 2019, 48000, 142000, 129000, 2500, 33, 110, 139000, 'María García'],
        ['Isuzu', 'D-Max', 2021, 39000, 239000, 220000, 0, 12, 140, 236000, 'Jorge Pérez'],
        ['Kia', 'Sportage', 2020, 51000, 168000, 154000, 1000, 52, 170, 164000, 'Lucía Hernández'],
    ];
    foreach ($vendidos as $d) {
        $idMarca->execute([$d[0]]);
        $ma = (int)$idMarca->fetchColumn();
        $idModelo->execute([$ma, $d[1]]);
        $mo = (int)$idModelo->fetchColumn();
        if (!$ma || !$mo) {
            continue;
        }
        $cli->execute([$d[10], '5555-0000']);
        $comprador = (int)$pdo->lastInsertId();
        $fv = date('Y-m-d', strtotime('-' . $d[8] . ' days'));
        $fi = date('Y-m-d', strtotime($fv . ' -' . $d[7] . ' days'));
        $ins->execute([$ma, $mo, null, $d[2], $d[3], 'agencia', 'Blanco', 'automatica', 'gasolina', null, null, null, 4, 5,
            'Vehículo de ejemplo vendido.', '', $d[4], 0, 'vendido', 'consignacion', $d[5], $d[6], $fi, $usuarioId]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE vehiculos SET codigo = ?, fecha_venta = ?, precio_venta_final = ?, comprador_id = ? WHERE id = ?')
            ->execute([codigo_vehiculo($id), $fv, $d[9], $comprador, $id]);
    }
}

