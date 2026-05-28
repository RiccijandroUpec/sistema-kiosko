<?php

namespace App\Filament\Admin\Resources\KioskoResource\Pages;

use App\Filament\Admin\Resources\KioskoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKioskos extends ListRecords
{
    protected static string $resource = KioskoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
