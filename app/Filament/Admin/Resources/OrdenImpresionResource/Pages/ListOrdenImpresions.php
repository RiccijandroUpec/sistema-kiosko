<?php

namespace App\Filament\Admin\Resources\OrdenImpresionResource\Pages;

use App\Filament\Admin\Resources\OrdenImpresionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrdenImpresions extends ListRecords
{
    protected static string $resource = OrdenImpresionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
