<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 25px;">
    <div style="max-width: 640px; margin: 0 auto; background-color: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 28px;">
        <h2 style="color: #fbbf24; margin-top: 0;">Penyerahan Amanat Aset Warisan Digital</h2>
        <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6;">
            Yth. <strong>{{ $beneficiary->name }}</strong> ({{ $beneficiary->relationship ?? 'Ahli Waris' }}),<br>
            Berdasarkan protokol Sakelar Otomatis (<em>Dead Man's Switch</em>), <strong>{{ $owner->name }}</strong> tidak memberikan respon konfirmasi batas waktu. Berikut adalah rincian lengkap aset digital yang diamanatkan kepada Anda:
        </p>

        @php
            $ownerPlan = $owner->currentPlan();
            $activeSub = $owner->activeSubscription;
            $planExpiry = ($activeSub && $activeSub->ends_at) 
                ? \Carbon\Carbon::parse($activeSub->ends_at)->format('d M Y') 
                : 'Selamanya (Aktif)';
        @endphp

        @foreach($assets as $asset)
            @php
                // Ambil URL gambar/file lampiran jika ada
                $fileUrl = null;
                $rawPath = $asset->file_path ?? $asset->attachment_path ?? $asset->image_path ?? null;
                if ($rawPath) {
                    $fileUrl = str_starts_with($rawPath, 'http') 
                        ? $rawPath 
                        : \Illuminate\Support\Facades\Storage::disk('supabase')->url($rawPath);
                }

                // Dekripsi isi rahasia jika terenkripsi
                $rawContent = $asset->content ?? $asset->credentials ?? $asset->secret_data ?? $asset->description ?? '-';
                try {
                    $decryptedContent = \Illuminate\Support\Facades\Crypt::decryptString($rawContent);
                } catch (\Throwable $e) {
                    $decryptedContent = $rawContent;
                }
            @endphp

            <div style="background-color: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 18px; margin-bottom: 18px;">
                <h3 style="color: #10b981; margin: 0 0 10px 0; font-size: 16px;">
                    📦 {{ $asset->name ?? $asset->title ?? 'Aset Digital' }}
                </h3>
                <table style="width: 100%; font-size: 13px; color: #e2e8f0; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 5px 0; color: #94a3b8; width: 35%;">Kategori:</td>
                        <td style="padding: 5px 0; font-weight: bold;">{{ strtoupper($asset->category ?? $asset->type ?? 'Umum') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #94a3b8;">Ahli Waris Penerima:</td>
                        <td style="padding: 5px 0;">{{ $beneficiary->name }} ({{ $beneficiary->email }} / {{ $beneficiary->phone ?? '-' }})</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #94a3b8;">Masa Berlaku Paket/Aset:</td>
                        <td style="padding: 5px 0;">{{ $asset->expires_at ? \Carbon\Carbon::parse($asset->expires_at)->format('d M Y') : $planExpiry }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #94a3b8; vertical-align: top;">Isi / Kredensial Aset:</td>
                        <td style="padding: 8px; background: #1e293b; border-radius: 8px; font-family: monospace; white-space: pre-wrap;">{{ $decryptedContent }}</td>
                    </tr>
                </table>

                @if($fileUrl)
                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #334155;">
                        <p style="font-size: 12px; color: #94a3b8; margin: 0 0 8px 0;">📎 Lampiran Gambar / Dokumen Aset:</p>
                        <img src="{{ $fileUrl }}" alt="Lampiran Aset" style="max-width: 100%; max-height: 260px; border-radius: 8px; display: block; margin-bottom: 8px;">
                        <a href="{{ $fileUrl }}" target="_blank" style="color: #38bdf8; font-size: 12px; font-weight: bold;">🔗 Buka / Unduh Lampiran Asli</a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</body>
</html>