<?php
$path = __DIR__ . '/../propietario/propiedad-panel.php';
$contents = file_get_contents($path);
$contents = mb_convert_encoding($contents, 'UTF-8', 'ISO-8859-1');
file_put_contents($path, $contents);
?>
