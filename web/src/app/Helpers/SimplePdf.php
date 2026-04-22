<?php

declare(strict_types=1);

namespace App\Helpers;

final class SimplePdf
{
    public static function table(string $title, array $rows): string
    {
        $lines = [$title, 'Сформировано: ' . date('Y-m-d H:i:s'), ''];

        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                $lines[] = $key . ': ' . str_replace(["\r", "\n"], ' ', (string) $value);
            }
            $lines[] = '';
        }

        return self::document(implode("\n", $lines));
    }

    private static function document(string $text): string
    {
        $text = iconv('UTF-8', 'Windows-1251//TRANSLIT', $text);
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 11 Tf 40 800 Td 14 TL ";
        foreach (explode("\n", $escaped) as $index => $line) {
            $stream .= ($index === 0 ? '' : 'T* ') . '(' . $line . ') Tj ';
        }
        $stream .= 'ET';

        $objects = [];
        $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
        $objects[] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';
        $objects[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj';
        $objects[] = '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >> endobj';
        $objects[] = '5 0 obj << /Length ' . strlen($stream) . ' >> stream' . "\n" . $stream . "\nendstream endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
