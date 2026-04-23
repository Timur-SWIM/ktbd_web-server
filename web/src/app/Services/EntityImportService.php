<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EntityRepository;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

final class EntityImportService
{
    private const XLSX_MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const XLSX_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const XLSX_PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    public function __construct(private readonly EntityRepository $repository)
    {
    }

    public function parse(string $entity, ?array $file): array
    {
        $config = $this->repository->config($entity);
        if (!isset($config['import'])) {
            return $this->error('Импорт для раздела недоступен.');
        }

        $uploadError = $this->validateUpload($file);
        if ($uploadError !== null) {
            return $this->error($uploadError);
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        try {
            $parsedRows = match ($extension) {
                'txt' => $this->readTxtRows((string) $file['tmp_name']),
                'xlsx' => $this->readXlsxRows((string) $file['tmp_name']),
                default => throw new RuntimeException('Поддерживаются только файлы .txt и .xlsx.'),
            };
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage());
        }

        return $this->normalizeRows($entity, $config, $parsedRows);
    }

    private function validateUpload(?array $file): ?string
    {
        if ($file === null || !isset($file['error'])) {
            return 'Выберите файл для импорта.';
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return match ((int) $file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл превышает допустимый размер.',
                UPLOAD_ERR_PARTIAL => 'Файл был загружен не полностью.',
                UPLOAD_ERR_NO_FILE => 'Выберите файл для импорта.',
                default => 'Не удалось загрузить файл.',
            };
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['txt', 'xlsx'], true)) {
            return 'Поддерживаются только файлы .txt и .xlsx.';
        }

        if (!is_uploaded_file((string) $file['tmp_name']) && !is_file((string) $file['tmp_name'])) {
            return 'Загруженный файл недоступен.';
        }

