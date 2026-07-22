<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $users = \App\Models\User::orderBy('name', 'asc')->get();

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.user-report', [
                        'users' => $users,
                    ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'laporan_pengguna_' . now()->format('Ymd_His') . '.pdf'
                    );
                }),
            Actions\CreateAction::make(),
        ];
    }
}
