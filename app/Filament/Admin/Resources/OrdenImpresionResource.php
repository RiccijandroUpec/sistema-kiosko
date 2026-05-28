<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrdenImpresionResource\Pages;
use App\Models\OrdenImpresion;
use App\Models\Kiosko;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section as InfoSection;

class OrdenImpresionResource extends Resource
{
    protected static ?string $model = OrdenImpresion::class;
    protected static ?string $navigationIcon = 'heroicon-o-printer';
    protected static ?string $navigationGroup = 'Impresiones';
    protected static ?string $navigationLabel = 'Órdenes de Impresión';
    protected static ?string $modelLabel = 'Orden';
    protected static ?string $pluralModelLabel = 'Órdenes de Impresión';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Detalle de la Orden')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\Select::make('kiosko_id')
                            ->label('Kiosko')
                            ->relationship('kiosko', 'nombre_comercial')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'pendiente'  => 'Pendiente',
                                'pagado'     => 'Pagado',
                                'imprimiendo' => 'Imprimiendo',
                                'completado' => 'Completado',
                                'cancelado'  => 'Cancelado',
                            ])
                            ->required()
                            ->native(false),
                    ]),
                    Forms\Components\TextInput::make('archivo_url')
                        ->label('URL del Archivo PDF')
                        ->url()
                        ->maxLength(255)
                        ->disabled(),
                    Grid::make(3)->schema([
                        Forms\Components\TextInput::make('paginas')
                            ->label('Páginas')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\Toggle::make('color')
                            ->label('¿Impresión a Color?')
                            ->disabled(),
                        Forms\Components\TextInput::make('costo_total')
                            ->label('Costo Total (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                    ]),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Información de la Orden')->schema([
                TextEntry::make('id')->label('ID')->copyable(),
                TextEntry::make('kiosko.nombre_comercial')->label('Kiosko')->badge(),
                TextEntry::make('estado')->label('Estado')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pagado', 'completado' => 'success',
                        'pendiente'            => 'warning',
                        'imprimiendo'          => 'info',
                        'cancelado'            => 'danger',
                        default                => 'gray',
                    }),
                TextEntry::make('cliente.telefono')->label('Cliente (Teléfono)'),
                TextEntry::make('paginas')->label('Páginas'),
                IconEntry::make('color')->label('A Color')->boolean(),
                TextEntry::make('costo_total')->label('Costo Total')->money('USD'),
                TextEntry::make('archivo_url')
                    ->label('PDF')
                    ->url(fn ($record) => $record->archivo_url)
                    ->openUrlInNewTab(),
                TextEntry::make('created_at')->label('Creada')->dateTime('d/m/Y H:i'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->limit(8)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kiosko.nombre_comercial')
                    ->label('Kiosko')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('cliente.telefono')
                    ->label('Cliente')
                    ->searchable()
                    ->default('—'),
                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['pagado', 'completado']),
                        'warning' => 'pendiente',
                        'info'    => 'imprimiendo',
                        'danger'  => 'cancelado',
                    ]),
                Tables\Columns\TextColumn::make('paginas')
                    ->label('Págs.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('color')
                    ->label('Color')
                    ->boolean(),
                Tables\Columns\TextColumn::make('costo_total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente'   => 'Pendiente',
                        'pagado'      => 'Pagado',
                        'imprimiendo' => 'Imprimiendo',
                        'completado'  => 'Completado',
                        'cancelado'   => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('kiosko_id')
                    ->label('Kiosko')
                    ->relationship('kiosko', 'nombre_comercial')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('color')
                    ->label('Solo a Color')
                    ->query(fn ($query) => $query->where('color', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index'  => Pages\ListOrdenImpresions::route('/'),
            'create' => Pages\CreateOrdenImpresion::route('/create'),
            'edit'   => Pages\EditOrdenImpresion::route('/{record}/edit'),
            'view'   => Pages\ViewOrdenImpresion::route('/{record}'),
        ];
    }
}
