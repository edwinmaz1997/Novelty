<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_once ROOT . '/app/imagen.php';
require __DIR__ . '/_layout.php';
requiere_admin();

/*
 * Tipos: text, email, url, number, textarea, color, check,
 * img => [etiqueta, 'img', ayuda, valor_original, fondo (claro|oscuro), maxAncho, maxAlto]
 */
$secciones = [
    'identidad' => ['Identidad', 'Logo, ícono y color principal del sitio y del panel.', [
        'negocio_nombre'  => ['Nombre del negocio', 'text', ''],
        'color_principal' => ['Color principal', 'color', 'Botones, acentos y fondos oscuros se generan a partir de este color.'],
        'logo'            => ['Logo (fondos claros)', 'img', 'PNG con fondo transparente. Se usa en el encabezado.', 'assets/img/logo.png', 'claro', 1200, 300],
        'logo_oscuro'     => ['Logo (fondos oscuros)', 'img', 'Versión blanca para el pie de página, panel e inicio de sesión.', 'assets/img/logo-blanco.png', 'oscuro', 1200, 300],
        'icono'           => ['Ícono', 'img', 'Versión cuadrada del logo; se usa en celulares.', 'assets/img/icono.png', 'claro', 512, 512],
        'favicon'         => ['Favicon', 'img', 'Ícono de la pestaña del navegador (PNG cuadrado, 64×64 o más).', 'assets/img/favicon.png', 'claro', 256, 256],
    ]],
    'portada' => ['Portada', 'Textos e imagen de la parte superior de la página de inicio.', [
        'hero_titulo'       => ['Título (línea 1)', 'text', ''],
        'hero_titulo_color' => ['Título (línea 2, en color)', 'text', ''],
        'hero_subtitulo'    => ['Subtítulo', 'textarea', ''],
        'hero_imagen'       => ['Imagen principal', 'img', 'Foto horizontal (1920×1080 ideal) con el carro al centro. Se muestra al lado del título con movimiento suave. Si la quitas, se usa el fondo de color.', 'assets/img/hero.jpg', 'oscuro', 2400, 1600],
        'eslogan'           => ['Eslogan', 'text', 'Aparece en el título de la pestaña y en buscadores.'],
    ]],
    'servicios' => ['Servicios', 'Los tres pasos que aparecen en la página de inicio.', [
        'servicios_titulo' => ['Título de la sección', 'text', ''],
        'servicio1_titulo' => ['01 · Título', 'text', ''],
        'servicio1_texto'  => ['01 · Texto', 'textarea', ''],
        'servicio2_titulo' => ['02 · Título', 'text', ''],
        'servicio2_texto'  => ['02 · Texto', 'textarea', ''],
        'servicio3_titulo' => ['03 · Título', 'text', ''],
        'servicio3_texto'  => ['03 · Texto', 'textarea', 'Deja un título vacío para ocultar ese paso.'],
    ]],
    'marcas' => ['Nuestras Marcas', 'Los logos se administran en Marcas y modelos.', [
        'mostrar_marcas'   => ['Mostrar la sección de marcas en el inicio', 'check', ''],
        'marcas_titulo'    => ['Título', 'text', ''],
        'marcas_subtitulo' => ['Subtítulo', 'textarea', 'También se usa como descripción en el pie de página.'],
    ]],
    'negocio' => ['Ventas', '', [
        'moneda'       => ['Símbolo de moneda', 'text', 'Ej. Q o $'],
        'comision_pct' => ['Comisión de referencia (%)', 'number', 'Solo informativa en el formulario de vehículos.'],
    ]],
    'contacto' => ['Contacto', '', [
        'whatsapp'  => ['WhatsApp (con código de país)', 'text', 'Ej. 50255555555 — recibe los mensajes de "Me interesa".'],
        'telefono'  => ['Teléfono visible', 'text', ''],
        'email'     => ['Correo', 'email', ''],
        'direccion' => ['Dirección', 'text', 'Al tocarla en el sitio se ofrece abrirla en Google Maps o Waze.'],
        'mapa_coords' => ['Ubicación exacta (opcional)', 'text', 'Latitud,longitud para llegar exacto. En Google Maps: clic derecho sobre el local → copia los números. Ej. 14.5935,-90.4844'],
        'horario'   => ['Horario', 'text', ''],
    ]],
    'redes' => ['Redes sociales', '', [
        'facebook'  => ['Facebook (URL)', 'url', ''],
        'instagram' => ['Instagram (URL)', 'url', ''],
        'tiktok'    => ['TikTok (URL)', 'url', ''],
    ]],
];

