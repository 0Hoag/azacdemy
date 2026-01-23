document.addEventListener('DOMContentLoaded', function() {
    // Tìm select khóa học trong form CF7
    var courseSelect = document.querySelector('select[name="course-name"]');
    if (!courseSelect) return;
    // ⚠️ IMPORTANT:
    // Không tự thay đổi options của select bằng JS vì CF7 validate theo schema enum lúc render form.
    // Nếu JS thay options khác với schema -> sẽ báo "Undefined value was submitted through this field."
    
    // Tạo div để hiển thị thời gian và chọn lịch
    var timeDisplay = document.createElement('div');
    timeDisplay.id = 'cf7-course-time-display';
    // Style đơn giản, không đóng khung alert
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
    courseSelect.addEventListener('change', function() {
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
                    // Set giá trị mặc định cho hidden input là schedule đầu tiên
                    scheduleInput.value = data.data.schedules[0].index;

                    // Tạo dropdown chọn lịch
                    html += '<div style="margin-bottom:5px; font-weight:600; font-size:14px; color:#333;">📅 Chọn lịch khai giảng:</div>';
                    html += '<select id="cf7_schedule_select" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px; color:#333; background:#fff;" onchange="document.querySelector(\'[name=\\\'cf7-course-schedule-index\\\']\').value = this.value;">';
                    
                    data.data.schedules.forEach(function(sch, index) {
                        html += `<option value="${sch.index}">
                            ${sch.label} (Khai giảng: ${sch.start_fmt})
                        </option>`;
                    });
                    html += '</select>';
                    
                } else if (data.data.start_date) {
                    // Trường hợp cũ (1 lịch) -> Hiển thị text như trước nhưng đẹp hơn
                    html = '<div style="padding:10px; background:#f8f9fa; border-radius:6px; color:#333; font-size:14px;">📅 <strong>Khai giảng:</strong> ' + data.data.start_date + '</div>';
                } else {
                    html = '<div style="font-style:italic; font-size:13px; color:#777;">(Chưa có lịch khai giảng)</div>';
                }
                
                timeDisplay.innerHTML = html;
                timeDisplay.style.display = 'block';
            } else {
                timeDisplay.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            timeDisplay.style.display = 'none';
        });
    });
    
    // Trigger change nếu đã có giá trị được chọn
    if (courseSelect.value) {
        courseSelect.dispatchEvent(new Event('change'));
    }
});
