document.addEventListener('DOMContentLoaded', function() {
    // Target the specific checkbox for showing course time
    var showTimeCheckbox = document.getElementById('show-course-time');
    var timeDisplay = document.getElementById('course-time-display');
    
    if (!showTimeCheckbox || !timeDisplay) return;

    // Dynamically find course key from the hidden input in the form
    var form = showTimeCheckbox.closest('form');
    var courseKey = '';
    
    if (form) {
        var courseInput = form.querySelector('input[name="course-name"]');
        if (courseInput) {
            var val = courseInput.value.trim().toLowerCase();
            if (val.includes('facebook')) {
                courseKey = 'facebook-ads';
            } else if (val.includes('google')) {
                courseKey = 'google-ads';
            } else {
                courseKey = courseInput.value;
            }
        }
    }
    
    // Fallback if no course key found (though it should be there)
    if (!courseKey) {
        console.warn('CF7 To Telegram: No course-name hidden input found.');
        return;
    }

    showTimeCheckbox.addEventListener('change', function() {
        if (this.checked) {
            // Show loading state
            timeDisplay.style.display = 'block';
            timeDisplay.innerHTML = '<span class="loading-text">⏳ Đang tải lịch khai giảng...</span>';
            
            // Call AJAX to get course info
            var formData = new FormData();
            formData.append('action', 'cf7_get_course_info');
            formData.append('course_key', courseKey);
            
            // Use cf7_ajax_obj.ajax_url if available, otherwise default to standard WP AJAX URL
            var ajaxUrl = (typeof cf7_ajax_obj !== 'undefined') ? cf7_ajax_obj.ajax_url : '/wp-admin/admin-ajax.php';
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var html = '';
                    var hasSchedules = data.data.schedules && data.data.schedules.length > 0;
                    
                    if (hasSchedules) {
                        // Display list of schedules
                        html += '<div class="schedule-list-title">📅 Lịch khai giảng sắp tới:</div>';
                        html += '<ul class="schedule-list">';
                        data.data.schedules.forEach(function(sch) {
                            html += `<li>
                                <strong>${sch.label}:</strong> ${sch.start_fmt} 
                                <span class="schedule-duration">(${data.data.duration})</span>
                            </li>`;
                        });
                        html += '</ul>';

                        // ✅ FIX: Tự động set schedule đầu tiên (K1) vào hidden input để submit
                        if (form) {
                            var hiddenInput = form.querySelector('input[name="cf7-course-schedule-index"]');
                            if (!hiddenInput) {
                                hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'cf7-course-schedule-index';
                                form.appendChild(hiddenInput);
                            }
                            hiddenInput.value = data.data.schedules[0].index;
                        }
                    } else if (data.data.start_date) {
                        // Single schedule
                        html = `<div class="single-schedule">
                            📅 <strong>Khai giảng:</strong> ${data.data.start_date}
                            <br>⏱️ <strong>Thời lượng:</strong> ${data.data.duration}
                        </div>`;
                    } else {
                        html = '<div class="no-schedule">Hiện chưa có lịch khai giảng mới.</div>';
                    }
                    
                    timeDisplay.innerHTML = html;
                } else {
                    timeDisplay.innerHTML = '<div class="error-text">Không tìm thấy thông tin khóa học.</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                timeDisplay.innerHTML = '<div class="error-text">Đã xảy ra lỗi khi tải dữ liệu.</div>';
            });
        } else {
            // Hide display
            timeDisplay.style.display = 'none';
            timeDisplay.innerHTML = '';
        }
    });
});
