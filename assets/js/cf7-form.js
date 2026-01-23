// Dùng wpcf7submit để bắt cả trạng thái mail_skipped (do skip mail)
document.addEventListener('wpcf7submit', function(event) {
    var form = event.target;
    if (!form || !event.detail) return;

    // Chỉ áp dụng cho form có field course-name
    if (!form.querySelector('[name="course-name"]')) {
        return;
    }

    // Chỉ xử lý khi submit thành công hoặc mail_skipped (do skip mail)
    var okStatus = ['mail_sent', 'mail_skipped'];
    if (okStatus.indexOf(event.detail.status) === -1) {
        return;
    }

    // Reset form
    form.reset();

    // ✅ FIX: Hiển thị thông báo thành công với delay để đảm bảo CF7 đã render xong
    setTimeout(function() {
        var response = form.querySelector('.wpcf7-response-output');
        if (response) {
            // Xóa các class lỗi
            response.classList.remove('wpcf7-validation-errors', 'wpcf7-mail-sent-ng', 'wpcf7-aborted');
            response.classList.add('wpcf7-mail-sent-ok');
            
            // ✅ FIX: Set text content và đảm bảo hiển thị
            response.textContent = '✅ Cảm ơn bạn! Đăng ký đã được gửi thành công.';
            response.innerHTML = '✅ Cảm ơn bạn! Đăng ký đã được gửi thành công.';
            
            // ✅ FIX: Đảm bảo hiển thị và bỏ aria-hidden
            response.style.display = 'block';
            response.style.visibility = 'visible';
            response.style.opacity = '1';
            response.removeAttribute('aria-hidden');
            response.setAttribute('aria-hidden', 'false');
            
            // ✅ FIX: Scroll đến thông báo để user thấy rõ
            response.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, 100);
}, false);