if (es_post()) {
    csrf_check();
    $st = db()->prepare('INSERT INTO ajustes (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
    $actual = ajustes();
    $errores = [];
    foreach ($secciones as $sec) {
        foreach ($sec[2] as $k => $c) {
            $tipo = $c[1];
            if ($tipo === 'img') {
                $previo = $actual[$k] ?? '';
                $accion = p($k . '__accion');
                $f = $_FILES[$k] ?? null;
                if ($f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $nuevo = imagen_guardar_sitio($f, $k, $c[5], $c[6]);
                        imagen_sitio_borrar($previo);
                        $st->execute([$k, $nuevo]);
                    } catch (RuntimeException $e) {
                        $errores[] = $c[0] . ': ' . $e->getMessage();
                    }
                } elseif ($accion === 'original' || $accion === 'quitar') {
                    imagen_sitio_borrar($previo);
                    $st->execute([$k, $accion === 'original' ? $c[3] : '']);
                }
                continue;
            }
            if ($tipo === 'check') {
                $val = p($k) === '1' ? '1' : '0';
            } elseif ($tipo === 'color') {
                $val = preg_match('/^#[0-9a-fA-F]{6}$/', p($k)) ? strtoupper(p($k)) : ($actual[$k] ?? '#005A50');
            } elseif ($k === 'whatsapp') {
                $val = preg_replace('/\D/', '', p($k));
            } else {
                $val = $tipo === 'textarea' ? mb_substr(p($k), 0, 600) : mb_substr(p($k), 0, 255);
            }
            $st->execute([$k, $val]);
        }
    }
    foreach ($errores as $er) {
        flash('error', $er);
    }
    flash('ok', 'Ajustes guardados.');
    redirect('admin/ajustes.php' . (p('_seccion') ? '#' . preg_replace('/[^a-z]/', '', p('_seccion')) : ''));
}

