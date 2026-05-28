<?php

namespace App\Filament\Admin\Resources\OrdenImpresionResource\Pages;

use App\Filament\Admin\Resources\OrdenImpresionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenImpresion extends EditRecord
{
    protected static string $resource = OrdenImpresionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
