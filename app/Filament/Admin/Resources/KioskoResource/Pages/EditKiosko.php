<?php

namespace App\Filament\Admin\Resources\KioskoResource\Pages;

use App\Filament\Admin\Resources\KioskoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKiosko extends EditRecord
{
    protected static string $resource = KioskoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