        return null;
    }

    private function readTxtRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Не удалось прочитать TXT-файл.');
        }

        $rows = [];
        $line = 0;
        while (($cells = fgetcsv($handle, 0, ';')) !== false) {
            $line++;
            $rows[] = [
                'line' => $line,
                'cells' => array_map(static fn (mixed $cell): string => trim((string) $cell), $cells),
            ];
        }

        fclose($handle);
        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('На сервере не подключено расширение PHP zip для чтения .xlsx.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Не удалось открыть XLSX-файл.');
        }

        $sheetPath = $this->firstWorksheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Не удалось найти первый лист в XLSX-файле.');
        }

        $sharedStrings = $this->sharedStrings($zip);
        $zip->close();

        $document = $this->loadXml($sheetXml, 'Не удалось прочитать XML первого листа XLSX.');
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('main', self::XLSX_MAIN_NS);

        $rows = [];
        foreach ($xpath->query('//main:sheetData/main:row') ?: [] as $rowNode) {
            if (!($rowNode instanceof DOMElement)) {
                continue;
            }

            $line = (int) ($rowNode->getAttribute('r') ?: (count($rows) + 1));
            $cells = [];
            foreach ($xpath->query('main:c', $rowNode) ?: [] as $cellNode) {
                if (!($cellNode instanceof DOMElement)) {
                    continue;
                }

                $index = $this->cellColumnIndex($cellNode->getAttribute('r'));
                if ($index === null) {
                    $index = count($cells);
                }
                $cells[$index] = trim($this->cellValue($cellNode, $xpath, $sharedStrings));
            }

            if ($cells !== []) {
                ksort($cells);
                $rows[] = ['line' => $line, 'cells' => $cells];
            }
        }

        return $rows;
    }

    private function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = $this->loadXml($workbookXml, 'Не удалось прочитать структуру XLSX.');
        $workbookPath = new DOMXPath($workbook);
        $workbookPath->registerNamespace('main', self::XLSX_MAIN_NS);
        $workbookPath->registerNamespace('rel', self::XLSX_REL_NS);

        $sheetNodes = $workbookPath->query('/main:workbook/main:sheets/main:sheet[1]');
        $sheet = $sheetNodes === false ? null : $sheetNodes->item(0);
        if (!($sheet instanceof DOMElement)) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationId = $sheet->getAttributeNS(self::XLSX_REL_NS, 'id');
        if ($relationId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        $rels = $this->loadXml($relsXml, 'Не удалось прочитать связи XLSX.');
        $relsPath = new DOMXPath($rels);
        $relsPath->registerNamespace('rel', self::XLSX_PACKAGE_REL_NS);

        foreach ($relsPath->query('/rel:Relationships/rel:Relationship') ?: [] as $relation) {
            if (!($relation instanceof DOMElement) || $relation->getAttribute('Id') !== $relationId) {
                continue;
            }

            return $this->zipPath('xl', $relation->getAttribute('Target'));
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = $this->loadXml($xml, 'Не удалось прочитать строки XLSX.');
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('main', self::XLSX_MAIN_NS);

        $strings = [];
        foreach ($xpath->query('//main:si') ?: [] as $item) {
            $parts = [];
            foreach ($xpath->query('.//main:t', $item) ?: [] as $textNode) {
                $parts[] = $textNode->textContent;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function cellValue(DOMElement $cell, DOMXPath $xpath, array $sharedStrings): string
    {
        $type = $cell->getAttribute('t');

        if ($type === 'inlineStr') {
            $parts = [];
            foreach ($xpath->query('.//main:is//main:t', $cell) ?: [] as $textNode) {
                $parts[] = $textNode->textContent;
            }
            return implode('', $parts);
        }

        $valueNodes = $xpath->query('main:v', $cell);
        $valueNode = $valueNodes === false ? null : $valueNodes->item(0);
        $value = $valueNode?->textContent ?? '';

        if ($type === 's') {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }

        return (string) $value;
    }

    private function normalizeRows(string $entity, array $config, array $parsedRows): array
    {
        $firstRow = $this->firstNonEmptyRow($parsedRows);
        if ($firstRow === null) {
            return $this->error('Файл не содержит заголовков.');
        }

        [$headerMap, $errors] = $this->headerMap($config, $firstRow['cells']);
        if ($headerMap === []) {
            $errors[] = ['line' => $firstRow['line'], 'message' => 'Не найдено ни одного поддерживаемого заголовка.'];
        }

        if ($errors !== []) {
            return ['rows' => [], 'errors' => $errors];
        }

        $rows = [];
        foreach ($parsedRows as $parsedRow) {
            if ($parsedRow['line'] <= $firstRow['line'] || $this->isEmptyRow($parsedRow['cells'])) {
                continue;
            }

            $input = [];
            foreach ($headerMap as $index => $fieldName) {
                $value = trim((string) ($parsedRow['cells'][$index] ?? ''));
                $field = $config['fields'][$fieldName];

                if ($value !== '' && isset($field['options'])) {
                    $normalized = $this->normalizeSelectValue($field, $value);
                    if ($normalized === null) {
                        $errors[] = [
                            'line' => $parsedRow['line'],
                            'message' => $field['label'] . ': недопустимое значение "' . $value . '".',
                        ];
                        continue;
                    }
                    $value = $normalized;
                }

                $input[$fieldName] = $value;
            }

            foreach ($this->repository->validate($entity, $input) as $fieldName => $message) {
                $errors[] = [
                    'line' => $parsedRow['line'],
                    'message' => $config['fields'][$fieldName]['label'] . ': ' . $message,
                ];
            }

            $rows[] = ['line' => $parsedRow['line'], 'data' => $input];
        }

        if ($rows === []) {
            $errors[] = ['line' => null, 'message' => 'Файл не содержит строк для импорта.'];
        }

        $this->appendUniqueErrors($entity, $config, $rows, $errors);

        if ($errors !== []) {
            return ['rows' => [], 'errors' => $errors];
        }

        return [
            'rows' => array_map(static fn (array $row): array => $row['data'], $rows),
            'errors' => [],
        ];
    }

    private function headerMap(array $config, array $headers): array
    {
        $aliases = [];
        foreach ($config['fields'] as $name => $field) {
            if (($field['virtual'] ?? false) === true) {
                continue;
            }

            $aliases[$this->normalizeToken($name)] = $name;
            $aliases[$this->normalizeToken((string) $field['label'])] = $name;
        }

        $map = [];
        $used = [];
        $errors = [];

        foreach ($headers as $index => $header) {
            $header = $this->stripBom(trim((string) $header));
            if ($header === '') {
                continue;
            }

            $token = $this->normalizeToken($header);
            $fieldName = $aliases[$token] ?? null;
            if ($fieldName === null) {
                $errors[] = ['line' => null, 'message' => 'Неизвестный заголовок: ' . $header . '.'];
                continue;
            }

            if (isset($used[$fieldName])) {
                $errors[] = ['line' => null, 'message' => 'Заголовок для поля "' . $config['fields'][$fieldName]['label'] . '" указан несколько раз.'];
                continue;
            }

            $map[(int) $index] = $fieldName;
            $used[$fieldName] = true;
        }

        return [$map, $errors];
    }

    private function normalizeSelectValue(array $field, string $value): ?string
    {
        $token = $this->normalizeToken($value);
        foreach ($field['options'] as $optionValue => $optionLabel) {
            if ($token === $this->normalizeToken((string) $optionValue) || $token === $this->normalizeToken((string) $optionLabel)) {
                return (string) $optionValue;
            }
        }

        return null;
    }

    private function appendUniqueErrors(string $entity, array $config, array $rows, array &$errors): void
    {
        $uniqueField = $config['import']['unique'] ?? null;
        if (!is_string($uniqueField) || $uniqueField === '') {
            return;
        }

        $seen = [];
        $values = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row['data'][$uniqueField] ?? ''));
            if ($value === '') {
                continue;
            }

            $values[] = $value;
            if (isset($seen[$value])) {
                $errors[] = [
                    'line' => $row['line'],
                    'message' => $config['fields'][$uniqueField]['label'] . ': значение "' . $value . '" уже встречается в строке ' . $seen[$value] . '.',
                ];
                continue;
            }

            $seen[$value] = $row['line'];
        }

        $existing = array_flip($this->repository->existingValues($entity, $uniqueField, $values));
        foreach ($rows as $row) {
            $value = trim((string) ($row['data'][$uniqueField] ?? ''));
            if ($value !== '' && isset($existing[$value])) {
                $errors[] = [
                    'line' => $row['line'],
                    'message' => $config['fields'][$uniqueField]['label'] . ': значение "' . $value . '" уже существует в базе.',
                ];
            }
        }
    }

    private function firstNonEmptyRow(array $rows): ?array
    {
        foreach ($rows as $row) {
            if (!$this->isEmptyRow($row['cells'])) {
                return $row;
            }
        }

        return null;
    }

    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeToken(string $value): string
    {
        $value = $this->stripBom(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower(strtr($value, [
            'А' => 'а', 'Б' => 'б', 'В' => 'в', 'Г' => 'г', 'Д' => 'д', 'Е' => 'е', 'Ё' => 'ё',
            'Ж' => 'ж', 'З' => 'з', 'И' => 'и', 'Й' => 'й', 'К' => 'к', 'Л' => 'л', 'М' => 'м',
            'Н' => 'н', 'О' => 'о', 'П' => 'п', 'Р' => 'р', 'С' => 'с', 'Т' => 'т', 'У' => 'у',
            'Ф' => 'ф', 'Х' => 'х', 'Ц' => 'ц', 'Ч' => 'ч', 'Ш' => 'ш', 'Щ' => 'щ', 'Ъ' => 'ъ',
            'Ы' => 'ы', 'Ь' => 'ь', 'Э' => 'э', 'Ю' => 'ю', 'Я' => 'я',
        ]));
    }

    private function stripBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }

    private function cellColumnIndex(string $reference): ?int
    {
        if (!preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return null;
        }

        $index = 0;
        foreach (str_split(strtoupper($matches[1])) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function zipPath(string $baseDirectory, string $target): string
    {
        if (str_starts_with($target, '/')) {
            return ltrim($target, '/');
        }

        $parts = explode('/', trim($baseDirectory . '/' . $target, '/'));
        $normalized = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($normalized);
                continue;
            }
            $normalized[] = $part;
        }

        return implode('/', $normalized);
    }

    private function loadXml(string $xml, string $errorMessage): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $loaded = $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new RuntimeException($errorMessage);
        }

        return $document;
    }

    private function error(string $message): array
    {
        return ['rows' => [], 'errors' => [['line' => null, 'message' => $message]]];
    }
}
