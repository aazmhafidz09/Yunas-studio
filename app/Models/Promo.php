<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $table = 'promos';

    protected $fillable = [
        'name',
        'content',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shares()
    {
        return $this->hasMany(PromoShare::class, 'promo_id');
    }

    /**
     * Build the WhatsApp message text for a specific transaction.
     */
    public static function buildMessage(string $promoName, string $promoContent, Transaksi $transaksi): string
    {
        $customerName = trim((string) $transaksi->customer_name) ?: 'Pelanggan';
        $productName = $transaksi->packet?->product?->name ?? '-';
        $packetName = $transaksi->packet?->name ?? '-';
        $tanggal = $transaksi->created_at ? $transaksi->created_at->format('d M Y') : '-';
        $nota = $transaksi->receipt_code ?? '-';

        $replacements = [
            '{nama}' => $customerName,
            '{produk}' => $productName,
            '{paket}' => $packetName,
            '{tanggal}' => $tanggal,
            '{nota}' => $nota,
        ];

        $hasNamePlaceholder = str_contains($promoContent, '{nama}');
        $renderedContent = str_replace(array_keys($replacements), array_values($replacements), trim($promoContent));
        $renderedName = str_replace(array_keys($replacements), array_values($replacements), trim($promoName));

        $parts = [];
        if (!$hasNamePlaceholder) {
            $parts[] = "Halo Kak *{$customerName}*";
        }
        if ($renderedName !== '') {
            $parts[] = "*{$renderedName}*";
        }
        if ($renderedContent !== '') {
            $parts[] = $renderedContent;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Normalize Indonesian phone number to 62... format for WhatsApp API.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Build direct WhatsApp share URL for a transaction.
     */
    public static function buildWhatsappUrl(string $promoName, string $promoContent, Transaksi $transaksi): ?string
    {
        $phone = self::normalizePhone($transaksi->phone_number);
        if (!$phone) {
            return null;
        }

        $message = self::buildMessage($promoName, $promoContent, $transaksi);

        return "https://api.whatsapp.com/send?phone={$phone}&text=" . rawurlencode($message);
    }
}
