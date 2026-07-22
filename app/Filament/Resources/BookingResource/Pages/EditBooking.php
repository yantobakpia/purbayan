<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->mountUsing(fn ($form) => $form->fill([
                    'random_word' => collect(['HAPUS', 'KONFIRMASI', 'SETUJU', 'YAKIN', 'BENAR', 'BERSIHKAN', 'PERMANEN', 'MUTLAK', 'LANJUT', 'OKEY'])->random(),
                ]))
                ->form([
                    Forms\Components\Hidden::make('random_word'),
                    Forms\Components\TextInput::make('confirm_word')
                        ->label(fn (Forms\Get $get) => "Ketik kata \"" . $get('random_word') . "\" untuk mengonfirmasi")
                        ->required()
                        ->rules([
                            fn (Forms\Get $get) => function (string $attribute, $value, $fail) use ($get) {
                                if (strtoupper($value) !== $get('random_word')) {
                                    $fail('Kata konfirmasi tidak cocok.');
                                }
                            },
                        ]),
                ])
        ];
    }
}
