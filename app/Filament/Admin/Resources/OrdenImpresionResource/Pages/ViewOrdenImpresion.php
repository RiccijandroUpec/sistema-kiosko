<?php

namespace App\Filament\Admin\Resources\OrdenImpresionResource\Pages;

use App\Filament\Admin\Resources\OrdenImpresionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewOrdenImpresion extends ViewRecord
{
    protected static string $resource = OrdenImpresionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