$a = ajustes();
admin_inicio('Ajustes del sitio', 'ajustes', [
    'acciones' => '<a href="' . url('') . '" target="_blank" class="' . C_BTN2 . '">' . icon('eye', 'w-4 h-4') . '<span class="hidden sm:inline">Ver sitio</span></a>',
]);
?>
<div class="grid gap-6 lg:grid-cols-[200px_minmax(0,1fr)]">
  <nav class="hidden lg:block">
    <div class="sticky top-24 space-y-0.5 text-sm font-semibold">
      <?php foreach ($secciones as $id => $sec): ?>
        <a href="#<?= $id ?>" class="block rounded-lg px-3 py-2 text-ink-500 hover:bg-white hover:text-ink-900"><?= e($sec[0]) ?></a>
      <?php endforeach; ?>
    </div>
  </nav>

  <form method="post" enctype="multipart/form-data" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>
    <input type="hidden" name="_seccion" id="_seccion" value="">
    <?php foreach ($secciones as $id => $sec): ?>
      <section id="<?= $id ?>" class="<?= C_CARD ?> scroll-mt-24 p-5 sm:p-6">
        <h2 class="font-bold"><?= e($sec[0]) ?></h2>
        <?php if ($sec[1]): ?><p class="mt-0.5 text-sm text-ink-500"><?= e($sec[1]) ?><?php if ($id === 'marcas'): ?> <a href="<?= url('admin/catalogos.php') ?>" class="font-semibold text-brand-600 hover:underline">Ir a marcas →</a><?php endif; ?></p><?php endif; ?>
        <div class="mt-5 grid gap-5 sm:grid-cols-2">
          <?php foreach ($sec[2] as $k => $c): $val = $a[$k] ?? ''; ?>
            <?php if ($c[1] === 'img'): $src = img_ajuste($k); ?>
              <div class="<?= in_array($k, ['icono', 'favicon'], true) ? 'sm:col-span-1' : 'sm:col-span-2' ?>">
                <span class="<?= C_LABEL ?>"><?= e($c[0]) ?></span>
                <div class="mt-1.5 flex flex-col gap-3 rounded-xl p-3 ring-1 ring-ink-200 sm:flex-row sm:items-center">
                  <div class="grid h-24 shrink-0 place-items-center rounded-lg px-4 <?= in_array($k, ['icono', 'favicon'], true) ? 'w-24' : 'sm:w-64' ?> <?= $c[4] === 'oscuro' ? 'bg-brand-950' : 'bg-ink-50' ?>" style="<?= $c[4] === 'claro' ? 'background-image:linear-gradient(45deg,#eef1f5 25%,transparent 25%,transparent 75%,#eef1f5 75%),linear-gradient(45deg,#eef1f5 25%,transparent 25%,transparent 75%,#eef1f5 75%);background-size:16px 16px;background-position:0 0,8px 8px' : '' ?>">
                    <?php if ($src): ?><img src="<?= e($src) ?>" alt="" class="<?= $k === 'hero_imagen' ? 'h-20 w-full rounded object-cover' : 'max-h-16 max-w-full object-contain' ?>" data-preview="<?= $k ?>">
                    <?php else: ?><span class="text-xs font-medium <?= $c[4] === 'oscuro' ? 'text-white/50' : 'text-ink-400' ?>" data-preview="<?= $k ?>">Sin imagen</span><?php endif; ?>
                  </div>
                  <div class="min-w-0 flex-1 space-y-2">
                    <input type="file" name="<?= $k ?>" accept="image/png,image/jpeg,image/webp" data-file="<?= $k ?>"
                           class="block w-full text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-ink-700">
                    <div class="flex flex-wrap gap-3 text-xs">
                      <?php if ($c[3] !== '' && $val !== $c[3]): ?>
                        <label class="inline-flex items-center gap-1.5 font-semibold text-ink-600"><input type="radio" name="<?= $k ?>__accion" value="original" class="text-brand-500 focus:ring-brand-500">Restaurar original</label>
                      <?php endif; ?>
                      <?php if ($val !== ''): ?>
                        <label class="inline-flex items-center gap-1.5 font-semibold text-red-600"><input type="radio" name="<?= $k ?>__accion" value="quitar" class="text-red-500 focus:ring-red-500">Quitar</label>
                      <?php endif; ?>
                    </div>
                    <?php if ($c[2]): ?><p class="text-xs text-ink-400"><?= e($c[2]) ?></p><?php endif; ?>
                  </div>
                </div>
              </div>
            <?php elseif ($c[1] === 'check'): ?>
              <label class="flex items-center gap-3 rounded-xl bg-ink-50 p-4 text-sm font-semibold sm:col-span-2">
                <input type="checkbox" name="<?= $k ?>" value="1" <?= $val === '1' ? 'checked' : '' ?> class="h-5 w-5 rounded border-ink-300 text-brand-500 focus:ring-brand-500"><?= e($c[0]) ?>
              </label>
            <?php elseif ($c[1] === 'color'): ?>
              <label class="<?= C_LABEL ?>"><?= e($c[0]) ?>
                <span class="mt-1.5 flex items-center gap-2">
                  <input type="color" name="<?= $k ?>" value="<?= e($val ?: '#005A50') ?>" id="color-<?= $k ?>" class="h-11 w-14 cursor-pointer rounded-xl border border-ink-200 bg-white p-1">
                  <input type="text" value="<?= e($val ?: '#005A50') ?>" id="color-<?= $k ?>-txt" maxlength="7" class="h-11 w-28 rounded-xl border-ink-200 font-mono text-sm uppercase focus:border-brand-500 focus:ring-brand-500">
                  <span id="color-muestra" class="flex h-11 flex-1 overflow-hidden rounded-xl ring-1 ring-ink-200"></span>
                </span>
                <span class="mt-1 block text-xs font-normal text-ink-400"><?= e($c[2]) ?></span>
              </label>
            <?php elseif ($c[1] === 'textarea'): ?>
              <label class="<?= C_LABEL ?> sm:col-span-2"><?= e($c[0]) ?>
                <textarea name="<?= $k ?>" rows="2" class="<?= C_INPUT ?>"><?= e($val) ?></textarea>
                <?php if ($c[2]): ?><span class="mt-1 block text-xs font-normal text-ink-400"><?= e($c[2]) ?></span><?php endif; ?>
              </label>
            <?php else: ?>
              <label class="<?= C_LABEL ?>"><?= e($c[0]) ?>
                <input type="<?= $c[1] ?>" name="<?= $k ?>" value="<?= e($val) ?>" <?= $c[1] === 'number' ? 'step="0.5" min="0" max="100"' : '' ?> class="<?= C_INPUT ?>">
                <?php if ($c[2]): ?><span class="mt-1 block text-xs font-normal text-ink-400"><?= e($c[2]) ?></span><?php endif; ?>
              </label>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <div class="mt-6 flex justify-end">
          <button name="guardar" value="<?= $id ?>" onclick="document.getElementById('_seccion').value='<?= $id ?>'" class="<?= C_BTN_BRAND ?>"><?= icon('check', 'w-4 h-4') ?>Guardar</button>
        </div>
      </section>
    <?php endforeach; ?>
  </form>
