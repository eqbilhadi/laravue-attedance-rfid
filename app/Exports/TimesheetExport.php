<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class TimesheetExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected $year;
    protected $month;
    protected $search;
    protected $daysInMonth;
    protected $attendanceMatrix;
    private $dataCount;

    public function __construct($year, $month, $search)
    {
        $this->year = $year;
        $this->month = $month;
        $this->search = $search;

        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $period = CarbonPeriod::create($startDate, $endDate);
        $this->daysInMonth = collect($period);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $startDate = $this->daysInMonth->first();
        $endDate = $this->daysInMonth->last();

        $usersQuery = User::query()
            ->where('is_active', true)
            ->whereHas('userSchedules', function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                    ->where(fn($sub) => $sub->where('end_date', '>=', $startDate)->orWhereNull('end_date'));
            })
            ->when($this->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            });

        $users = $usersQuery->get();
        $this->dataCount = $users->count();

        $userIds = $users->pluck('id');

        $attendances = Attendance::whereIn('user_id', $userIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $this->attendanceMatrix = $attendances->groupBy('user_id')->map(function ($userAttendances) {
            return $userAttendances->keyBy(fn($att) => $att->date->toDateString());
        });

        return $users;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = ['Employee Name'];
        foreach ($this->daysInMonth as $day) {
            $headings[] = $day->format('d');
        }
        return $headings;
    }

    /**
     * @param mixed $user
     * @return array
     */
    public function map($user): array
    {
        $row = [$user->name];
        foreach ($this->daysInMonth as $day) {
            $dateString = $day->toDateString();
            $attendance = $this->attendanceMatrix->get($user->id)?->get($dateString);

            $row[] = $this->getStatusSymbol($attendance?->status?->value);
        }
        return $row;
    }

    /**
     * Fungsi helper untuk mengubah status menjadi simbol.
     */
    private function getStatusSymbol(?string $statusValue): string
    {
        if ($statusValue === null) {
            return '-';
        }

        return match ($statusValue) {
            'Present' => 'P',
            'Late' => 'L',
            'Absent' => 'A',
            'Holiday' => 'H',
            'Sick' => 'S',
            'Permit' => 'I', // Izin
            'Leave' => 'C', // Cuti
            default => '-',
        };
    }

    public function title(): string
    {
        return 'Timesheet - ' . Carbon::createFromDate($this->year, $this->month)->format('F Y');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $monthName = Carbon::createFromDate($this->year, $this->month)->format('F Y');
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $this->dataCount + 4;

                // 1. Judul Laporan
                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->setCellValue('A1', 'Timesheet Report');
                $sheet->setCellValue('A2', "Period: {$monthName}");

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A2')->getFont()->setItalic(true);
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 2. Border
                $cellRange = "A4:{$lastColumn}{$lastRow}";
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // 3. Mewarnai Akhir Pekan
                foreach ($this->daysInMonth as $index => $day) {
                    if ($day->isWeekend()) {
                        $columnIndex = $index + 2;
                        $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);

                        $sheet->getStyle("{$columnLetter}4:{$columnLetter}{$lastRow}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFE0E0E0'],
                            ],
                        ]);
                    }
                }

                // --- LOGIKA BARU: MENENGahkan DATA TABEL ---
                $dataCellRange = "B5:{$lastColumn}{$lastRow}";
                $sheet->getStyle($dataCellRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B4:{$lastColumn}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


                // --- LOGIKA BARU: MENAMBAHKAN KETERANGAN DI KANAN ---
                $startLegendColumnIndex = Coordinate::columnIndexFromString($lastColumn) + 2;
                $symbolColumn = Coordinate::stringFromColumnIndex($startLegendColumnIndex);
                $descColumn = Coordinate::stringFromColumnIndex($startLegendColumnIndex + 1);

                $startLegendRow = 4;

                $sheet->setCellValue("{$symbolColumn}{$startLegendRow}", 'Keterangan:');
                $sheet->getStyle("{$symbolColumn}{$startLegendRow}")->getFont()->setBold(true);
                $sheet->mergeCells("{$symbolColumn}{$startLegendRow}:{$descColumn}{$startLegendRow}");

                $legend = [
                    'P' => 'Hadir (Present)',
                    'L' => 'Terlambat (Late)',
                    'A' => 'Mangkir (Absent)',
                    'H' => 'Libur Nasional (Holiday)',
                    'S' => 'Sakit (Sick)',
                    'I' => 'Izin (Permit)',
                    'C' => 'Cuti (Leave)',
                    '-' => 'Tidak Ada Data / Hari Libur Jadwal',
                ];

                $currentRow = $startLegendRow + 1;
                foreach ($legend as $symbol => $description) {
                    $sheet->setCellValue("{$symbolColumn}{$currentRow}", $symbol);
                    $sheet->setCellValue("{$descColumn}{$currentRow}", $description);
                    $sheet->getStyle("{$symbolColumn}{$currentRow}")->getFont()->setBold(true);
                    $sheet->getStyle("{$symbolColumn}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $currentRow++;
                }

                $sheet->getColumnDimension($symbolColumn)->setAutoSize(true);
                $sheet->getColumnDimension($descColumn)->setAutoSize(true);
            },
        ];
    }
}
