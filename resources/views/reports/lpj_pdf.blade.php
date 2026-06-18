<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>LPJ Pengeluaran Kas - PAUD Al Marjan</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #0d9488; /* Teal theme color */
            text-transform: uppercase;
        }
        .header h3 {
            margin: 0 0 5px 0;
            font-size: 13px;
            color: #333;
        }
        .header p {
            margin: 0;
            font-size: 10px;
            color: #666;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        .summary-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary-table td.amount, .summary-table th.amount {
            text-align: right;
            white-space: nowrap;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .page-break {
            page-break-before: always;
        }
        .receipt-container {
            margin-bottom: 30px;
            page-break-inside: avoid;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #fafafa;
            border-radius: 4px;
        }
        .receipt-header {
            font-size: 12px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
            color: #0f766e;
        }
        .receipt-meta {
            margin-bottom: 10px;
            font-size: 10px;
            line-height: 1.5;
        }
        .receipt-image {
            max-width: 100%;
            max-height: 400px;
            display: block;
            margin: 10px auto 0 auto;
            border: 1px solid #eee;
        }
        .receipt-placeholder {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            color: #666;
            background-color: #fff;
            margin-top: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <h2>Buku Kas Pengeluaran Operasional</h2>
        <h3>PAUD AL MARJAN</h3>
        <p>Periode Laporan: {{ date('d M Y', strtotime($startDate)) }} s/d {{ date('d M Y', strtotime($endDate)) }}</p>
    </div>

    <!-- Summary Cash Ledger Table -->
    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Tanggal</th>
                <th style="width: 25%;">Kategori</th>
                <th>Keterangan/Catatan</th>
                <th style="width: 20%;" class="amount">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @php $idx = 1; @endphp
            @foreach($expenses as $exp)
                <tr>
                    <td>{{ $idx++ }}</td>
                    <td>{{ $exp->date->format('d M Y') }}</td>
                    <td>{{ $exp->expenseCategory->name }}</td>
                    <td>{{ $exp->notes ?? 'Tanpa catatan' }}</td>
                    <td class="amount">Rp {{ number_format($exp->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">TOTAL PENGELUARAN:</td>
                <td class="amount">Rp {{ number_format($totalOutcome, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Section Divider / Page Break for Physical Attachments -->
    <div class="page-break"></div>

    <div class="header">
        <h2>Lampiran Bukti Fisik Pengeluaran</h2>
        <p>Periode Laporan: {{ date('d M Y', strtotime($startDate)) }} s/d {{ date('d M Y', strtotime($endDate)) }}</p>
    </div>

    <!-- List of Receipt Attachments -->
    @foreach($expenses as $exp)
        <div class="receipt-container">
            <div class="receipt-header">
                Lampiran Bukti Pengeluaran #{{ str_pad($exp->id, 5, '0', STR_PAD_LEFT) }}
            </div>
            <div class="receipt-meta">
                <strong>Tanggal:</strong> {{ $exp->date->format('d M Y') }}<br>
                <strong>Kategori:</strong> {{ $exp->expenseCategory->name }}<br>
                <strong>Nominal Belanja:</strong> Rp {{ number_format($exp->amount, 0, ',', '.') }}<br>
                <strong>Keterangan/Catatan:</strong> {{ $exp->notes ?? '-' }}
            </div>
            
            @if($exp->attachment_path)
                @php
                    $defaultDisk = config('filesystems.default');
                    $disk = ($defaultDisk === 'local') ? 'public' : $defaultDisk;
                    $exists = \Illuminate\Support\Facades\Storage::disk($disk)->exists($exp->attachment_path);
                    $extension = strtolower(pathinfo($exp->attachment_path, PATHINFO_EXTENSION));
                @endphp

                @if($exists)
                    @if(in_array($extension, ['jpg', 'jpeg', 'png']))
                        <!-- Embedded Image for JPG/PNG -->
                        <img class="receipt-image" src="data:image/{{ $extension }};base64,{{ base64_encode(\Illuminate\Support\Facades\Storage::disk($disk)->get($exp->attachment_path)) }}">
                    @elseif($extension === 'pdf')
                        <!-- PDF Placeholder message -->
                        <div class="receipt-placeholder">
                            <strong>Berkas Bukti Berformat PDF:</strong><br>
                            <span style="font-family: monospace;">{{ basename($exp->attachment_path) }}</span><br><br>
                            <span style="font-size: 10px; color: #888;">(Berkas PDF tidak dapat digabung langsung secara otomatis ke cetakan PDF ini. Silakan periksa di folder/penyimpanan cloud: <code>{{ $exp->attachment_path }}</code>)</span>
                        </div>
                    @elseif(in_array($extension, ['doc', 'docx']))
                        <!-- Word Document Placeholder message -->
                        <div class="receipt-placeholder">
                            <strong>Berkas Bukti Berformat Microsoft Word:</strong><br>
                            <span style="font-family: monospace;">{{ basename($exp->attachment_path) }}</span><br><br>
                            <span style="font-size: 10px; color: #888;">(Berkas Word tidak dapat digabung langsung secara otomatis ke cetakan PDF ini. Silakan periksa di folder/penyimpanan cloud: <code>{{ $exp->attachment_path }}</code>)</span>
                        </div>
                    @endif
                @else
                    <div class="receipt-placeholder" style="color: #ef4444;">
                        <strong>Berkas bukti fisik tidak ditemukan di storage ({{ basename($exp->attachment_path) }})</strong>
                    </div>
                @endif
            @else
                <div class="receipt-placeholder">
                    <strong>Tidak ada berkas bukti diunggah</strong>
                </div>
            @endif
        </div>
    @endforeach

</body>
</html>
