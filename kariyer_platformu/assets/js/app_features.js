/**
 * KBÜ Kariyer Portalı - Uygulama Dinamik Özellikleri (app_features.js)
 * Kapsam: AJAX Forum İşlemleri, Profil Yetenek Yönetimi, Form Dinamikleri
 */

$(document).ready(function() {

    // --- 1. FORUM İŞLEMLERİ (AJAX) ---
    
    // Yeni Konu Açma (forum.php)
    $('#newPostForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        $.ajax({
            url: 'forum_operations.php',
            type: 'POST',
            data: $form.serialize() + '&action=create_post',
            success: function(response) {
                if(response.status === "success") {
                    location.reload();
                } else {
                    alert("Hata: " + (response.error_msg || "Konu paylaşılamadı."));
                }
            }
        });
    });

    // Yeni Yorum Ekleme (post_detail.php)
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#submitBtn');
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Gönderiliyor...');
        
        $.ajax({
            url: 'forum_operations.php',
            type: 'POST',
            data: $(this).serialize() + '&action=add_comment',
            success: function(response) {
                if(response.status === "success") {
                    location.reload();
                } else {
                    alert("Hata: Yorum eklenemedi.");
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert("Sunucu hatası oluştu.");
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // --- 2. PROFİL YETENEK YÖNETİMİ (profile.php) ---
    
    const skillInput = document.getElementById('skill_input');
    const addSkillBtn = document.getElementById('add_skill_btn');
    const skillsContainer = document.getElementById('skills_container');
    const hiddenSkillsInput = document.getElementById('hidden_skills');

    if (skillInput && addSkillBtn) {
        let skillsArray = hiddenSkillsInput.value ? hiddenSkillsInput.value.split(',').map(s => s.trim()).filter(s => s !== '') : [];

        function renderSkills() {
            skillsContainer.innerHTML = '';
            skillsArray.forEach((skill, index) => {
                const badge = document.createElement('div');
                badge.className = 'skill-badge';
                badge.innerHTML = `${skill} <i class="fa-solid fa-circle-xmark remove-skill" style="cursor:pointer; margin-left:5px; color:#C8102E;" onclick="removeSkill(${index})"></i>`;
                skillsContainer.appendChild(badge);
            });
            hiddenSkillsInput.value = skillsArray.join(',');
        }

        window.removeSkill = function(index) {
            skillsArray.splice(index, 1);
            renderSkills();
        };

        addSkillBtn.addEventListener('click', function() {
            const val = skillInput.value.trim();
            if (val) {
                const newSkills = val.split(',').map(s => s.trim()).filter(s => s !== '');
                newSkills.forEach(s => {
                    if(!skillsArray.some(existing => existing.toLowerCase() === s.toLowerCase())) {
                        skillsArray.push(s);
                    }
                });
                skillInput.value = ''; 
                renderSkills(); 
            }
        });

        skillInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                addSkillBtn.click();
            }
        });

        renderSkills(); // Sayfa yüklendiğinde mevcutları çiz
    }

    // --- 3. DENEYİM EKLEME DİNAMİKLERİ (add_experience.php) ---
    
    const currentJobCheck = document.getElementById('c');
    const endDateBox = document.getElementById('e_box');
    
    if (currentJobCheck && endDateBox) {
        currentJobCheck.onchange = function() {
            endDateBox.style.display = this.checked ? 'none' : 'block';
            if(this.checked) {
                const endDateInput = endDateBox.querySelector('input[name="end_date"]');
                if(endDateInput) endDateInput.value = '';
            }
        };
    }
});

// Yorum Silme Fonksiyonu (Global kapsamda olmalı çünkü onclick ile çağrılıyor)
function deleteComment(id) {
    if(confirm('Yorumu silmek istediğine emin misin?')) {
        $.ajax({
            url: 'forum_operations.php',
            type: 'POST',
            data: { action: 'delete_comment', comment_id: id },
            success: function(response) {
                if(response.status === "success") {
                    location.reload();
                } else {
                    alert("Silme hatası!");
                }
            }
        });
    }
}