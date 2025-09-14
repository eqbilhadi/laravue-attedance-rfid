<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Attendance;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithColumnFormatting; // <-- TAMBAHKAN IMPORT INI
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // <-- TAMBAHKAN IMPORT INI

class MonthlyReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents, WithColumnFormatting
{
    protected $year;
    protected $month;
    protected $userId;
    private $dataCount;

    public function __construct($year, $month, $userId)
    {
        $this->year = $year;
        $this->month = $month;
        $this->userId = $userId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $collection = User::query()
            ->where('is_active', true)
            ->whereHas('attendances', function ($q) {
                $q->whereYear('date', $this->year)->whereMonth('date', $this->month);
            })
            // --- Eager load semua relasi yang dibutuhkan ---
            ->with([
                'attendances' => function ($query) {
                    $query->whereYear('date', $this->year)->whereMonth('date', $this->month);
                },
                'userSchedules' => function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<=', $endDate)
                        ->where(fn($q) => $q->where('end_date', '>=', $startDate)->orWhereNull('end_date'))
                        ->with('workSchedule.days.time');
                }
            ])
            ->when($this->userId, fn($q) => $q->where('id', $this->userId))
            ->get();

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
            'Work Days',
            'Presence Ratio (%)',
            'Present',
            'Late',
            'Absent',
            'Sick',
            'Permit',
            'Leave',
        ];
    }

    /**
     * @param mixed $user
     * @return array
     */
    public function map($user): array
    {
        // Gunakan data dari relasi yang sudah di-load, bukan query baru
        $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = $user->attendances;
        $userSchedule = $user->userSchedules->first();

        $totalWorkDays = 0;
        if ($userSchedule) {
            $dailyScheduleMap = $userSchedule->workSchedule->days->keyBy('day_of_week');
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $isWithinScheduleRange = $userSchedule->end_date
                    ? $date->between($userSchedule->start_date, $userSchedule->end_date)
                    : $date->gte($userSchedule->start_date);
                if ($isWithinScheduleRange && $dailyScheduleMap->has($date->dayOfWeekIso) && $dailyScheduleMap->get($date->dayOfWeekIso)->time) {
                    $totalWorkDays++;
                }
            }
        }

        $presentCount = $attendances->where('status', 'Present')->count();
        $lateCount = $attendances->where('status', 'Late')->count();
        $totalPresence = $presentCount + $lateCount;
        $presenceRatio = ($totalWorkDays > 0) ? round(($totalPresence / $totalWorkDays) * 100, 2) : 0;

        return [
            $user->name,
            $totalWorkDays,
            $presenceRatio,
            $presentCount,
            $lateCount,
            $attendances->where('status', 'Absent')->count(),
            $attendances->where('status', 'Sick')->count(),
            $attendances->where('status', 'Permit')->count(),
            $attendances->where('status', 'Leave')->count(),
        ];
    }

    /**
     * Menentukan format kolom.
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'B' => '0',
            'C' => '0.00"%"', // Format kustom untuk menampilkan persentase
            'D' => '0',
            'E' => '0',
            'F' => '0',
            'G' => '0',
            'H' => '0',
            'I' => '0',
        ];
    }

    /**
     * Menentukan nama worksheet.
     * @return string
     */
    public function title(): string
    {
        return Carbon::createFromDate($this->year, $this->month)->format('F Y');
    }

    /**
     * Menerapkan style ke worksheet.
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Mendaftarkan event untuk manipulasi sheet setelah dibuat.
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. Menambahkan Judul Laporan di Atas
                $monthName = Carbon::createFromDate($this->year, $this->month)->format('F Y');
                $sheet->insertNewRowBefore(1, 3);
                $sheet->mergeCells('A1:I1');
                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue('A1', 'Monthly Attendance Report');
                $sheet->setCellValue('A2', "Period: {$monthName}");

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A2')->getFont()->setItalic(true);
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');

                // 2. Menyesuaikan Lebar Kolom Secara Otomatis
                foreach (range('A', 'I') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                // 3. Menambahkan Border ke Seluruh Tabel
                $lastRow = $this->dataCount + 4;
                $cellRange = 'A4:I' . $lastRow;
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
