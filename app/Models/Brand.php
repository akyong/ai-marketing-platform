<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'website_url',
        'logo_url',
        'brand_color',
        'description',
        'industry',
        'target_audience',
        'tone_of_voice',
        'knowledge_base',
        'custom_rules',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function getKnowledgeBaseAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }

        return "1. Waperu.com adalah platform toko online mandiri yang membebaskan seller dari ketergantungan marketplace.\n" .
            "2. Solusi Branding Sosmed: Seller bisa jualan & membangun brand sendiri di semua sosial media (Instagram, TikTok, Threads, Facebook).\n" .
            "3. 0% Komisi: Keuntungan 100% milik seller, pembayaran QRIS & COD langsung ke rekening tanpa potongan admin.\n" .
            "4. Landing Page Instan & Scrape Produk: Bikin toko online cepat dan bisa scrape/import produk tanpa repot input manual.\n" .
            "5. Referensi Panduan Resmi: https://waperu.com/guides";
    }

    public function getCustomRulesAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }

        return "1. KELUARKAN HANYA TEKS CAPTION/POSTINGAN AKHIR yang SIAP DITERBITKAN DENGAN RAPI.\n" .
            "2. DILARANG KERAS menyertakan kata pengantar AI seperti 'Halo sebagai AI', 'Berikut analisis strategis', 'Panduan visual slide 1', 'Format:', atau penjelasan internal AI.\n" .
            "3. DILARANG MENGAKU ATAU MENYEBUTKAN DIRI SEBAGAI AI, BOT, ATAU AUTOMATION. Menulislah 100% sebagai tim resmi / owner Waperu yang manusiawi, empati, dan solutif.\n" .
            "4. DILARANG menggunakan tanda tebal markdown asterisks seperti **kata** atau ### Header (karena sosmed seperti Threads, Facebook, Instagram tidak mendukung sintaks markdown).\n" .
            "5. Gunakan EMOJI yang menarik (🚀, 💸, ✅, 👋, 📦) dan buat paragraf yang rapi, mudah dibaca, serta kaitkan dengan keunggulan brand Anda.\n" .
            "6. Sertakan Call to Action (CTA) ke website brand dan Hashtag relevan di akhir postingan.";
    }
}
