<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'Audit Log';

    protected static ?string $pluralModelLabel = 'Audit Log';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 99;

    // Hanya Owner. Log tidak bisa dibuat, diubah, atau dihapus dari panel.
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Aktivitas')
                ->columns(3)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Waktu')
                        ->dateTime('d M Y H:i:s'),

                    TextEntry::make('user.name')
                        ->label('Pelaku')
                        ->placeholder('Tidak diketahui'),

                    TextEntry::make('event')
                        ->label('Aksi')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => AuditLog::EVENT_LABELS[$state] ?? $state)
                        ->color(fn (string $state): string => AuditLog::EVENT_COLORS[$state] ?? 'gray'),

                    TextEntry::make('subject')
                        ->label('Objek')
                        ->state(fn (AuditLog $record): string => $record->subjectLabel()
                            . ($record->auditable_id ? ' #' . $record->auditable_id : '')),

                    TextEntry::make('description')
                        ->label('Keterangan')
                        ->placeholder('-'),

                    TextEntry::make('ip_address')
                        ->label('Alamat IP')
                        ->placeholder('-'),

                    TextEntry::make('user_agent')
                        ->label('Perangkat')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),

            Section::make('Perubahan Nilai')
                ->schema([
                    TextEntry::make('changes')
                        ->label('')
                        ->state(fn (AuditLog $record): array => $record->changeLines())
                        ->listWithLineBreaks()
                        ->placeholder('Tidak ada perubahan nilai yang dicatat.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Pelaku')
                    ->placeholder('Tidak diketahui')
                    ->searchable(),

                TextColumn::make('event')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AuditLog::EVENT_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => AuditLog::EVENT_COLORS[$state] ?? 'gray'),

                TextColumn::make('subject')
                    ->label('Objek')
                    ->state(fn (AuditLog $record): string => $record->subjectLabel()),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn (AuditLog $record): ?string => $record->description)
                    ->searchable(),

                TextColumn::make('summary')
                    ->label('Yang berubah')
                    ->state(fn (AuditLog $record): string => $record->summary())
                    ->limit(40),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('created_at')
                    ->label('Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari tanggal'),
                        DatePicker::make('until')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),

                SelectFilter::make('event')
                    ->label('Aksi')
                    ->options(AuditLog::EVENT_LABELS),

                SelectFilter::make('user_id')
                    ->label('Pelaku')
                    ->relationship('user', 'name'),

                SelectFilter::make('auditable_type')
                    ->label('Objek')
                    ->options(AuditLog::MODEL_LABELS),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}