<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalOrdenes     = OrdenImpresion::count();
        $ordenesPendiente = OrdenImpresion::where('estado', 'pendiente')->count();
        $ordenesHoy       = OrdenImpresion::whereDate('created_at', today())->count();
        $ingresoTotal     = OrdenImpresion::where('estado', 'completado')->sum('costo_total');
        $ingresoHoy       = OrdenImpresion::where('estado', 'completado')
                                ->whereDate('created_at', today())->sum('costo_total');
        $kioskosActivos   = Kiosko::where('estado', 'activo')->count();

        return [
            Stat::make('Kioskos Activos', $kioskosActivos)
                ->description('Cajeros en línea')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('success'),

            Stat::make('Órdenes Hoy', $ordenesHoy)
                ->description("$ordenesPendiente pendientes de pago")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Ingreso Hoy', '$' . number_format($ingresoHoy, 2))
                ->description('Total completadas hoy')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Ingreso Total', '$' . number_format($ingresoTotal, 2))
                ->description("$totalOrdenes órdenes en total")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
