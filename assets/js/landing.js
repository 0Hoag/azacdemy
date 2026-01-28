document.addEventListener('DOMContentLoaded', function () {
    // Target the display container
    var timeDisplay = document.getElementById('course-time-display');

    if (!timeDisplay) return;

    // Dynamically find course key from the hidden input in the form
    var form = timeDisplay.closest('form');
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

    // Fallback if no course key found
    if (!courseKey) {
        console.warn('CF7 To Telegram: No course-name hidden input found.');
        return;
    }

    // Start fetching immediately
    fetchCourseInfo();

    function fetchCourseInfo() {
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

                    // ✅ CASE 1: Khóa học đã kết thúc
                    if (data.data.is_expired) {
                        html = '<div class="pro-schedule-box" style="border-color:#e74c3c; background:#fdf0ef;">' +
                            '<div class="pro-schedule-icon"><span class="calendar-icon" style="filter: grayscale(1);">🔒</span></div>' +
                            '<div class="pro-schedule-content">' +
                            '<div class="pro-schedule-label" style="color:#c0392b;">Khóa học đã kết thúc</div>' +
                            '<div class="pro-schedule-date" style="color:#7f8c8d; font-size:13px;">Vui lòng đợi khóa tiếp theo</div>' +
                            '</div></div>';

                        // Disable ALL inputs and submit button
                        if (form) {
                            var allInputs = form.querySelectorAll('input, select, textarea, button');
                            allInputs.forEach(function (el) {
                                // Skip the hidden inputs
                                if (el.type !== 'hidden') {
                                    el.disabled = true;
                                    el.style.opacity = '0.6';
                                    el.style.cursor = 'not-allowed';
                                }
                            });

                            var btn = form.querySelector('button[type="submit"], input[type="submit"]');
                            if (btn) {
                                btn.innerHTML = 'Đã kết thúc';
                                btn.value = 'Đã kết thúc';
                                btn.style.background = '#ccc';
                                btn.disabled = true;
                            }
                        }
                    }
                    // ✅ CASE 2: Có lịch học cụ thể
                    else if (hasSchedules) {
                        html = '<div class="schedule-selection-container" style="background:#fff; padding:15px; border-radius:10px; border:1px solid #eee; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">';
                        html += '<div class="pro-schedule-label" style="font-weight:700; color:#34495e; margin-bottom:12px; display:flex; align-items:center; gap:8px; font-size:15px;">📅 CHỌN LỊCH KHAI GIẢNG:</div>';

                        data.data.schedules.forEach(function (sch, index) {
                            var isChecked = index === 0 ? 'checked' : '';
                            var scheduleText = sch.label + ' (' + sch.start_fmt + ')';

                            html += `
                        <label class="schedule-radio-item" style="display:flex; align-items:center; margin-bottom:10px; cursor:pointer; padding:12px 14px; border:1px solid #e0e0e0; border-radius:8px; background:#fff; transition:all 0.2s ease;">
                            <input type="radio" name="schedule_option" value="${sch.index}" data-schedule-text="${scheduleText}" ${isChecked} style="margin:0 12px 0 0; width:20px; height:20px; accent-color:#0d6efd; cursor:pointer; flex-shrink:0;" onchange="updateHiddenInput(this)">
                            <div style="flex:1; display:flex; align-items:center; flex-wrap:wrap; gap:6px; line-height:1.5;">
                                <span style="font-weight:700; color:#2c3e50; font-size:15px;">${sch.label}</span> 
                                <span style="font-size:14px; color:#666;">(Khai giảng: ${sch.start_fmt})</span>
                            </div>
                        </label>`;
                        });
                        html += '</div>';

                        // Set hidden input value to the first schedule index by default
                        var firstSch = data.data.schedules[0];
                        updateHiddenInput({
                            value: firstSch.index,
                            dataset: { scheduleText: firstSch.label + ' (' + firstSch.start_fmt + ')' }
                        });

                    }
                    // ✅ CASE 3: Fallback (Chỉ có ngày start_date)
                    else if (data.data.start_date) {
                        // Single schedule - Display ONLY start time
                        html = `<div class="pro-schedule-box">
                        <div class="pro-schedule-icon">
                            <span class="calendar-icon">📅</span>
                        </div>
                        <div class="pro-schedule-content">
                            <div class="pro-schedule-label">Lịch khai giảng dự kiến</div>
                            <div class="pro-schedule-date">${data.data.start_date}</div>
                        </div>
                    </div>`;

                        // Clear hidden input just in case
                        if (form) {
                            var hiddenInput = form.querySelector('input[name="cf7-course-schedule-index"]');
                            if (hiddenInput) hiddenInput.value = '';

                            // Set course-time text directly for single schedule
                            var dateInput = form.querySelector('input[name="course-time"]');
                            if (dateInput) dateInput.value = data.data.start_date;
                        }

                    } else {
                        html = '<div class="no-schedule">Hiện chưa có lịch khai giảng mới.</div>';
                    }

                    timeDisplay.innerHTML = html;
                } else {
                    // If course not found in DB, try to display nothing or a friendly message?
                    // User criticized "Error loading data", so let's be subtle or just show empty if not critical.
                    // But better to show error so they know why it's empty.
                    timeDisplay.innerHTML = '<div class="error-text">Chưa có lịch khai giảng.</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                timeDisplay.innerHTML = '<div class="error-text">Đã xảy ra lỗi kết nối.</div>';
            });
    }

    // Helper function to update hidden input
    window.updateHiddenInput = function (radioOrObj) {
        if (!form) return;

        // 1. Update Schedule Index (để code backend xử lý logic)
        var hiddenInput = form.querySelector('input[name="cf7-course-schedule-index"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'cf7-course-schedule-index';
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = radioOrObj.value;

        // 2. Update Readable Text (để gửi mail) -> target field [text course-time]
        var textInput = form.querySelector('input[name="course-time"]');
        if (textInput && radioOrObj.dataset && radioOrObj.dataset.scheduleText) {
            textInput.value = radioOrObj.dataset.scheduleText;
        }
    };

});
