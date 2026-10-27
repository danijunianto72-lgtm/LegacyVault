<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Kata Sandi LegacyVault</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f8fafc; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 32px; border: 1px solid #e2e8f0;">
        
        <h2 style="margin-top: 0; color: #0f172a; font-size: 18px;">Pemulihan Kata Sandi LegacyVault</h2>

        <p style="font-size: 14px;">Halo <strong>{{ $user->name }}</strong>,</p>

        <p style="font-size: 14px; color: #334155;">
            Kami menerima permintaan untuk mengatur ulang kata sandi akun <strong>LegacyVault</strong> Anda. Silakan klik tombol di bawah ini untuk membuat kata sandi baru:
        </p>

        <div style="text-align: center; margin: 28px 0;">
            <a href="{{ $resetUrl }}" 
               style="display: inline-block; background-color: #10b981; color: #022c22; font-weight: bold; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px;">
                Atur Ulang Kata Sandi
            </a>
        </div>

        <p style="font-size: 13px; color: #64748b;">
            Tautan pemulihan ini hanya berlaku selama <strong>60 menit</strong>. Jika Anda tidak merasa melakukan permintaan ini, abaikan saja email ini dan akun Anda tetap aman.
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">

        <p style="font-size: 11px; color: #94a3b8; word-break: break-all;">
            Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:<br>
            <a href="{{ $resetUrl }}" style="color: #2563eb;">{{ $resetUrl }}</a>
        </p>

        <p style="margin-top: 20px; font-size: 13px; color: #475569;">
            Hormat kami,<br>
            <strong>Tim Keamanan LegacyVault</strong>
        </p>
    </div>
</body>
</html>