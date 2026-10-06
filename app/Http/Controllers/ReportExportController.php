<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Support\ReportData;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    private const FILTER_KEYS = ['preset', 'startDate', 'endDate', 'cashier_id', 'payment_method', 'category_id', 'product_id'];

    public function __invoke(Request $request, string $report, string $format)
    {
        $user = $request->user();

        abort_unless($user->is_active, 403);

        // Omset kasir boleh dibuka semua role (datanya otomatis dibatasi ke miliknya sendiri).
        abort_unless($report === 'cashiers' || $user->isManager(), 403);

        $request->validate([
            'preset' => ['nullable', 'in:today,week,month,custom'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date'],
            'cashier_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'in:tunai,transfer,qris'],
            'category_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
        ]);

        $filters = $request->only(self::FILTER_KEYS);

        $data = match ($report) {
            'sales' => ReportData::sales($filters),
            'cashiers' => ReportData::cashiers($filters),
            'profit-loss' => ReportData::profitLoss($filters),
            'stock' => ReportData::stock(),
        };

        $filename = $report . '-' . now()->format('Ymd-His');

        if ($format === 'xlsx') {
            return Excel::download(new ArrayExport($data), $filename . '.xlsx');
        }

        abort_if(
            count($data['rows']) > 1000,
            422,
            'Data terlalu banyak untuk PDF (maksimal 1.000 baris). Persempit periode atau gunakan Excel.'
        );

        return $this->pdf($data, $filename);
    }

    private function pdf(array $data, string $filename)
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.pdf', $data)->render());
        $dompdf->setPaper('A4', $data['orientation'] ?? 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }
}