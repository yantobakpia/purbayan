<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\DatePicker;
use App\Models\Booking;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use Carbon\Carbon;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai'),
                    DatePicker::make('end_date')
                        ->label('Tanggal Selesai'),
                    \Filament\Forms\Components\Select::make('room_id')
                        ->label('Ruangan')
                        ->options(\App\Models\Room::pluck('name', 'id'))
                        ->placeholder('Semua Ruangan'),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'checked_in' => 'Checked In',
                            'selesai' => 'Selesai',
                        ])
                        ->placeholder('Semua Status'),
                ])
                ->action(function (array $data) {
                    $startDate = $data['start_date'] ?? null;
                    $endDate = $data['end_date'] ?? null;
                    $roomId = $data['room_id'] ?? null;
                    $status = $data['status'] ?? null;

                    $query = Booking::query()->with('room');

                    if ($startDate) {
                        $query->where('date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $query->where('date', '<=', $endDate);
                    }
                    if ($roomId) {
                        $query->where('room_id', $roomId);
                    }
                    if ($status) {
                        $query->where('status', $status);
                    }

                    $bookings = $query->orderBy('date', 'asc')->orderBy('start_time', 'asc')->get();
                    $roomName = $roomId ? \App\Models\Room::find($roomId)?->name : null;

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.booking-report', [
                        'bookings' => $bookings,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'room_name' => $roomName,
                        'status_filter' => $status ? ucfirst($status) : null,
                    ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'laporan_peminjaman_' . now()->format('Ymd_His') . '.pdf'
                    );
                }),
            Actions\Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai'),
                    DatePicker::make('end_date')
                        ->label('Tanggal Selesai'),
                ])
                ->action(function (array $data) {
                    $startDate = $data['start_date'] ?? null;
                    $endDate = $data['end_date'] ?? null;

                    $query = Booking::query()
                        ->select('room_id', 'date')
                        ->selectRaw('count(*) as total_pengajuan')
                        ->selectRaw("sum(case when status in ('approved', 'checked_in', 'selesai') then 1 else 0 end) as disetujui")
                        ->selectRaw("sum(case when status = 'rejected' then 1 else 0 end) as ditolak")
                        ->groupBy('room_id', 'date')
                        ->with('room');

                    if ($startDate) {
                        $query->where('date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $query->where('date', '<=', $endDate);
                    }

                    $bookings = $query->get();

                    return response()->streamDownload(function () use ($bookings) {
                        $writer = new Writer();
                        $writer->openToFile('php://output');

                        // Add Header Row
                        $writer->addRow(Row::fromValues([
                            'No',
                            'Ruangan',
                            'Tanggal',
                            'Total Pengajuan',
                            'Disetujui',
                            'Ditolak'
                        ]));

                        $no = 1;
                        foreach ($bookings as $booking) {
                            $writer->addRow(Row::fromValues([
                                $no++,
                                $booking->room?->name ?? 'N/A',
                                Carbon::parse($booking->date)->format('d-m-Y'),
                                (int) $booking->total_pengajuan,
                                (int) $booking->disetujui,
                                (int) $booking->ditolak,
                            ]));
                        }

                        // Add Summary Row
                        $writer->addRow(Row::fromValues([
                            'Total',
                            '',
                            '',
                            (int) $bookings->sum('total_pengajuan'),
                            (int) $bookings->sum('disetujui'),
                            (int) $bookings->sum('ditolak'),
                        ]));

                        $writer->close();
                    }, 'rekap_peminjaman.xlsx', [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
                }),
            Actions\CreateAction::make(),
        ];
    }
}
