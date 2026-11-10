<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $transaksi->receipt_code }}</title>
    <style>
        /* --- 1. CONFIG PAGE --- */
        @page {
            /* Tinggi diset 9.3in (sedikit kurang dari fisik 9.5in) 
               agar browser tidak mendeteksi 'overflow' ke halaman 2 */
            size: 11in 9.2in landscape;
            margin: 0; 
        }

        /* --- 2. TYPOGRAPHY & LAYOUT --- */
        body {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            /* UKURAN FONT DIPERBESAR */
            font-size: 11pt; 
            color: #000;
            margin: 0;
            
            /* Padding Atas 15mm (Sesuai request agar tidak terpotong)
               Padding Bawah 0 (Agar muat) */
            padding: 15mm 10mm 0 10mm; 
            
            width: 225mm;
            /* KUNCI AGAR TIDAK MUNCUL HALAMAN 2 */
            height: 90vh; 
            overflow: hidden; 
            
            box-sizing: border-box;
        }

        /* --- UTILITIES --- */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .uppercase { text-transform: uppercase; }
        
        /* Garis Double & Dashed yang lebih rapat */
        .dashed { border-bottom: 1px dashed #000; margin: 2px 0; }
        .double { border-bottom: 3px double #000; margin: 2px 0; }

        /* --- HEADER SECTION (DIPADATKAN) --- */
        .header-container {
            display: flex;
            align-items: center;
            margin-bottom: 5px; /* Jarak header ke konten dikurangi */
            padding-bottom: 5px;
            border-bottom: 3px double #000;
        }
        
        .logo-box {
            width: 70px; /* Sedikit diperkecil agar hemat tempat */
            height: 70px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            /* border: 1px dashed #ccc; */ /* Aktifkan jika ingin lihat kotak logo */
        }
        
        .logo-box img {
            max-width: 100%;
            max-height: 100%;
            filter: grayscale(100%) contrast(150%);
        }

        .company-info { flex: 1; }
        /* Font Judul Lebih Besar */
        .company-info h1 { margin: 0; font-size: 20pt; letter-spacing: 1px; line-height: 1; }
        .company-info p { margin: 0; font-size: 10pt; line-height: 1.2; }

        .invoice-title {
            text-align: right;
            border: 2px solid #000;
            padding: 2px 8px;
            display: inline-block;
        }
        .invoice-title h2 { margin: 0; font-size: 16pt; }

        /* --- INFO GRID (DIPADATKAN) --- */
        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 10pt; /* Font info diperbesar */
        }
        .info-col { width: 48%; }
        .info-row { display: flex; margin-bottom: 0px; } /* Hilangkan margin antar baris */
        .label { width: 90px; }
        .sep { width: 10px; }
        .val { flex: 1; }

        /* --- TABLE ITEMS --- */
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th {
            text-align: left;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 3px 0; /* Padding diperkecil */
            text-transform: uppercase;
            font-size: 11pt;
        }
        td { 
            padding: 2px 0; /* Padding diperkecil agar muat banyak */
            vertical-align: top; 
            font-size: 11pt;
        }

        /* --- FOOTER SECTION --- */
        .footer-container {
            display: flex;
            margin-top: 10px; /* Jarak ke footer dikurangi */
            font-size: 11pt;
        }
        
        .signature-area {
            width: 55%;
            display: flex;
            padding-top: 5px;
        }
        .sign-box {
            text-align: center;
            width: 140px;
            margin-right: 20px;
        }
        .sign-space { height: 40px; } /* Ruang ttd dikurangi dikit agar muat */

        .totals-area { width: 45%; }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }
        .grand-total {
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            padding: 3px 0;
            margin-top: 3px;
            font-size: 13pt; /* Grand total lebih besar */
            font-weight: 900;
        }

        @media print {
            .no-print { display: none !important; }
            /* Ini script PENTING untuk memotong halaman ke-2 */
            html, body { height: 99%; overflow: hidden; } 
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-weight: bold; cursor: pointer;">🖨️ PRINT</button>
    </div>

    <div class="header-container">
        <div class="logo-box">
            <img src="{{ asset('assets/images/yunas_dark.png') }}" alt="Logo">
        </div>
        
        <div class="company-info">
            <h1 class="uppercase">YUNAS STUDIO</h1>
            <p>Jl. Lingkar Selatan, Kec. Baros, Kota Sukabumi, Jawa Barat 43166</p>
            <p>HP: 0812-3506-3247 | Email: yunas.studio22@gmail.com</p>
        </div>
        
        <div style="align-self: flex-start;">
            <div class="invoice-title">
                <h2>INVOICE</h2>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-col">
            <div class="info-row">
                <span class="label">Customer</span><span class="sep">:</span><span class="val uppercase">{{ $transaksi->customer_name }}</span>
            </div>
            <div class="info-row">
                {{-- UPGRADE: Dynamic Payment Type --}}
                <span class="label">Metode</span><span class="sep">:</span><span class="val">{{ $transaksi->payment_type == 'none' ? '-' : $transaksi->payment_type }}</span>
            </div>
        </div>
        <div class="info-col">
            <div class="info-row">
                <span class="label">No. Inv</span><span class="sep">:</span><span class="val">{{ $transaksi->receipt_code }}</span>
            </div>
            <div class="info-row">
                <span class="label">Tanggal</span><span class="sep">:</span><span class="val">{{ $transaksi->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Kasir</span><span class="sep">:</span><span class="val">{{ auth()->user()->name }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">NO</th>
                <th style="width: 45%">DESKRIPSI</th>
                <th style="width: 10%; text-align: center;">QTY</th>
                <th style="width: 20%; text-align: right;">HARGA</th>
                <th style="width: 20%; text-align: right;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            
            @if($transaksi->packet)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>
                    {{ $transaksi->packet->name }}
                    @if($transaksi->packet->combined_defaults->isNotEmpty())
                        <br><span style="font-size: 10pt; font-weight: normal;">
                        @foreach($transaksi->packet->combined_defaults as $d)
                           + {{ $d->quantity }}x {{ $d->name }} 
                        @endforeach
                        </span>
                    @endif
                </td>
                <td class="text-center">1</td>
                <td class="text-right">{{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
            </tr>
            @endif

            @foreach($transaksi->additionals as $item)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $item->name }}</td>
                <td class="text-center">{{ $item->pivot->quantity }}</td>
                <td class="text-right">{{ number_format($item->pivot->price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->pivot->price * $item->pivot->quantity, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-container">
        <div class="signature-area">
            <div class="sign-box">
                <p>Penerima,</p>
                <div class="sign-space"></div>
                <p>( .......... )</p>
            </div>
            <div class="sign-box">
                <p>Hormat Kami,</p>
                <div class="sign-space"></div>
                <p><strong>{{ auth()->user()->name }}</strong> </p>
            </div>
        </div>

        <div class="totals-area">
            <div class="total-row">
                <span>Subtotal</span>
                <span>Rp {{ number_format($transaksi->total_price + $transaksi->discount, 0, ',', '.') }}</span>
            </div>
            @if($transaksi->discount > 0)
            <div class="total-row">
                <span>Discount</span>
                <span>- Rp {{ number_format($transaksi->discount, 0, ',', '.') }}</span>
            </div>
            @endif
            
            <div class="total-row grand-total">
                <span>GRAND TOTAL</span>
                <span>Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</span>
            </div>

            @php $dp = $transaksi->dp_amount ?? 0; @endphp
            @if($dp > 0)
            <div class="total-row" style="margin-top: 5px;">
                <span>Bayar (DP)</span>
                <span>Rp {{ number_format($dp, 0, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>Sisa/Pelunasan</span>
                <span>Rp {{ number_format($transaksi->total_price - $dp, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>
    </div>

    <div style="text-align: center; margin-top: 10px; font-size: 9pt; font-weight: normal; border-top: 1px dashed #000; padding-top: 5px;">
        Terima Kasih Atas Kepercayaan Anda Kepada Kami - Yunas Studio
    </div>

</body>
</html>