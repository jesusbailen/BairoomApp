<?php
// Minimal PDF generator with simple styling (no images).
final class BairoomSimplePDF
{
  private array $lines = [];
  private string $title = '';
  private string $subtitle = '';

  public function setHeader(string $title, string $subtitle = ''): void
  {
    $this->title = $title;
    $this->subtitle = $subtitle;
  }

  public function addLine(string $text, int $size = 12, string $color = 'dark'): void
  {
    $this->lines[] = ['text' => $text, 'size' => $size, 'color' => $color];
  }

  public function addSpacer(int $lines = 1): void
  {
    for ($i = 0; $i < $lines; $i++) {
      $this->lines[] = ['text' => '', 'size' => 12, 'color' => 'dark'];
    }
  }

  public function output(string $filename = 'documento.pdf'): void
  {
    $y = 760;
    $leading = 18;
    $content = '';

    // Header bar (Bairoom blue)
    $content .= "0.17 0.60 0.93 rg 0 800 595 42 re f\n0 0 0 rg \n";
    $content .= "1 1 1 rg BT /F1 16 Tf 24 812 Td (" . $this->encodeText($this->title) . ") Tj ET\n";
    if ($this->subtitle !== '') {
      $content .= "0.12 0.34 0.58 rg BT /F1 11 Tf 24 790 Td (" . $this->encodeText($this->subtitle) . ") Tj ET\n";
      $y = 740;
    }

    foreach ($this->lines as $line) {
      $text = $this->encodeText($line['text']);
      $size = (int) $line['size'];
      $color = $this->colorToRgb($line['color']);
      $content .= sprintf("%s rg BT /F1 %d Tf 40 %d Td (%s) Tj ET\n", $color, $size, $y, $text);
      $y -= $leading;
    }

    $objects = [];
    $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj";
    $objects[] = "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj";
    $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 5 0 R /Resources << /Font << /F1 4 0 R >> >> >>endobj";
    $objects[] = "4 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>endobj";
    $stream = "<< /Length " . strlen($content) . " >>stream\n" . $content . "endstream";
    $objects[] = "5 0 obj" . $stream . "endobj";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
      $offsets[] = strlen($pdf);
      $pdf .= $obj . "\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
      $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref . "\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
  }

  private function colorToRgb(string $color): string
  {
    switch ($color) {
      case 'muted':
        return '0.35 0.43 0.48';
      case 'accent':
        return '0.13 0.46 0.88';
      case 'success':
        return '0.11 0.55 0.33';
      default:
        return '0 0 0';
    }
  }

  private function encodeText(string $text): string
  {
    $normalized = $this->normalizeUtf8($text);
    if (function_exists('mb_convert_encoding')) {
      $encoded = mb_convert_encoding($normalized, 'Windows-1252', 'UTF-8');
    } else {
      $encoded = iconv('UTF-8', 'CP1252//IGNORE', $normalized);
    }
    if ($encoded === false || $encoded === '') {
      $encoded = preg_replace('/[^\\x20-\\x7E]/', '', $normalized);
      $encoded = $encoded ?? '';
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
  }

  private function normalizeUtf8(string $text): string
  {
    $normalized = $text;
    if (!preg_match('//u', $normalized)) {
      $normalized = function_exists('mb_convert_encoding')
        ? mb_convert_encoding($normalized, 'UTF-8', 'ISO-8859-1')
        : utf8_encode($normalized);
    }
    if (preg_match('/Ã.|Â.|â.|Ø|Æ/', $normalized)) {
      $normalized = function_exists('mb_convert_encoding')
        ? mb_convert_encoding($normalized, 'UTF-8', 'Windows-1252')
        : utf8_encode(utf8_decode($normalized));
    }
    $normalized = strtr($normalized, [
      'Ø' => 'é',
      'Æ' => 'á',
      'Ð' => 'í',
      'Þ' => 'ó',
      'ß' => 'ú',
    ]);
    return $normalized;
  }
}

