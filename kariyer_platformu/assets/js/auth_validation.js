/**
 * KBÜ Kariyer Portalı - Kimlik Doğrulama Validasyonları (auth_validation.js)
 * Kapsam: Kayıt ve Giriş Formu Gerçek Zamanlı Kontrolleri
 */

document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.querySelector('form[action="register.php"]') || document.querySelector('form');
    const loginForm = document.querySelector('form[action="login.php"]');

    // --- 1. KAYIT FORMU VALIDASYONU ---
    if (document.title.includes("Kayıt Ol")) {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            let errors = [];
            
            const firstName = form.querySelector('input[name="first_name"]').value.trim();
            const lastName = form.querySelector('input[name="last_name"]').value.trim();
            const email = form.querySelector('input[name="email"]').value.trim();
            const password = form.querySelector('input[name="password"]').value;
            const role = form.querySelector('select[name="role"]').value;

            // Boş Alan Kontrolü
            if (!firstName || !lastName || !email || !password || !role) {
                errors.push("Lütfen tüm zorunlu alanları doldurun.");
            }

            // İsim Uzunluğu Kontrolü
            if (firstName.length < 2 || lastName.length < 2) {
                errors.push("Ad ve soyad en az 2 karakter olmalıdır.");
            }

            // E-posta Format Kontrolü (KBÜ Özel)
            if (role === 'student') {
                const kbuPattern = /^[0-9]+@ogrenci\.karabuk\.edu\.tr$/;
                if (!kbuPattern.test(email)) {
                    errors.push("Öğrenciler sadece 'öğrencinumarası@ogrenci.karabuk.edu.tr' formatında kayıt olabilir.");
                }
            } else {
                const generalPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!generalPattern.test(email)) {
                    errors.push("Geçerli bir e-posta adresi giriniz.");
                }
            }

            // Şifre Gücü Kontrolü
            if (password.length < 6) {
                errors.push("Güvenliğiniz için şifreniz en az 6 karakter olmalıdır.");
            }

            // Hataları Göster ve Formu Durdur
            if (errors.length > 0) {
                e.preventDefault(); // Formun gönderilmesini engelle
                alert("Formda Hatalar Var:\n\n• " + errors.join("\n• "));
            }
        });
    }

    // --- 2. GİRİŞ FORMU VALIDASYONU ---
    if (document.title.includes("Giriş")) {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            const email = form.querySelector('input[name="email"]').value.trim();
            const password = form.querySelector('input[name="password"]').value;

            if (!email || !password) {
                e.preventDefault();
                alert("Lütfen e-posta ve şifrenizi giriniz.");
            }
        });
    }
});