<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kuitansi - {{ $transaction->receipt_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 10px;
            background-color: #fff;
        }
        .receipt-box {
            max-width: 580px;
            margin: auto;
            border: 1px dashed #000;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            letter-spacing: 1px;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 11px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .details-table th {
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            text-align: left;
            font-size: 11px;
        }
        .details-table td {
            padding: 5px 0;
            font-size: 11px;
        }
        .text-right {
            text-align: right !important;
        }
        .footer-sig {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .sig-block {
            text-align: center;
            width: 180px;
            font-size: 11px;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-box">
        <div class="header">
            <h3>PAUD AL MARJAN</h3>
            <p>Jl. Raya Al Marjan No. 12, Kelapa Dua, Tangerang</p>
            <div class="divider"></div>
            <strong>KUITANSI BUKTI PEMBAYARAN</strong>
        </div>

        <table class="info-table">
            <tr>
                <td style="width: 55%;">
                    No: <strong>{{ $transaction->receipt_number }}</strong><br>
                    Tgl: {{ $transaction->date->format('d M Y') }}<br>
                    Kasir: {{ $transaction->user ? $transaction->user->name : '-' }}
                </td>
                <td style="width: 45%;" class="text-right">
                    Siswa: <strong>{{ $transaction->student->name }}</strong><br>
                    NIS: {{ $transaction->student->nis ?? '-' }}<br>
                    Kelas: {{ $transaction->academicYear->name }} / {{ $enrollment ? $enrollment->studentGroup->name : '-' }}
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <table class="details-table">
            <thead>
                <tr>
                    <th>Deskripsi Pembayaran</th>
                    <th class="text-right" style="width: 150px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->paymentDetails as $det)
                <tr>
                    <td>
                        @if($det->type === 'Annual')
                            {{ $det->studentAnnualFee->annualFeeComponent->name }} (Tahunan)
                        @else
                            SPP Bulan {{ $det->month_name }}
                        @endif
                    </td>
                    <td class="text-right">Rp {{ number_format($det->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <table class="info-table" style="margin-bottom: 0;">
            <tr>
                <td class="text-right" style="font-size: 13px;"><strong>TOTAL: Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</strong></td>
            </tr>
        </table>

        <div class="footer-sig">
            <div>
                <p style="font-size: 9px; margin: 0;">* Simpan bukti ini secara fisik / digital.</p>
                <p style="font-size: 9px; margin: 0;">* Dicetak otomatis oleh sistem.</p>
            </div>
            <div class="sig-block">
                <p>Pencatat/Kasir,</p>
                <br><br>
                <p>___________________</p>
                <p><strong>{{ $transaction->user ? $transaction->user->name : '-' }}</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
