<?php
/**
 * SEO: metaetiquetas, Open Graph, datos estructurados (schema.org) y utilidades.
 */

function ciudad(): string
{
    return 'Guatemala';
}

function seo_titulo(string $t): string
{
    $n = negocio();
    return (mb_stripos($t, $n) === false) ? $t . ' | ' . $n : $t;
}

/** Imagen por defecto para compartir (portada del sitio o logo). */
function seo_imagen_defecto(): string
{
    $h = ajuste('hero_imagen');
    if ($h && is_file(ROOT . '/' . $h)) {
        return abs_url($h);
    }
    return abs_url('icon-512.png');
}

function seo_texto(string $s, int $max = 160): string
{
    $s = trim(preg_replace('/\s+/', ' ', strip_tags($s)));
    return mb_strlen($s) > $max ? rtrim(mb_substr($s, 0, $max - 1)) . '…' : $s;
}

function jsonld(array $data): string
{
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';
}

/** Negocio (AutoDealer = concesionario) para Google. */
function ld_negocio(): array
{
    $d = [
        '@context'    => 'https://schema.org',
        '@type'       => 'AutoDealer',
        '@id'         => abs_url('') . '#negocio',
        'name'        => negocio(),
        'url'         => abs_url(''),
        'description' => seo_texto(ajuste('marcas_subtitulo', 'Venta, consignación y compra de vehículos semi-nuevos de agencia e importados.'), 300),
        'image'       => seo_imagen_defecto(),
        'logo'        => abs_url(ajuste('logo', 'assets/img/logo.png')),
        'priceRange'  => '$$',
        'currenciesAccepted' => 'GTQ',
        'areaServed'  => ['@type' => 'Country', 'name' => 'Guatemala'],
    ];
    if (ajuste('telefono')) {
        $d['telephone'] = ajuste('telefono');
    }
    if (ajuste('email')) {
        $d['email'] = ajuste('email');
    }
    if (ajuste('direccion')) {
        $d['address'] = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => ajuste('direccion'),
            'addressLocality' => ciudad(),
            'addressCountry'  => 'GT',
        ];
    }
    $c = mapa_destino();
    if ($c !== '') {
        list($lat, $lng) = array_map('floatval', explode(',', $c));
        $d['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lng];
    }
    $d['hasMap'] = mapa_google();
    $redes = array_values(array_filter([ajuste('facebook'), ajuste('instagram'), ajuste('tiktok')]));
    if ($redes) {
        $d['sameAs'] = $redes;
    }
    $horas = horario_schema(ajuste('horario'));
    if ($horas) {
        $d['openingHoursSpecification'] = $horas;
    }
    return $d;
}

