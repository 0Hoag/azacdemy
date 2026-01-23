document.addEventListener('DOMContentLoaded', function() {
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
                
                if (hasSchedules) {
                    // Display ONLY the start time of the first schedule as requested
                    var firstSchedule = data.data.schedules[0];
                    html = `<div class="pro-schedule-box">
                        <div class="pro-schedule-icon">
                            <span class="calendar-icon">📅</span>
                        </div>
                        <div class="pro-schedule-content">
                            <div class="pro-schedule-label">Lịch khai giảng dự kiến</div>
                            <div class="pro-schedule-date">${firstSchedule.start_fmt}</div>
                        </div>
                    </div>`;

                    // Set hidden input value to the first schedule index
                    updateHiddenInput({ value: firstSchedule.index });
                    
                } else if (data.data.start_date) {
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
    window.updateHiddenInput = function(radioOrObj) {
        if (!form) return;
        var hiddenInput = form.querySelector('input[name="cf7-course-schedule-index"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'cf7-course-schedule-index';
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = radioOrObj.value;
    };

});
