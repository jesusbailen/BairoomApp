<?php
$files = [__DIR__ . '/../includes/header-simple.php', __DIR__ . '/../includes/header-hero.php'];
foreach ($files as $path) {
  $contents = file_get_contents($path);
  $contents = str_replace('class="nav-link fw-bold nav-pill"', 'class="nav-link fw-bold nav-pill nav-pill--panel"', $contents);
  $contents = preg_replace('/MI PANEL/', 'MI PANEL', $contents, 1);
  file_put_contents($path, $contents);
}
?>