/** Interpreta horarios tipo "Lunes a viernes de 9:00 am a 5:00 pm y sábados de 9:00 am a 12:00 pm". */
function horario_schema(string $txt): array
{
    $t = strtr(mb_strtolower($txt, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    $t = str_replace(['a.m.', 'p.m.', 'hrs', 'h.'], ['am', 'pm', '', ''], $t);
    $dias = ['lunes' => 'Monday', 'martes' => 'Tuesday', 'miercoles' => 'Wednesday', 'jueves' => 'Thursday',
        'viernes' => 'Friday', 'sabado' => 'Saturday', 'domingo' => 'Sunday'];
    $orden = array_keys($dias);
    $out = [];
    $hora = function ($h, $m, $ap) {
        $h = (int)$h;
        if ($ap === 'pm' && $h < 12) {
            $h += 12;
        }
        if ($ap === 'am' && $h === 12) {
            $h = 0;
        }
        return sprintf('%02d:%02d', $h, (int)$m);
    };
    // "lunes a viernes de 9:00 am a 5:00 pm" / "sabados de 9:00 am a 12:00 pm"
    $re = '/(lunes|martes|miercoles|jueves|viernes|sabado|domingo)s?(?:\s+a\s+(lunes|martes|miercoles|jueves|viernes|sabado|domingo)s?)?[\s·,:\-]*(?:de\s+)?(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\s*(?:a|-)\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)?/';
    if (preg_match_all($re, $t, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $m) {
            $i = array_search($m[1], $orden, true);
            $j = $m[2] !== '' ? array_search($m[2], $orden, true) : $i;
            $lista = [];
            for ($k = $i; $k <= $j; $k++) {
                $lista[] = $dias[$orden[$k]];
            }
            $ap1 = ($m[5] ?? '') ?: ((int)$m[3] < 7 ? 'pm' : 'am');
            $ap2 = ($m[8] ?? '') ?: ((int)$m[6] < 9 ? 'pm' : 'am');
            $out[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $lista,
                'opens'     => $hora($m[3], ($m[4] ?? '') ?: 0, $ap1),
                'closes'    => $hora($m[6], ($m[7] ?? '') ?: 0, $ap2),
            ];
        }
    }
    return $out;
}

/** Sitio web + buscador interno (cuadro de búsqueda en Google). */
function ld_sitio(): array
{
    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        '@id'             => abs_url('') . '#sitio',
        'name'            => negocio(),
        'alternateName'   => array_values(array_unique([negocio(), 'Novelty', 'Novelty Motors Guatemala'])),
        'url'             => abs_url(''),
        'inLanguage'      => 'es-GT',
        'publisher'       => ['@id' => abs_url('') . '#negocio'],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => abs_url('autos.php') . '?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

/** Migas de pan. $items = [[nombre, url], ...] */
function ld_migas(array $items): array
{
    $l = [];
    foreach ($items as $i => $it) {
        $l[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $it[0], 'item' => $it[1]];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $l];
}

/** Vehículo en venta (Car + Offer). */
function ld_vehiculo(array $v, array $fotos): array
{
    $imgs = array_map(function ($f) {
        return abs_url('uploads/vehiculos/' . $f['archivo']);
    }, array_slice($fotos, 0, 10));
    $d = [
        '@context'      => 'https://schema.org',
        '@type'         => 'Car',
        'name'          => trim(vehiculo_titulo($v) . ' ' . ($v['version'] ?: '') . ' ' . $v['anio']),
        'url'           => vehiculo_url_abs($v),
        'sku'           => $v['codigo'],
        'brand'         => ['@type' => 'Brand', 'name' => $v['marca']],
        'model'         => $v['modelo'],
        'vehicleModelDate' => (string)$v['anio'],
        'modelDate'     => (string)$v['anio'],
        'mileageFromOdometer' => ['@type' => 'QuantitativeValue', 'value' => (int)$v['kilometraje'], 'unitCode' => 'KMT'],
        'itemCondition' => 'https://schema.org/UsedCondition',
        'description'   => seo_texto($v['descripcion'] ?: vehiculo_titulo($v) . ' ' . $v['anio'], 500),
        'offers'        => [
            '@type'         => 'Offer',
            'price'         => number_format(precio_actual($v), 2, '.', ''),
            'priceCurrency' => 'GTQ',
            'availability'  => $v['estado'] === 'reservado' ? 'https://schema.org/LimitedAvailability' : 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/UsedCondition',
            'seller'        => ['@id' => abs_url('') . '#negocio'],
            'url'           => vehiculo_url_abs($v),
        ],
    ];
    if ($imgs) {
        $d['image'] = $imgs;
    }
    if ($v['color']) {
        $d['color'] = $v['color'];
    }
    if ($v['carroceria']) {
        $d['bodyType'] = $v['carroceria'];
    }
    if ($v['transmision']) {
        $d['vehicleTransmission'] = TRANSMISIONES[$v['transmision']];
    }
    if ($v['combustible']) {
        $d['fuelType'] = COMBUSTIBLES[$v['combustible']];
    }
    if ($v['puertas']) {
        $d['numberOfDoors'] = (int)$v['puertas'];
    }
    if ($v['pasajeros']) {
        $d['seatingCapacity'] = (int)$v['pasajeros'];
    }
    if ($v['traccion']) {
        $d['driveWheelConfiguration'] = $v['traccion'];
    }
    if ($v['motor']) {
        $d['vehicleEngine'] = ['@type' => 'EngineSpecification', 'name' => $v['motor']];
    }
    return $d;
}

/** URL limpia de la página de una marca. */
function marca_url(string $slug): string
{
    return url('autos/marca/' . $slug . '/');
}

function vehiculo_url_abs(array $v): string
{
    return abs_url('auto/' . $v['id'] . '-' . slugify($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['anio']));
}
