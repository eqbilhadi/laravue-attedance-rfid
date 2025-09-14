<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LateReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected $search;
    protected $startDate;
    protected $endDate;
    private $dataCount;

    public function __construct($search, $startDate, $endDate)
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Attendance::query()
            ->with(['user:id,name', 'workSchedule.days.time'])
            ->where('status', 'Late')
            ->latest('date');

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        $collection = $query->get();
        $this->dataCount = $collection->count();

        return $collection;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Employee Name',
            'Date',
            'Check-in Time',
            'Scheduled Time',
            'Late Duration (mins)',
        ];
    }

    /**
     * @param mixed $attendance
     * @return array
     */
    public function map($attendance): array
    {
        return [
            $attendance->user->name,
            $attendance->date->format('d M Y'),
            $attendance->clock_in->format('H:i'),
            $this->getScheduledStartTime($attendance), // <-- Memanggil fungsi helper
            $attendance->late_minutes,
        ];
    }

    /**
     * Fungsi helper versi PHP untuk mendapatkan jadwal masuk.
     */
    private function getScheduledStartTime(Attendance $attendance): ?string
    {
        if (!$attendance->workSchedule || !$attendance->workSchedule->days) {
            return null;
        }

        $date = Carbon::parse($attendance->date);
        $dayOfWeek = $date->dayOfWeekIso; // Senin = 1, Minggu = 7

        $workScheduleDay = $attendance->workSchedule->days->firstWhere('day_of_week', $dayOfWeek);

        return $workScheduleDay?->time?->start_time;
    }

    /**
     * Menentukan nama worksheet.
     * @return string
     */
    public function title(): string
    {
        return 'Late Attendance Report';
    }

    /**
     * Mendaftarkan event untuk manipulasi sheet setelah dibuat.
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insert rows for letterhead
                $sheet->insertNewRowBefore(1, 7);

                // Letterhead
                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->mergeCells('A3:E3');
                $sheet->setCellValue('A1', 'COMPANY NAME');
                $sheet->setCellValue('A2', 'Address: Your Company Address Here');
                $sheet->setCellValue('A3', 'Phone: (123) 456-7890 | Email: info@company.com');

                // Report Title & Period
                $sheet->mergeCells('A5:E5');
                $sheet->mergeCells('A6:E6');

                $period = "All Time";
                if ($this->startDate && $this->endDate) {
                    $period = Carbon::parse($this->startDate)->format('d M Y') . ' - ' . Carbon::parse($this->endDate)->format('d M Y');
                }

                $sheet->setCellValue('A5', 'LATE ATTENDANCE REPORT');
                $sheet->setCellValue('A6', "Period: {$period}");

                // Style the letterhead
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A2:A3')->getFont()->setSize(10);
                $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('center');

                // Style the title
                $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A6')->getFont()->setItalic(true);
                $sheet->getStyle('A5:A6')->getAlignment()->setHorizontal('center');

                // Add borders to the table
                $lastRow = $this->dataCount + 8; // 7 is the starting row of table data
                $tableRange = 'A8:E' . $lastRow;
                $sheet->getStyle($tableRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Add signature section
                $signatureRow = $lastRow + 3;
                $sheet->mergeCells('D' . $signatureRow . ':E' . $signatureRow);
                $sheet->setCellValue('D' . $signatureRow, 'Jakarta, ' . Carbon::now()->format('d F Y'));
                
                $sheet->mergeCells('D' . ($signatureRow + 1) . ':E' . ($signatureRow + 1));
                $sheet->setCellValue('D' . ($signatureRow + 1), 'Human Resource Manager');
                
                $sheet->mergeCells('D' . ($signatureRow + 5) . ':E' . ($signatureRow + 5));
                $sheet->setCellValue('D' . ($signatureRow + 5), '(__________________)');
                
                // Style the signature section
                $sheet->getStyle('D' . $signatureRow . ':D' . ($signatureRow + 5))
                    ->getAlignment()
                    ->setHorizontal('center');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Header row is now at row 7
        ];
    }
}

