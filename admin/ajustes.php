<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
requiere_admin();

$campos = [
    'Negocio' => [
        'negocio_nombre' => ['Nombre del negocio', 'text', ''],
        'eslogan'        => ['Eslogan', 'text', 'Aparece en el pie de página y en buscadores'],
        'moneda'         => ['Símbolo de moneda', 'text', 'Ej. Q o $'],
        'comision_pct'   => ['Comisión de referencia (%)', 'number', 'Solo informativa en el formulario de vehículos'],
    ],
    'Contacto' => [
        'whatsapp'  => ['WhatsApp (con código de país)', 'text', 'Ej. 50255555555 — recibe los mensajes de "Me interesa"'],
        'telefono'  => ['Teléfono visible', 'text', ''],
        'email'     => ['Correo', 'email', ''],
        'direccion' => ['Dirección del predio', 'text', ''],
        'horario'   => ['Horario', 'text', ''],
    ],
    'Redes sociales' => [
        'facebook'  => ['Facebook (URL)', 'url', ''],
        'instagram' => ['Instagram (URL)', 'url', ''],
        'tiktok'    => ['TikTok (URL)', 'url', ''],
    ],
];

if (es_post()) {
    csrf_check();
    $st = db()->prepare('INSERT INTO ajustes (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
    foreach ($campos as $grupo) {
        foreach ($grupo as $k => $c) {
            $val = p($k);
            if ($k === 'whatsapp') {
                $val = preg_replace('/\D/', '', $val);
            }
            $st->execute([$k, mb_substr($val, 0, 255)]);
        }
    }
    flash('ok', 'Ajustes guardados.');
    redirect('admin/ajustes.php');
}

$a = ajustes();
admin_inicio('Ajustes', 'ajustes');
?>
<form method="post" class="max-w-3xl space-y-6">
  <?= csrf_field() ?>
  <?php foreach ($campos as $grupo => $cs): ?>
    <section class="<?= C_CARD ?> p-5 sm:p-6">
      <h2 class="font-bold"><?= e($grupo) ?></h2>
      <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <?php foreach ($cs as $k => $c): ?>
          <label class="<?= C_LABEL ?>"><?= e($c[0]) ?>
            <input type="<?= $c[1] ?>" name="<?= $k ?>" value="<?= e($a[$k] ?? '') ?>" <?= $c[1] === 'number' ? 'step="0.5" min="0" max="100"' : '' ?> class="<?= C_INPUT ?>">
            <?php if ($c[2]): ?><span class="mt-1 block text-xs font-normal text-ink-400"><?= e($c[2]) ?></span><?php endif; ?>
          </label>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
  <button class="<?= C_BTN_BRAND ?> h-12 px-8"><?= icon('check', 'w-5 h-5') ?>Guardar ajustes</button>
</form>
<?php admin_fin();
