document.addEventListener('DOMContentLoaded', function () {
    // Tìm TẤT CẢ các select khóa học trong trang (hỗ trợ nhiều form)
    var courseSelects = document.querySelectorAll('select[name="course-name"]');

    courseSelects.forEach(function (courseSelect) {
        // ⚠️ IMPORTANT:
        // Không tự thay đổi options của select bằng JS vì CF7 validate theo schema enum lúc render form.

        // Scope to the current form
        var form = courseSelect.closest('form');
        if (!form) return;

        // Tạo div để hiển thị thời gian và chọn lịch
        var timeDisplay = document.createElement('div');
        timeDisplay.className = 'cf7-course-time-display'; // Use class instead of ID for uniqueness
        timeDisplay.style.cssText = 'margin-top: 10px; display: none;';

        // Tạo input hidden để lưu schedule index
        var scheduleInput = document.createElement('input');
        scheduleInput.type = 'hidden';
        scheduleInput.name = 'cf7-course-schedule-index';
        scheduleInput.value = '';
        courseSelect.parentNode.appendChild(scheduleInput);

        // Chèn div vào sau select
        courseSelect.parentNode.insertBefore(timeDisplay, courseSelect.nextSibling);

        // Xử lý khi chọn khóa học
        courseSelect.addEventListener('change', function () {
            var selectedValue = this.value;
            if (!selectedValue) {
                timeDisplay.style.display = 'none';
                scheduleInput.value = '';
                return;
            }

            var courseKey = selectedValue;

            // Hiển thị loading (dạng text nhỏ)
            timeDisplay.style.display = 'block';
            timeDisplay.innerHTML = '<span style="font-size:13px; color:#666;">⏳ Đang tải lịch học...</span>';
            scheduleInput.value = ''; // Reset schedule

            // Gọi AJAX để lấy thông tin khóa học
            var formData = new FormData();
            formData.append('action', 'cf7_get_course_info');
            formData.append('course_key', courseKey);

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
                            // Set giá trị mặc định cho hidden input là schedule đầu tiên
                            var firstSch = data.data.schedules[0];
                            scheduleInput.value = firstSch.index;

                            // Set text giá trị mặc định cho input course-time NẾU CÓ TRONG FORM NÀY
                            var courseTimeInput = form.querySelector('input[name="course-time"]');
                            if (courseTimeInput) {
                                courseTimeInput.value = firstSch.label + ' (' + firstSch.start_fmt + ')';
                            }

                            // Tạo list chọn lịch (Radio Button style)
                            html += '<div style="background:#fff; padding:15px; border-radius:10px; border:1px solid #eee; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">';
                            html += '<div style="margin-bottom:12px; font-weight:700; font-size:15px; color:#333; display:flex; align-items:center; gap:8px;">📅 CHỌN LỊCH KHAI GIẢNG:</div>';
                            html += '<div style="display:flex; flex-direction:column; gap:8px;">';

                            data.data.schedules.forEach(function (sch, index) {
                                var isChecked = index === 0 ? 'checked' : '';
                                var scheduleText = sch.label + ' (' + sch.start_fmt + ')';
                                // Unique name per form instance to prevent conflicts
                                var radioName = 'cf7_std_schedule_opt_' + Math.floor(Math.random() * 100000);

                                html += `
                            <label style="display:flex; align-items:center; cursor:pointer; padding:12px 14px; border:1px solid #e0e0e0; border-radius:8px; background:#fff; transition:all 0.2s ease; position:relative;">
                                <input type="radio" name="${radioName}" value="${sch.index}" data-schedule-text="${scheduleText}" ${isChecked} style="margin:0 12px 0 0; width:20px; height:20px; accent-color:#0d6efd; cursor:pointer; flex-shrink:0;">
                                <div style="flex:1; display:flex; align-items:center; flex-wrap:wrap; gap:6px; line-height:1.5;">
                                    <span style="font-weight:700; color:#2c3e50; font-size:15px;">${sch.label}</span> 
                                    <span style="font-size:14px; color:#666;">(Khai giảng: ${sch.start_fmt})</span>
                                </div>
                            </label>`;
                            });
                            html += '</div></div>';

                        } else if (data.data.start_date) {
                            html = '<div style="padding:10px; background:#f8f9fa; border-radius:6px; color:#333; font-size:14px;">📅 <strong>Khai giảng:</strong> ' + data.data.start_date + '</div>';

                            var courseTimeInput = form.querySelector('input[name="course-time"]');
                            if (courseTimeInput) {
                                courseTimeInput.value = data.data.start_date;
                            }
                        } else {
                            html = '<div style="font-style:italic; font-size:13px; color:#777;">(Chưa có lịch khai giảng)</div>';
                        }

                        timeDisplay.innerHTML = html;
                        timeDisplay.style.display = 'block';

                        // Add event listeners for radio buttons
                        if (hasSchedules) {
                            var radios = timeDisplay.querySelectorAll('input[type="radio"]');
                            radios.forEach(function (radio) {
                                radio.addEventListener('change', function () {
                                    scheduleInput.value = this.value;

                                    var courseTimeInput = form.querySelector('input[name="course-time"]');
                                    if (courseTimeInput && this.dataset.scheduleText) {
                                        courseTimeInput.value = this.dataset.scheduleText;
                                    }
                                });
                            });
                        }
                    } else {
                        timeDisplay.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    timeDisplay.style.display = 'none';
                });
        });

        // Trigger change nếu đã có giá trị
        if (courseSelect.value) {
            courseSelect.dispatchEvent(new Event('change'));
        }
    });
});
