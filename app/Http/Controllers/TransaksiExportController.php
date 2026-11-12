<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export transactions to Excel (.xlsx), limited to the selected date range ("Dari" – "Sampai").
 */
class TransaksiExportController extends Controller
{
    private const PAYMENT_LABELS = [
        'belum dibayar' => 'Belum Dibayar',
        'dp' => 'DP (Uang Muka)',
        'sudah dibayar' => 'Lunas',
    ];

    public function export(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isKasir(), 403, 'AKSES DITOLAK: Hanya admin dan kasir yang dapat mengekspor transaksi.');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'start_date.required' => 'Isi tanggal "Dari" terlebih dahulu sebelum export Excel.',
            'end_date.required' => 'Isi tanggal "Sampai" terlebih dahulu sebelum export Excel.',
            'end_date.after_or_equal' => 'Tanggal "Sampai" tidak boleh sebelum tanggal "Dari".',
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->endOfDay();

        // Same date column as the transaction list filter (created_at)
        $transactions = Transaksi::with(['packet.product', 'additionals'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transaksi');

        $headers = [
            'No', 'Tanggal Transaksi', 'No. Nota', 'Nama Pelanggan', 'No. HP', 'Produk', 'Paket',
            'Tanggal Sesi', 'Jam Sesi', 'Additional', 'Diskon', 'Total Harga', 'Dibayar', 'Sisa Tagihan',
            'Status Pembayaran', 'Status Pengerjaan', 'Catatan',
        ];
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));

        // Title rows
        $sheet->setCellValue('A1', 'Laporan Transaksi Yunas Studio');
        $sheet->setCellValue('A2', 'Periode: ' . $start->translatedFormat('d M Y') . ' s/d ' . $end->translatedFormat('d M Y'));
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Header row
        $headerRow = 4;
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5B73E8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $row = $headerRow + 1;
        $totals = ['total' => 0, 'paid' => 0, 'remaining' => 0];

        foreach ($transactions as $i => $trx) {
            $total = (float) $trx->total_price;
            $paid = match ($trx->status) {
                'sudah dibayar' => $total,
                'dp' => (float) $trx->dp_amount,
                default => 0,
            };
            $remaining = max(0, $total - $paid);

            $totals['total'] += $total;
            $totals['paid'] += $paid;
            $totals['remaining'] += $remaining;

            $additionals = $trx->additionals
                ->map(fn ($a) => $a->name . ' x' . ($a->pivot->quantity ?? 1))
                ->implode(', ');

            $sheet->fromArray([
                $i + 1,
                $trx->created_at?->format('d/m/Y H:i'),
                $trx->receipt_code,
                $trx->customer_name,
                $trx->phone_number,
                $trx->packet?->product?->name,
                $trx->packet?->name,
                $trx->session_date ? Carbon::parse($trx->session_date)->format('d/m/Y') : '',
                $trx->session_time ?? '',
                $additionals,
                (float) ($trx->discount ?? 0),
                $total,
                $paid,
                $remaining,
                self::PAYMENT_LABELS[$trx->status] ?? $trx->status,
                $trx->process_status,
                $trx->note,
            ], null, "A{$row}");

            // Keep phone numbers as text so leading zeros are preserved
            $sheet->setCellValueExplicit("E{$row}", (string) $trx->phone_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }

        if ($transactions->isEmpty()) {
            $sheet->setCellValue("A{$row}", 'Tidak ada transaksi pada periode ini.');
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $row++;
        }

        // Totals row
        $sheet->setCellValue("A{$row}", 'TOTAL (' . $transactions->count() . ' transaksi)');
        $sheet->mergeCells("A{$row}:K{$row}");
        $sheet->setCellValue("L{$row}", $totals['total']);
        $sheet->setCellValue("M{$row}", $totals['paid']);
        $sheet->setCellValue("N{$row}", $totals['remaining']);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF0F7']],
        ]);

        // Formatting
        $sheet->getStyle("K" . ($headerRow + 1) . ":N{$row}")
            ->getNumberFormat()->setFormatCode('"Rp" #,##0');
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$row}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D0D4E0');
        $sheet->getStyle("J" . ($headerRow + 1) . ":J{$row}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("Q" . ($headerRow + 1) . ":Q{$row}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A" . ($headerRow + 1) . ":{$lastCol}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }
        $sheet->getColumnDimension('J')->setAutoSize(false)->setWidth(40);
        $sheet->getColumnDimension('Q')->setAutoSize(false)->setWidth(40);
        $sheet->freezePane('A' . ($headerRow + 1));
        $sheet->setAutoFilter("A{$headerRow}:{$lastCol}" . max($headerRow, $row - 1));

        $filename = 'transaksi_' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
