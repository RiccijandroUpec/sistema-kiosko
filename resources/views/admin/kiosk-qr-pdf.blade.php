<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 22px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
        }
        .card {
            border: 1px solid #dbe2ea;
            border-radius: 28px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12);
        }
        .header {
            background: linear-gradient(135deg, #020617 0%, #1e1b4b 52%, #4338ca 100%);
            color: white;
            padding: 30px 32px;
        }
        .header h1 {
            margin: 8px 0 0;
            font-size: 30px;
            line-height: 1.1;
        }
        .header p {
            margin: 10px 0 0;
            color: #c7d2fe;
            font-size: 12px;
        }
        .body {
            display: table;
            width: 100%;
            background: white;
        }
        .col {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
            padding: 32px;
        }
        .qr-wrap {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 22px;
            text-align: center;
        }
        .qr-wrap img {
            width: 300px;
            height: 300px;
        }
        .badge {
            display: inline-block;
            background: rgba(255,255,255,0.12);
            color: #c7d2fe;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 7px 12px;
        }
        .steps {
            margin: 0;
            padding-left: 18px;
            font-size: 13px;
            line-height: 1.8;
            color: #334155;
        }
        .meta {
            margin-top: 20px;
            padding: 16px 18px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            font-size: 11px;
            line-height: 1.9;
        }
        .meta strong {
            color: #0f172a;
        }
        .footer {
            padding: 0 32px 28px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <span class="badge">RickTech Kiosko</span>
            <h1>{{ $kiosk->nombre }}</h1>
            <p>{{ $kiosk->ubicacion ?? 'Sin ubicación' }}</p>
            <p>QR para WhatsApp y asignación automática de sede</p>
        </div>

        <div class="body">
            <div class="col" style="width: 52%;">
                <div class="qr-wrap">
                    <img src="{{ $qrDataUri }}" alt="QR {{ $kiosk->nombre }}">
                </div>
            </div>
            <div class="col" style="width: 48%;">
                <h2 style="margin:0 0 10px;font-size:19px;">Instrucciones</h2>
                <ol class="steps">
                    <li>Escanea el QR con WhatsApp.</li>
                    <li>Envía el mensaje que se abre automáticamente.</li>
                    <li>Luego manda tu PDF al sistema.</li>
                    <li>El pedido quedará asociado a esta sede.</li>
                </ol>

                <div class="meta">
                    <div><strong>WhatsApp:</strong> {{ config('evolution.whatsapp_number') }}</div>
                    <div><strong>Estado:</strong> {{ ucfirst($kiosk->estado_conexion) }}</div>
                    <div><strong>Fecha:</strong> {{ now()->format('Y-m-d H:i') }}</div>
                </div>

                <div style="margin-top: 18px; padding: 16px 18px; border-radius: 18px; background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%); border: 1px solid #dbe2ea;">
                    <div style="font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #64748b;">Sede activa</div>
                    <div style="margin-top: 6px; font-size: 18px; font-weight: 900; color: #0f172a;">{{ $kiosk->nombre }}</div>
                    <div style="margin-top: 2px; font-size: 12px; color: #475569;">{{ $kiosk->ubicacion ?? 'Sin ubicación' }}</div>
                </div>
            </div>
        </div>

        <div class="footer">Tarjeta QR lista para mostrador o pared. Formato premium de impresión.</div>
    </div>
</body>
</html>
