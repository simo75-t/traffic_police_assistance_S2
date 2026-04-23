<?php

namespace App\Http\Services\PoliceOfficer;

use App\Models\Violation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ViolationPdfService
{
    public function generateAndStore(Violation $violation, string $officerName = '-'): string
    {
        $violation->loadMissing(['vehicle', 'violationType', 'violationLocation.city', 'violationLocation.area']);

        $pdf = Pdf::loadView('pdf.violation', [
            'violation' => $violation,
            'officerName' => trim($officerName) !== '' ? $officerName : '-',
        ])->setPaper('a4', 'landscape');

        $path = 'violation_pdfs/violation_' . $violation->id . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function pdfUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
