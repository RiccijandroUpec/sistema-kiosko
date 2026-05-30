<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KioskoResource\Pages;
use App\Models\Kiosko;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

class KioskoResource extends Resource
{
    protected static ?string $model = Kiosko::class;
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationGroup = 'Kioskos';
    protected static ?string $navigationLabel = 'Kioskos';
    protected static ?string $modelLabel = 'Kiosko';
    protected static ?string $pluralModelLabel = 'Kioskos';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Información General')
                ->description('Datos básicos del kiosko de impresión.')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Forms\Components\TextInput::make('id')
                        ->label('ID del Kiosko (UUID)')
                        ->disabled()
                        ->visible(fn ($record) => $record !== null),
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('nombre_comercial')
                            ->label('Nombre Comercial')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Ej: Kiosko Plaza Central'),
                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'activo'   => 'Activo',
                                'inactivo' => 'Inactivo',
                                'offline'  => 'Offline',
                            ])
                            ->default('inactivo')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('pin')
                            ->label('PIN de Seguridad')
                            ->required()
                            ->minLength(4)
                            ->maxLength(4)
                            ->default('0000')
                            ->placeholder('Ej: 1234')
                            ->helperText('PIN de 4 dígitos para acceso local.'),
                    ]),
                    Forms\Components\TextInput::make('nombre_cups')
                        ->label('Nombre de Impresora (CUPS)')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Nombre exacto de la impresora en el sistema Linux (comando: lpstat -a)')
                        ->placeholder('Ej: HP_LaserJet_Pro'),
                ]),

            Section::make('Precios de Impresión')
                ->description('Costos por hoja de impresión.')
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('precio_blanco_negro')
                            ->label('Precio B/N (USD)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('$')
                            ->default(0.05),
                        Forms\Components\TextInput::make('precio_color')
                            ->label('Precio Color (USD)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('$')
                            ->default(0.20),
                    ]),
                ]),

            Section::make('Diseño Visual')
                ->description('Personalización de la interfaz del cajero.')
                ->icon('heroicon-o-paint-brush')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\ColorPicker::make('color_tema')
                            ->label('Color Primario')
                            ->default('#7c3aed')
                            ->required(),
                        Forms\Components\TextInput::make('logo_url')
                            ->label('URL del Logo')
                            ->maxLength(255)
                            ->url()
                            ->placeholder('https://tu-bucket.supabase.co/storage/logo.png')
                            ->helperText('URL pública del logo en Supabase Storage'),
                    ]),
                ]),

            Section::make('Estado del Sistema')
                ->description('Información de conectividad.')
                ->icon('heroicon-o-signal')
                ->schema([
                    Forms\Components\DateTimePicker::make('ultima_conexion')
                        ->label('Última Conexión')
                        ->disabled()
                        ->helperText('Actualizado automáticamente por el agente local.'),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID (UUID)')
                    ->fontFamily('mono')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8) . '...')
                    ->copyable()
                    ->copyableState(fn (string $state): string => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nombre_comercial')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'success' => 'activo',
                        'danger'  => 'inactivo',
                        'warning' => 'offline',
                    ]),
                Tables\Columns\TextColumn::make('precio_blanco_negro')
                    ->label('B/N')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('precio_color')
                    ->label('Color')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nombre_cups')
                    ->label('Impresora CUPS')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ColorColumn::make('color_tema')
                    ->label('Tema')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ultima_conexion')
                    ->label('Última Conexión')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->ultima_conexion?->format('d/m/Y H:i:s')),
                Tables\Columns\TextColumn::make('ordenes_count')
                    ->label('Órdenes')
                    ->counts('ordenes')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'activo'   => 'Activo',
                        'inactivo' => 'Inactivo',
                        'offline'  => 'Offline',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('poster')
                    ->label('Imprimir Poster A4')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn ($record) => route('kiosko.poster', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKioskos::route('/'),
            'create' => Pages\CreateKiosko::route('/create'),
            'edit'   => Pages\EditKiosko::route('/{record}/edit'),
        ];
    }
}
