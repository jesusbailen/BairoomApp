<?php
declare(strict_types=1);

function bairoom_base_url(): string
{
  $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  $dir = rtrim($dir, '/');
  $dir = preg_replace('#/propietario$#', '', $dir);
  $dir = preg_replace('#/stripe$#', '', $dir);
  $dir = preg_replace('#/app/pages/public$#', '', $dir);
  $dir = preg_replace('#/app/pages$#', '', $dir);
  return $dir;
}

function bairoom_url(string $path): string
{
  $base = bairoom_base_url();
  $path = ltrim($path, '/');
  if ($base === '') {
    return '/' . $path;
  }
  return $base . '/' . $path;
}
