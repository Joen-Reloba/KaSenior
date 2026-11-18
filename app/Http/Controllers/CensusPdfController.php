<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TCPDF;

class CensusPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $details = $request->validate([
            'chapter' => ['nullable', 'string', 'max:150'],
            'president' => ['nullable', 'string', 'max:100'],
            'chapter_address' => ['nullable', 'string', 'max:255'],
            'pensioners' => ['nullable', 'integer', 'min:0'],
            'waitlisted' => ['nullable', 'integer', 'min:0'],
            'submitted_by' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
        ]);
        $data = app(ReportController::class)->census($request)->getData();
        $data += [
            'details' => $details,
            'asOf' => today(),
            'association' => $details['chapter'] ?? 'Kalmado Senior Citizen Association - Purok 7C',
        ];
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle('Chapter Data Sheet - Census Report');
        $pdf->SetMargins(25.4, 25.4, 25.4);
        $pdf->SetAutoPageBreak(true, 25.4);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->AddPage();
        $pdf->writeHTML(view('census-pdf', $data)->render());

        return response($pdf->Output('census-report.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="census-report.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
