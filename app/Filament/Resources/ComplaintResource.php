<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplaintResource\Pages;
use App\Models\Complaint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'Keluhan';
    protected static ?string $modelLabel = 'Keluhan';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required(),
            Forms\Components\TextInput::make('email_or_phone')
                ->label('No. HP')
                ->required()
                ->regex('/^(08|\+62|62)[0-9]{7,13}$/')
                ->validationMessages([
                    'regex' => 'Format nomor HP tidak valid. Gunakan format seperti 08123456789 atau +628123456789.',
                ]),
            Forms\Components\Select::make('room_id')
                ->label('Ruangan')
                ->relationship('room', 'name')
                ->nullable()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(['pending' => 'Pending', 'resolved' => 'Selesai'])
                ->required(),
            Forms\Components\Textarea::make('complaint_text')->label('Isi Keluhan')->columnSpanFull()->required(),
            Forms\Components\Textarea::make('admin_response')->label('Tindak Lanjut / Respon Admin')->columnSpanFull(),
            Forms\Components\FileUpload::make('photo_path')
                ->label('Foto Keluhan')
                ->image()
                ->directory('complaints')
                ->columnSpanFull()
                ->saveUploadedFileUsing(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file) {
                    $filename = uniqid() . '.webp';

                    $info = getimagesize($file->getRealPath());
                    $image = null;
                    if ($info) {
                        $mime = $info['mime'];
                        switch ($mime) {
                            case 'image/jpeg':
                                $image = imagecreatefromjpeg($file->getRealPath());
                                break;
                            case 'image/png':
                                $image = imagecreatefrompng($file->getRealPath());
                                if ($image) {
                                    imagepalettetotruecolor($image);
                                    imagesavealpha($image, true);
                                }
                                break;
                            case 'image/gif':
                                $image = imagecreatefromgif($file->getRealPath());
                                break;
                            case 'image/webp':
                                $image = imagecreatefromwebp($file->getRealPath());
                                break;
                        }
                    }

                    if ($image) {
                        ob_start();
                        imagewebp($image, null, 80);
                        $webpData = ob_get_clean();
                        imagedestroy($image);

                        \Illuminate\Support\Facades\Storage::disk('public')->put('complaints/' . $filename, $webpData);
                        return 'complaints/' . $filename;
                    }

                    return $file->storeAs('complaints', $filename, 'public');
                }),
            Forms\Components\DateTimePicker::make('resolved_at')->label('Waktu Diselesaikan'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('email_or_phone')->label('No. HP'),
                Tables\Columns\TextColumn::make('room.name')->label('Ruangan')->placeholder('-'),
                Tables\Columns\ImageColumn::make('photo_path')->label('Foto'),
                Tables\Columns\TextColumn::make('complaint_text')->label('Keluhan')->limit(60),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors(['warning' => 'pending', 'success' => 'resolved'])
                    ->formatStateUsing(fn($state) => $state === 'resolved' ? 'Selesai' : 'Pending'),
                Tables\Columns\TextColumn::make('resolved_at')->label('Diselesaikan')->dateTime('d M Y H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'resolved' => 'Selesai']),
                Tables\Filters\SelectFilter::make('room_id')
                    ->label('Ruangan')
                    ->relationship('room', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('exportPdf')
                    ->label('Export PDF')
                    ->color('danger')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Complaint $record) {
                        $record->load('resolver');
                        $photoBase64 = null;
                        if ($record->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->photo_path)) {
                            $imageContent = \Illuminate\Support\Facades\Storage::disk('public')->get($record->photo_path);
                            $mimeType = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($record->photo_path);
                            $photoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
                        }

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.single-complaint', [
                            'complaint' => $record,
                            'photoBase64' => $photoBase64,
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'laporan_keluhan_' . $record->id . '_' . now()->format('Ymd_His') . '.pdf'
                        );
                    }),
                Tables\Actions\Action::make('resolve')
                    ->label('✓ Selesaikan')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn(Complaint $r) => $r->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_response')
                            ->label('Tindak Lanjut / Respon Admin')
                            ->placeholder('Tuliskan tindakan yang telah dilakukan...')
                            ->required(),
                    ])
                    ->action(function (Complaint $record, array $data) {
                        $record->update([
                            'status' => 'resolved',
                            'resolved_at' => now(),
                            'resolved_by' => auth()->id(),
                            'admin_response' => $data['admin_response'],
                        ]);
                        Notification::make()->title('Keluhan ditandai selesai!')->success()->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->label('Edit Respon')
                    ->form([
                        Forms\Components\Textarea::make('admin_response')
                            ->label('Tindak Lanjut / Respon Admin')
                            ->required(),
                    ])
                    ->using(function (Complaint $record, array $data): Complaint {
                        $record->update([
                            'admin_response' => $data['admin_response'],
                            'status' => 'resolved',
                            'resolved_at' => $record->resolved_at ?? now(),
                            'resolved_by' => auth()->id(),
                        ]);
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
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
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('exportSelectedPdf')
                        ->label('Export PDF Terpilih')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->load(['resolver', 'room']);

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.complaint-report', [
                                'complaints' => $records,
                                'start_date' => null,
                                'end_date' => null,
                                'status_filter' => 'Terpilih (' . $records->count() . ' Data)',
                                'room_filter' => null,
                            ]);

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'laporan_keluhan_terpilih_' . now()->format('Ymd_His') . '.pdf'
                            );
                        }),
                    Tables\Actions\DeleteBulkAction::make()
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
                        ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListComplaints::route('/'),
            'create' => Pages\CreateComplaint::route('/create'),
        ];
    }
}
