<?php
$path = __DIR__ . '/../propietario/propiedad-panel.php';
$contents = file_get_contents($path);

$contents = preg_replace('#<title>.*</title>#', '<title><?php echo htmlspecialchars($propiedad[\'nombre\'], ENT_QUOTES, \'UTF-8\'); ?> · Panel</title>', $contents);
$contents = preg_replace('#\$propiedad\[\'direccion\'\].*?\$propiedad\[\'ciudad\'\]#', "\$propiedad['direccion'] . ' · ' . \$propiedad['ciudad']", $contents);
$contents = str_replace('<section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 property-panel-hero">', '<section class="card-bairoom shadow-sm p-4 p-md-5 rounded-4 mb-5 property-panel-hero position-relative">\n        <a href="propietario-panel.php" class="btn btn-outline-secondary btn-sm property-back">Volver al panel</a>', $contents);
$contents = preg_replace('#<span>Ocupaci.*?habitaciones</span>#', '<span>Ocupación <?php echo (int) $metrics[\'ocupacion\']; ?>% · <?php echo (int) $propiedad[\'total_habitaciones\']; ?> habitaciones</span>', $contents);
$contents = preg_replace('#Cobros al[^:]*:#', 'Cobros al día:', $contents);
$contents = preg_replace('#Pr.*?ximas salidas#', 'Próximas salidas', $contents);
$contents = preg_replace('#pr.*?ximos 30 d.*?as#', 'próximos 30 días', $contents);
$contents = preg_replace('#revisi.*?n#', 'revisión', $contents);
$contents = preg_replace('#Estado y rentabilidad individual por habitaci.*?n\.#', 'Estado y rentabilidad individual por habitación.', $contents);
$contents = preg_replace('#<th>Habitaci.*?n</th>#', '<th>Habitación</th>', $contents);

file_put_contents($path, $contents);
?>
