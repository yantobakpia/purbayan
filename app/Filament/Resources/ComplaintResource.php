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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required(),
            Forms\Components\TextInput::make('email_or_phone')->label('Email / No. HP')->required(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(['pending' => 'Pending', 'resolved' => 'Selesai'])
                ->required(),
            Forms\Components\Textarea::make('complaint_text')->label('Isi Keluhan')->columnSpanFull()->required(),
            Forms\Components\DateTimePicker::make('resolved_at')->label('Waktu Diselesaikan'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('email_or_phone')->label('Kontak'),
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
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('✓ Selesaikan')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn(Complaint $r) => $r->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Complaint $record) {
                        $record->update(['status' => 'resolved', 'resolved_at' => now()]);
                        Notification::make()->title('Keluhan ditandai selesai!')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListComplaints::route('/'),
            'create' => Pages\CreateComplaint::route('/create'),
            'edit'   => Pages\EditComplaint::route('/{record}/edit'),
        ];
    }
}
