<?php

// Hook to add scripts and styles for the landing page form
add_action('wp_footer', 'cf7_landing_google_page_scripts');

function cf7_landing_google_page_scripts() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Target the specific checkbox for showing course time
        var showTimeCheckbox = document.getElementById('show-course-time');
        var timeDisplay = document.getElementById('course-time-display');
        
        if (!showTimeCheckbox || !timeDisplay) return;

        // Hardcoded course key for this landing pages
        var courseKey = 'google-ads';

        showTimeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                // Show loading state
                timeDisplay.style.display = 'block';
                timeDisplay.innerHTML = '<span class="loading-text">⏳ Đang tải lịch khai giảng...</span>';
                
                // Call AJAX to get course info
                var formData = new FormData();
                formData.append('action', 'cf7_get_course_info');
                formData.append('course_key', courseKey);
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
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
                            var form = showTimeCheckbox.closest('form');
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
    </script>

    <style>
    /* Styling for the checkbox and time display area */
    .cf7-checkbox-wrapper {
        margin-bottom: 25px;
        display: flex;
        align-items: center;
    }

    .cf7-checkbox-label {
        display: inline-flex !important;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        font-weight: 600;
        color: #333;
        user-select: none;
        font-size: 15px;
    }
    
    .cf7-checkbox-label input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 22px !important;
        height: 22px !important;
        border: 2px solid #0064E0;
        border-radius: 4px;
        cursor: pointer;
        position: relative;
        background: #fff;
        transition: all 0.2s ease;
        margin: 0 !important;
    }

    .cf7-checkbox-label input[type="checkbox"]:checked {
        background: #0064E0;
    }

    .cf7-checkbox-label input[type="checkbox"]:checked::after {
        content: "✔";
        color: #fff;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 14px;
        line-height: 1;
    }

    .cf7-course-time-box {
        margin-top: 15px;
        padding: 20px;
        background: #f0f7ff;
        border: 1px solid #cce5ff;
        border-radius: 12px;
        font-size: 15px;
        color: #333;
        animation: fadeIn 0.4s ease;
        box-shadow: 0 4px 12px rgba(0, 100, 224, 0.08);
        text-align: left; /* Căn trái nội dung */
    }

    .schedule-list-title {
        font-weight: 700;
        margin-bottom: 12px;
        color: #0064E0;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: flex-start; /* Căn trái tiêu đề */
        gap: 8px;
    }

    .schedule-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: inline-block; /* Để căn giữa hoạt động tốt hơn */
        width: 100%;
        text-align: left;
    }

    .schedule-list li {
        padding: 10px 15px;
        background: #fff;
        border: 1px solid #e1eeff;
        border-radius: 8px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .schedule-list li:last-child {
        margin-bottom: 0;
    }

    .schedule-duration {
        font-size: 13px;
        color: #666;
        background: #f8f9fa;
        padding: 2px 8px;
        border-radius: 4px;
    }

    .single-schedule {
        line-height: 1.8;
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e1eeff;
        display: inline-block;
        min-width: 250px;
    }

    .loading-text {
        color: #666;
        font-style: italic;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .error-text {
        color: #dc3545;
        font-weight: 500;
    }

    .no-schedule {
        color: #777;
        font-style: italic;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>
    <?php
}
