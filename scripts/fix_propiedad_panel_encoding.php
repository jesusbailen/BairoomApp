<?php
$path = __DIR__ . '/../propietario/propiedad-panel.php';
$contents = file_get_contents($path);
$map = [
  "�" => "�",
  "Á" => "�",
  "á" => "�",
  "é" => "�",
  "í" => "�",
  "ó" => "�",
  "ú" => "�",
  "ñ" => "�",
  "Ó" => "�",
  "É" => "�",
  "Í" => "�",
  "Ú" => "�",
  "Ñ" => "�",
  "ü" => "�",
  "Ü" => "�"
];
foreach ($map as $bad => $good) {
  $contents = str_replace($bad, $good, $contents);
}
$contents = preg_replace('/\s+�tico\s+�\s+/', ' � ', $contents);
file_put_contents($path, $contents);
?>
