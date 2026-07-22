<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Models\Room;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

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
