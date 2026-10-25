<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 30px;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 28px;">
        <h2 style="color: #10b981; margin-top: 0;">Halo, {{ $user->name }}!</h2>
        <p style="font-size: 14px; line-height: 1.6; color: #cbd5e1;">
            Ini adalah pesan otomatis dari <strong>Sakelar Otomatis Brankas Warisan Digital</strong>. 
            Mohon segera lakukan <strong>konfirmasi bahwa Anda masih sehat</strong> dan aktif.
        </p>
        <div style="background-color: #0f172a; border-left: 4px solid #f59e0b; padding: 14px; margin: 20px 0; font-size: 13px; color: #fbbf24;">
            ⚠️ <strong>Peringatan Sakelar Otomatis:</strong><br>
            Apabila dalam waktu <strong>10 menit</strong> sejak siklus pengujian dimulai tidak ada respon konfirmasi dari Anda, maka seluruh Aset Digital di brankas Anda akan <strong>otomatis dikirimkan ke Email & WhatsApp masing-masing Ahli Waris</strong> yang terdaftar.
        </div>
        <div style="text-align: center; margin: 28px 0;">
            <a href="{{ $confirmUrl }}" style="background-color: #10b981; color: #022c22; padding: 14px 26px; font-weight: bold; text-decoration: none; border-radius: 10px; display: inline-block; font-size: 14px;">
                ✅ Konfirmasi Bahwa Saya Masih Sehat
            </a>
        </div>
        <p style="font-size: 12px; color: #94a3b8;">
            Setelah tombol di atas diklik, timer sakelar Anda akan otomatis direset kembali ke jadwal interval rutin Anda ({{ $user->check_in_interval_days ?: 180 }} hari).
        </p>
    </div>
</body>
</html>