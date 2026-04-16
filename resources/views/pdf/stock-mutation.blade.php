<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Mutasi Stok</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #0f172a;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 30px;
            width: 100%;
        }
        .header-table {
            width: 100%;
        }
        .header-title {
            font-size: 24px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -0.05em;
            margin: 0;
        }
        .header-subtitle {
            font-size: 11px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 4px;
        }
        .report-label {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: right;
            margin: 0;
        }
        .report-date {
            font-size: 10px;
            color: #94a3b8;
            text-align: right;
            margin-top: 4px;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 30px;
        }
        .summary-item {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
        .summary-label {
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .summary-value {
            font-size: 12px;
            font-weight: bold;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            border-bottom: 2px solid #0f172a;
            padding: 10px 0;
            text-align: left;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
        }
        td {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .product-name {
            font-weight: bold;
            color: #0f172a;
            font-size: 12px;
        }
        .product-sku {
            font-size: 9px;
            color: #94a3b8;
            font-family: monospace;
        }
        .badge {
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .badge-in {
            color: #059669;
            background-color: #ecfdf5;
        }
        .badge-out {
            color: #e11d48;
            background-color: #fff1f2;
        }
        .amount {
            font-weight: bold;
            text-align: right;
        }
        .amount-in {
            color: #059669;
        }
        .amount-out {
            color: #e11d48;
        }
        .unit {
            font-size: 9px;
            font-weight: 900;
            color: #cbd5e1;
            margin-left: 2px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
        }
        .footer-text {
            font-size: 9px;
            font-weight: bold;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.2em;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <table class="header-table">
                <tr>
                    <td>
                        <h1 class="header-title">WARUNG ERP</h1>
                        <p class="header-subtitle">Sistem Manajemen Inventaris</p>
                    </td>
                    <td style="text-align: right;">
                        <h2 class="report-label">Laporan Mutasi Stok</h2>
                        <p class="report-date">Dicetak pada: {{ $generated_at->format('d M Y H:i') }}</p>
                    </td>
                </tr>
            </table>
        </header>

        <!-- Summary Info -->
        <div class="summary-box">
            <div class="summary-item" style="margin-right: 2%;">
                <div class="summary-label">Periode Laporan</div>
                <p class="summary-value">
                    {{ isset($filters['start_date']) ? \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') : 'Awal' }}
                    <span style="color: #cbd5e1; padding: 0 5px;">—</span>
                    {{ isset($filters['end_date']) ? \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') : 'Sekarang' }}
                </p>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Transaksi</div>
                <p class="summary-value">{{ $movements->count() }} Pergerakan Stok</p>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th width="15%">Tanggal</th>
                    <th width="35%">Barang</th>
                    <th width="10%">Tipe</th>
                    <th width="15%" style="text-align: right;">Jumlah</th>
                    <th width="25%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $movement)
                <tr>
                    <td>{{ $movement->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="product-name">{{ $movement->produk->nama }}</div>
                        <div class="product-sku">{{ $movement->produk->sku }}</div>
                    </td>
                    <td>
                        @if($movement->type === 'in')
                            <span class="badge badge-in">Masuk</span>
                        @else
                            <span class="badge badge-out">Keluar</span>
                        @endif
                    </td>
                    <td class="amount">
                        <span class="{{ $movement->type === 'in' ? 'amount-in' : 'amount-out' }}">
                            {{ $movement->type === 'in' ? '+' : '-' }}{{ number_format($movement->jumlah, 2) }}
                        </span>
                        <span class="unit">{{ $movement->satuan->simbol }}</span>
                    </td>
                    <td>
                        <div style="color: #64748b;">{{ $movement->keterangan }}</div>
                        @if($movement->reference_type)
                            <div style="font-size: 8px; font-weight: bold; color: #94a3b8; text-transform: uppercase; margin-top: 4px;">
                                Ref: {{ $movement->reference_type }}
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">Dokumen ini digenerate secara otomatis oleh Warung ERP</p>
        </div>
    </div>
</body>
</html>