</div>

<script>
(function () {
  // Vista previa de imágenes
  document.querySelectorAll('[data-file]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var f = inp.files[0], k = inp.getAttribute('data-file');
      var prev = document.querySelector('[data-preview="' + k + '"]');
      if (!f || !prev) return;
      var img = prev.tagName === 'IMG' ? prev : document.createElement('img');
      img.className = k === 'hero_imagen' ? 'h-20 w-full rounded object-cover' : 'max-h-16 max-w-full object-contain';
      img.setAttribute('data-preview', k);
      img.src = URL.createObjectURL(f);
      if (prev !== img) prev.replaceWith(img);
    });
  });
  // Color: sincronizar y mostrar escala
  var c = document.getElementById('color-color_principal'), t = document.getElementById('color-color_principal-txt'), m = document.getElementById('color-muestra');
  if (!c) return;
  function mezcla(hex, p, to) {
    var r = parseInt(hex.substr(1, 2), 16), g = parseInt(hex.substr(3, 2), 16), b = parseInt(hex.substr(5, 2), 16);
    return 'rgb(' + [r, g, b].map(function (x) { return Math.round(x + (to - x) * p); }).join(',') + ')';
  }
  function pintar(hex) {
    var pasos = [[.84, 255], [.48, 255], [.24, 255], [0, 0], [.28, 0], [.6, 0], [.72, 0]];
    m.innerHTML = pasos.map(function (p) { return '<span class="flex-1" style="background:' + mezcla(hex, p[0], p[1]) + '"></span>'; }).join('');
  }
  c.addEventListener('input', function () { t.value = c.value.toUpperCase(); pintar(c.value); });
  t.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(t.value)) { c.value = t.value; pintar(t.value); } });
  pintar(c.value);
})();
</script>
<?php admin_fin();
