<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice Booking</title>

    <style>
        body {
            font-family: sans-serif;
            color: #333;
            padding: 30px;
        }

        .header {
            margin-bottom: 30px;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
        }

        .subtitle {
            color: #777;
            margin-top: 5px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .label {
            color: #666;
            width: 40%;
        }

        .value {
            font-weight: bold;
            text-align: right;
        }

        .total {
            font-size: 20px;
            color: green;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            color: #777;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="title">Yunas Studio</div>
        <div class="subtitle">Invoice Booking Studio</div>
    </div>

    <div class="card">

        <table>
            <tr>
                <td class="label">Order ID</td>
                <td class="value">{{ $booking->order_id }}</td>
            </tr>

            <tr>
                <td class="label">Nama</td>
                <td class="value">{{ $booking->customer_name }}</td>
            </tr>

            <tr>
                <td class="label">WhatsApp</td>
                <td class="value">{{ $booking->phone_number }}</td>
            </tr>

            <tr>
                <td class="label">Produk</td>
                <td class="value">{{ $booking->packet->product->name ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Paket</td>
                <td class="value">{{ $booking->packet->name ?? '-' }}</td>
            </tr>

            <tr>
                <td class="label">Tanggal</td>
                <td class="value">
                    {{ \Carbon\Carbon::parse($booking->session_date)->translatedFormat('d F Y') }}
                </td>
            </tr>

            <tr>
                <td class="label">Waktu</td>
                <td class="value">{{ $booking->session_time }} WIB</td>
            </tr>

            <tr>
                <td class="label">Total Pembayaran</td>
                <td class="value total">
                    Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                </td>
            </tr>
        </table>

    </div>

    <div class="footer">
        Terima kasih telah melakukan booking di Yunas Studio
    </div>

</body>

</html>