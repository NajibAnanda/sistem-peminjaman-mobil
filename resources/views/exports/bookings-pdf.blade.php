<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Riwayat Peminjaman Mobil Dinas</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 12mm 12mm 15mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }
        .header table {
            width: 100%;
        }
        .title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
        }
        .subtitle {
            font-size: 8pt;
            color: #475569;
            margin: 0;
        }
        .meta-right {
            text-align: right;
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.4;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6px 5px;
            border: 1px solid #1e293b;
            text-align: left;
        }
        table.data-table td {
            padding: 5px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
            font-size: 7.5pt;
            word-wrap: break-word;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #64748b; font-size: 7pt; }
        .text-danger { color: #dc2626; font-weight: bold; font-size: 7pt; }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-completed { background-color: #e2e8f0; color: #334155; }
        .badge-active { background-color: #dbeafe; color: #1e40af; }
        .badge-scheduled { background-color: #e0f2fe; color: #0369a1; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }
        .badge-overdue { background-color: #ffedd5; color: #9a3412; }
        .footer-note {
            margin-top: 10px;
            font-size: 7pt;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="vertical-align: middle;">
                    <h1 class="title">Laporan Riwayat Peminjaman Mobil Dinas</h1>
                    <p class="subtitle">
                        Periode: 
                        <strong>
                            {{ $table->from ? \App\Support\DateFormat::date($table->from) : '-' }} 
                            s/d 
                            {{ $table->to ? \App\Support\DateFormat::date($table->to) : '-' }}
                        </strong>
                        &nbsp;&bull;&nbsp; Total: <strong>{{ $bookings->count() }} Data</strong>
                    </p>
                </td>
                <td class="meta-right" style="vertical-align: middle;">
                    <div>Dicetak pada: <strong>{{ \App\Support\DateFormat::dateTime($now) }}</strong></div>
                    <div>Petugas GA: <strong>{{ $actor->name }}</strong></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 85px;">Kode</th>
                <th style="width: 125px;">Peminjam</th>
                <th style="width: 95px;">Kendaraan</th>
                <th style="width: 110px;">Jadwal Rencana</th>
                <th style="width: 110px;">Realisasi Aktual</th>
                <th style="width: 80px;">Status</th>
                <th>Tujuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $index => $booking)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $booking->code }}</td>
                    <td>
                        <div class="font-bold">{{ $booking->employee->name }}</div>
                        <div class="text-muted">{{ $booking->employee->division }} &bull; {{ $booking->employee->phone }}</div>
                    </td>
                    <td>
                        <div class="font-bold">{{ $booking->car->brand }} {{ $booking->car->model }}</div>
                        <div class="text-muted">{{ $booking->car->plate_number }}</div>
                    </td>
                    <td>
                        <div><span class="text-muted">Mulai:</span> {{ \App\Support\DateFormat::dateTime($booking->start_at) }}</div>
                        <div><span class="text-muted">Kembali:</span> {{ \App\Support\DateFormat::dateTime($booking->planned_return_at) }}</div>
                    </td>
                    <td>
                        <div>
                            <span class="text-muted">Ambil:</span> 
                            {{ $booking->picked_up_at ? \App\Support\DateFormat::dateTime($booking->picked_up_at) : '-' }}
                        </div>
                        <div>
                            <span class="text-muted">Kembali:</span> 
                            {{ $booking->returned_at ? \App\Support\DateFormat::dateTime($booking->returned_at) : '-' }}
                        </div>
                    </td>
                    <td>
                        <div>{{ $booking->displayStatus($now)->label() }}</div>
                        @if($booking->extensions_count > 0)
                            <div class="text-muted">Perpanjang: {{ $booking->extensions_count }}x</div>
                        @endif
                        @php $lateness = $booking->latenessMinutes($now); @endphp
                        @if($lateness !== null)
                            <div class="text-danger">Telat: {{ \App\Support\DateFormat::duration($lateness) }}</div>
                        @endif
                    </td>
                    <td>
                        <div>{{ $booking->purpose }}</div>
                        @if($booking->cancellation_reason)
                            <div class="text-danger" style="margin-top: 2px;">
                                Batal: {{ $booking->cancellation_reason }}
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">
                        Tidak ada data peminjaman untuk periode dan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Dokumen ini dibuat otomatis oleh Sistem Peminjaman Mobil Dinas pada {{ \App\Support\DateFormat::dateTime($now) }}.
    </div>
</body>
</html>
