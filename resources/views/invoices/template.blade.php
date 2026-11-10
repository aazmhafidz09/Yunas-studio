<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Invoice #{{ $transaksi->receipt_code }}</title>

    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            text-align: center;
            color: #777;
        }

        body h1 {
            font-weight: 300;
            margin-bottom: 0px;
            padding-bottom: 0px;
            color: #000;
        }

        body h3 {
            font-weight: 300;
            margin-top: 10px;
            margin-bottom: 20px;
            font-style: italic;
            color: #555;
        }

        body a {
            color: #06f;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
            text-align: left;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }

        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }

        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="4">
                    <table>
                        <tr>
                            <td class="title">
                                @if(isset($company['logo']) && file_exists($company['logo']))
                                    <img src="{{ $company['logo'] }}" style="width: 100%; max-width: 150px" />
                                @else
                                    <h2>Yunas Studio</h2>
                                @endif
                            </td>

                            <td>
                                <strong>Invoice #:</strong> {{ $transaksi->receipt_code }}<br />
                                <strong>Created:</strong> {{ $transaksi->created_at->format('d F Y') }}<br />
                                <strong>Status:</strong> {{ strtoupper($transaksi->status) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="4">
                    <table>
                        <tr>
                            <td>
                                <strong>{{ $company['name'] ?? 'Yunas Studio' }}</strong><br />
                                {{ $company['address'] ?? 'Jalan Lingkar Selatan' }}<br />
                                Sukabumi, Indonesia
                            </td>

                            <td>
                                <strong>Billed To:</strong><br />
                                {{ $transaksi->customer_name }}<br />
                                {{ $transaksi->phone_number }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td style="width: 50%">Item Description</td>
                <td style="text-align: center; width: 10%">Qty</td>
                <td style="text-align: right; width: 20%">Price</td>
                <td style="text-align: right; width: 20%">Total</td>
            </tr>

            <tr class="item">
                <td>
                    <strong>{{ $transaksi->packet->product->name ?? 'Product' }}</strong><br>
                    <span style="color: #777; font-size: 0.9em;">Package: {{ $transaksi->packet->name }}</span>
                </td>
                <td style="text-align: center">1</td>
                <td style="text-align: right">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                <td style="text-align: right">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
            </tr>

            @if($transaksi->packet && $transaksi->packet->printOptions->isNotEmpty())
                @foreach($transaksi->packet->printOptions as $printOption)
                <tr class="item" style="background-color: #fdfdfd;">
                    <td style="padding-left: 25px; font-style: italic; color: #666;">
                        <span>&bull; Include Cetak {{ $printOption->name }}</span>
                    </td>
                    <td style="text-align: center; color: #666;">{{ $printOption->pivot->quantity }}</td>
                    <td style="text-align: right; color: #999; font-size: 0.85em;">(Included)</td>
                    <td style="text-align: right; color: #999;">-</td>
                </tr>
                @endforeach
            @endif

            @foreach($transaksi->additionals as $additional)
            <tr class="item">
                <td>
                    {{ $additional->name }} <span style="font-size: 0.8em; color: #888;">(Additional)</span>
                </td>
                <td style="text-align: center">{{ $additional->pivot->quantity }}</td>
                <td style="text-align: right">Rp {{ number_format($additional->pivot->price, 0, ',', '.') }}</td>
                <td style="text-align: right">Rp {{ number_format($additional->pivot->price * $additional->pivot->quantity, 0, ',', '.') }}</td>
            </tr>
            @endforeach

            <tr class="total" style="border-top: 2px solid #eee;">
                <td colspan="2"></td>
                <td style="text-align: right; padding-top: 10px;">Subtotal:</td>
                <td style="text-align: right; padding-top: 10px;">
                    @php
                        $subtotalPacket = $transaksi->packet->price;
                        $subtotalAdditional = $transaksi->additionals->sum(function($add) {
                            return $add->pivot->price * $add->pivot->quantity;
                        });
                        $grandTotalBeforeDiscount = $subtotalPacket + $subtotalAdditional;
                    @endphp
                    Rp {{ number_format($grandTotalBeforeDiscount, 0, ',', '.') }}
                </td>
            </tr>

            @if($transaksi->discount > 0)
            <tr class="total">
                <td colspan="2"></td>
                <td style="text-align: right; color: #d9534f;">Discount:</td>
                <td style="text-align: right; color: #d9534f;">- Rp {{ number_format($transaksi->discount, 0, ',', '.') }}</td>
            </tr>
            @endif

            <tr class="total">
                <td colspan="2"></td>
                <td style="text-align: right; font-size: 1.1em;"><strong>Total:</strong></td>
                <td style="text-align: right; font-size: 1.1em;"><strong>Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</strong></td>
            </tr>

            @if($transaksi->status == 'dp' && $transaksi->dp_amount > 0)
                <tr class="total">
                    <td colspan="2"></td>
                    <td style="text-align: right;">DP Paid:</td>
                    <td style="text-align: right;">Rp {{ number_format($transaksi->dp_amount, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td colspan="2"></td>
                    <td style="text-align: right; font-weight: bold; color: #d9534f;">Remaining:</td>
                    <td style="text-align: right; font-weight: bold; color: #d9534f;">
                        Rp {{ number_format($transaksi->total_price - $transaksi->dp_amount, 0, ',', '.') }}
                    </td>
                </tr>
            @elseif($transaksi->status == 'sudah dibayar')
                @if($transaksi->dp_amount > 0)
                    <tr class="total">
                        <td colspan="2"></td>
                        <td style="text-align: right;">DP (History):</td>
                        <td style="text-align: right;">Rp {{ number_format($transaksi->dp_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total">
                        <td colspan="2"></td>
                        <td style="text-align: right;">Settlement:</td>
                        <td style="text-align: right;">Rp {{ number_format($transaksi->total_price - $transaksi->dp_amount, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total">
                    <td colspan="2"></td>
                    <td style="text-align: right; font-weight: bold; color: #5cb85c;">Total Paid:</td>
                    <td style="text-align: right; font-weight: bold; color: #5cb85c;">
                        Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}
                    </td>
                </tr>
            @endif

        </table>

        @if($transaksi->note)
        <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #eee; text-align: left;">
            <strong>Note:</strong><br>
            <i style="color: #666;">{{ $transaksi->note }}</i>
        </div>
        @endif

        <div style="margin-top: 40px; font-size: 12px; color: #aaa; border-top: 1px solid #eee; padding-top: 10px;">
            Terima kasih telah mempercayakan momen spesial Anda kepada Yunas Studio.<br>
            Harap simpan bukti pembayaran ini sebagai referensi.
        </div>
    </div>
</body>
</html>