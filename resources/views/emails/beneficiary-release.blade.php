<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penyerahan Amanat Aset Digital</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f8fafc; padding: 20px;">
    <div style="max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 30px; border: 1px solid #e2e8f0;">
        
        <p style="font-size: 15px;">Halo <strong>{{ $beneficiary->name }}</strong>,</p>

        <p style="font-size: 14px; text-align: justify;">
            Pesan otomatis ini dikirimkan oleh sistem <strong>LegacyVault</strong> secara otomatis karena Bapak/Ibu <strong>{{ $owner->name }}</strong> tidak merespons konfirmasi keaktifan berkala dalam batas waktu yang telah ditentukan (melewati prosedur pengingat bertahap).
        </p>

        <p style="font-size: 14px; text-align: justify;">
            Sesuai dengan instruksi dan amanat yang telah diatur oleh beliau semasa hidup/aktif, berikut adalah rincian aset digital, dokumen, serta kredensial penting yang diwariskan secara khusus kepada Anda:
        </p>

        <div style="margin: 20px 0;">
            @foreach($formattedAssets as $idx => $asset)
                <div style="background-color: #f1f5f9; border-left: 4px solid #0f172a; padding: 16px; margin-bottom: 16px; border-radius: 4px;">
                    <p style="margin: 0 0 8px 0; font-size: 15px; font-weight: bold; color: #0f172a;">
                        {{ $idx + 1 }}. {{ $asset['name'] }}
                    </p>
                    <p style="margin: 4px 0; font-size: 14px;"><strong>2. Kategori:</strong> {{ $asset['category'] }}</p>
                    <p style="margin: 4px 0; font-size: 14px;"><strong>3. Ahli Waris Ditunjuk:</strong> {{ $beneficiary->name }}</p>
                    <p style="margin: 4px 0; font-size: 14px;"><strong>4. Masa Berlaku Aset:</strong> {{ $asset['validity'] }}</p>
                    <p style="margin: 4px 0; font-size: 14px; white-space: pre-wrap;"><strong>5. Isi Aset & Kredensial:</strong> {{ $asset['content'] }}</p>
                    <p style="margin: 4px 0; font-size: 14px;">
                        <strong>6. Dokumen Lampiran:</strong> 
                        @if($asset['attachment_url'])
                            <a href="{{ $asset['attachment_url'] }}" target="_blank" style="color: #2563eb; text-decoration: underline;">Unduh Berkas Lampiran</a>
                        @else
                            Tidak Ada
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 6px; margin-top: 25px;">
            <p style="margin: 0 0 8px 0; font-weight: bold; font-size: 14px; color: #334155;">Informasi Penting & Petunjuk Selanjutnya:</p>
            <ol style="margin: 0; padding-left: 20px; font-size: 13px; color: #475569;">
                <li style="margin-bottom: 6px;">Harap simpan rincian informasi dan dokumen lampiran di atas dengan aman dan bersifat rahasia.</li>
                <li style="margin-bottom: 6px;">Jika dokumen memerlukan proses verifikasi atau klaim fisik (seperti rekening bank, polis asuransi, atau sertifikat), Anda dapat membawa salinan berkas ini ke instansi/lembaga terkait sesuai dengan prosedur hukum yang berlaku.</li>
                <li style="margin-bottom: 0;">Apabila terdapat kekeliruan atau Anda membutuhkan bantuan teknis dalam mengunduh dokumen terenkripsi, silakan hubungi tim dukungan kami di <a href="mailto:support@legacyvault.com" style="color: #2563eb;">support@legacyvault.com</a> atau kunjungi <a href="https://legacyvault.com/help" style="color: #2563eb;">legacyvault.com/help</a>.</li>
            </ol>
        </div>

        <p style="margin-top: 25px; font-size: 13px; color: #64748b; font-style: italic;">
            Pesan ini disampaikan secara sah dan otomatis berdasarkan pengaturan amanat dari pemilik akun yang bersangkutan.
        </p>

        <p style="margin-top: 20px; font-size: 14px; line-height: 1.4;">
            Hormat kami,<br>
            <strong>Tim Otomasi Sistem LegacyVault</strong><br>
            <span style="font-size: 12px; color: #64748b;">Sistem Penyimpanan & Penyerahan Aset Digital Terenkripsi</span>
        </p>

    </div>
</body>
</html>