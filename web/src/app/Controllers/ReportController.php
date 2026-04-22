<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\SimplePdf;
use App\Repositories\EntityRepository;

final class ReportController extends BaseController
{
    public function entity(string $entity): void
    {
        $repository = new EntityRepository();
        $config = $repository->config($entity);
        $this->ensureEntityAccess($config);

        $rows = $repository->all($entity);
        $filename = 'report_' . $entity . '_' . date('Ymd_His') . '.pdf';
        $this->sendPdf($filename, $config['title'], $rows);
    }

    public function document(string $id): void
    {
        $repository = new EntityRepository();
        $config = $repository->config('documents');
        $this->ensureEntityAccess($config);

        $document = $repository->find('documents', (int) $id);
        if (!$document) {
            http_response_code(404);
            echo 'Документ не найден.';
            return;
        }

        $filename = 'document_' . (int) $id . '.pdf';
        $this->sendPdf($filename, $document['title'], [$document]);
    }

    private function sendPdf(string $filename, string $title, array $rows): void
    {
        if (class_exists('\TCPDF')) {
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetCreator('CRM / АСУ ТП');
            $pdf->SetTitle($title);
            $pdf->SetMargins(12, 12, 12);
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->Cell(0, 10, $title, 0, 1);
            $pdf->SetFont('dejavusans', '', 9);
            $html = '<table border="1" cellpadding="4"><tbody>';
            foreach ($rows as $row) {
                foreach ($row as $key => $value) {
                    $html .= '<tr><th width="35%">' . e((string) $key) . '</th><td>' . e((string) $value) . '</td></tr>';
                }
                $html .= '<tr><td colspan="2"></td></tr>';
            }
            $html .= '</tbody></table>';
            $pdf->writeHTML($html);
            $pdf->Output($filename, 'D');
            return;
        }

        $content = SimplePdf::table($title, $rows);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
    }

    private function ensureEntityAccess(array $config): void
    {
        foreach ($config['roles'] as $role) {
            if (has_role($role)) {
                return;
            }
        }

        http_response_code(403);
        echo 'Недостаточно прав.';
        exit;
    }
}
