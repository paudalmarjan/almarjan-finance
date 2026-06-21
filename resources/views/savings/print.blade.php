<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Transaksi Tabungan - {{ $transaction->receipt_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            border: 1px dashed #ccc;
            padding: 20px;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .header h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        .transaction-info {
            margin-bottom: 20px;
        }
        .transaction-info table {
            width: 100%;
        }
        .transaction-info td {
            padding: 4px 0;
            vertical-align: top;
        }
        .transaction-info td:first-child {
            width: 130px;
            font-weight: bold;
        }
        .amount-box {
            text-align: center;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .amount-box span {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .amount-box strong {
            font-size: 24px;
            color: #000;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }
        .signature {
            width: 45%;
        }
        .signature p {
            margin: 0 0 50px 0;
        }
        .signature span {
            border-top: 1px solid #333;
            display: block;
            padding-top: 5px;
        }
        .btn-print {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            padding: 10px;
            background: #065f46;
            color: #fff;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }
        @media print {
            body { padding: 0; background: none; }
            .receipt-container { border: none; max-width: 100%; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        <div class="header">
            <h3>PAUD AL MARJAN</h3>
            <p>Bukti Mutasi Tabungan Siswa</p>
        </div>

        <div class="transaction-info">
            <table>
                <tr>
                    <td>No. Referensi</td>
                    <td>: {{ $transaction->receipt_number }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: {{ $transaction->transaction_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Nama Siswa</td>
                    <td>: {{ $transaction->student->name }}</td>
                </tr>
                <tr>
                    <td>NIS</td>
                    <td>: {{ $transaction->student->nis }}</td>
                </tr>
                <tr>
                    <td>Jenis Transaksi</td>
                    <td>: {{ $transaction->type === 'Deposit' ? 'Setoran Masuk' : 'Penarikan Keluar' }}</td>
                </tr>
                @if($transaction->notes)
                <tr>
                    <td>Catatan</td>
                    <td>: {{ $transaction->notes }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="amount-box">
            <span>Nominal Mutasi</span>
            <strong>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</strong>
        </div>

        <div class="footer">
            <div class="signature">
                <p>Orang Tua / Wali,</p>
                <span>Tanda Tangan</span>
            </div>
            <div class="signature">
                <p>Petugas / Teller,</p>
                <span>{{ $transaction->user->name ?? 'Admin' }}</span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; font-size: 11px; color: #888;">
            Dicetak otomatis oleh Sistem Keuangan Al Marjan<br>
            {{ date('d/m/Y H:i:s') }}
        </div>
    </div>

    <button class="btn-print" onclick="window.print()">Cetak Bukti (Ctrl+P)</button>

</body>
</html>
