<?php

namespace App\Filament\Resources\ComplaintResource\Pages;

use App\Filament\Resources\ComplaintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListComplaints extends ListRecords
{
    protected static string $resource = ComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai'),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai'),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'resolved' => 'Selesai',
                        ])
                        ->placeholder('Semua Status'),
                    \Filament\Forms\Components\Select::make('room_id')
                        ->label('Ruangan')
                        ->options(\App\Models\Room::pluck('name', 'id'))
                        ->placeholder('Semua Ruangan'),
                ])
                ->action(function (array $data) {
                    $startDate = $data['start_date'] ?? null;
                    $endDate = $data['end_date'] ?? null;
                    $status = $data['status'] ?? null;
                    $roomId = $data['room_id'] ?? null;

                    $query = \App\Models\Complaint::with(['resolver', 'room']);

                    if ($startDate) {
                        $query->whereDate('created_at', '>=', $startDate);
                    }
                    if ($endDate) {
                        $query->whereDate('created_at', '<=', $endDate);
                    }
                    if ($status) {
                        $query->where('status', $status);
                    }
                    if ($roomId) {
                        $query->where('room_id', $roomId);
                    }

                    $complaints = $query->orderBy('created_at', 'desc')->get();

                    $roomName = $roomId ? \App\Models\Room::find($roomId)?->name : null;

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.complaint-report', [
                        'complaints' => $complaints,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status_filter' => $status ? ucfirst($status) : null,
                        'room_filter' => $roomName,
                    ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'laporan_keluhan_' . now()->format('Ymd_His') . '.pdf'
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(ComplaintResource::getModel()::where('status', 'pending')->count()),
            'resolved' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'resolved')),
        ];
    }
}
