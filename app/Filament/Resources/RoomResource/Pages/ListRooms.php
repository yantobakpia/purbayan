<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $rooms = \App\Models\Room::orderBy('name', 'asc')->get();

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.room-report', [
                        'rooms' => $rooms,
                    ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'laporan_ruangan_' . now()->format('Ymd_His') . '.pdf'
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }
}
