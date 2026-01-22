<?php

add_action('wpcf7_submit', 'cf7_send_to_telegram');

// ✅ FIX: Populate select field course-name từ database động
add_filter('wpcf7_form_tag', 'cf7_populate_course_select', 10, 2);
function cf7_populate_course_select($tag, $unused) {
    $original_is_array = is_array($tag);
    // CF7 có thể truyền $tag dạng object hoặc array (tùy version/hook)
    if ($original_is_array) {
        if (!class_exists('WPCF7_FormTag')) {
            return $tag;
        }
        $tag = new WPCF7_FormTag($tag);
    }

    if (!is_object($tag)) {
        return $tag;
    }
    
    // Chỉ xử lý select field có name là course-name (CF7 có thể là select hoặc select*)
    if (!isset($tag->name) || $tag->name !== 'course-name' || !isset($tag->type) || strpos($tag->type, 'select') !== 0) {
        return $tag;
    }
    
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    // Lấy TẤT CẢ khóa học từ database
    $courses_raw = $wpdb->get_results("SELECT course_key, data FROM $table_courses ORDER BY id ASC", ARRAY_A);
    
    if (empty($courses_raw)) {
        return $tag; // Không có khóa học nào
    }
    
    // ✅ XÓA HOÀN TOÀN options cũ (kể cả options hard-code trong form CF7 admin)
    $tag->values = [];
    $tag->labels = [];
    
    // Thêm option mặc định
    $tag->values[] = '';
    $tag->labels[] = '-- Chọn khóa học --';
    
    // ✅ CHỈ thêm các khóa học từ database
    foreach ($courses_raw as $row) {
        $course_data = json_decode($row['data'], true);
        if ($course_data) {
            $course_key = $row['course_key'];
            $course_name = $course_data['course_name'] ?? '';
            
            if (!empty($course_name)) {
                // ✅ FIX: submit value = course_key thuần (tránh CF7 undefined value)
                $tag->values[] = $course_key;
                $tag->labels[] = $course_name;
            }
        }
    }
    
    // Trả về đúng kiểu ban đầu (array -> array)
    if ($original_is_array && is_object($tag) && method_exists($tag, 'to_array')) {
        return $tag->to_array();
    }

    return $tag;
}

// ✅ Cho phép submit giá trị động cho field course-name (tránh lỗi "Undefined value was submitted...")
// 1. Tắt hẳn SWV để dùng cơ chế validate cũ (filter wpcf7_validate_*)
add_filter('wpcf7_use_swv', function($use, $contact_form) {
    return false; // Không dùng SWV, tránh enum check cứng
}, 10, 2);

// 2. Dùng hook wpcf7_init để chắc chắn chạy SAU khi CF7 đăng ký validate mặc định
add_action('wpcf7_init', function() {
    // Loại bỏ validate mặc định của CF7 cho select, chúng ta tự validate theo DB
    remove_filter('wpcf7_validate_select', 'wpcf7_select_validation_filter', 10);
    remove_filter('wpcf7_validate_select*', 'wpcf7_select_validation_filter', 10);
});

add_filter('wpcf7_validate_select', 'cf7_validate_course_select_from_db', 10, 2);
add_filter('wpcf7_validate_select*', 'cf7_validate_course_select_from_db', 10, 2);
function cf7_validate_course_select_from_db($result, $tag) {
    if (!is_object($tag) || $tag->name !== 'course-name') {
        return $result;
    }

    $value = $_POST['course-name'] ?? '';
    if (is_array($value)) {
        $value = $value[0] ?? '';
    }

    // Nếu bắt buộc và để trống -> giữ nguyên lỗi required của CF7
    if ($tag->is_required() && $value === '') {
        return $result;
    }

    // ✅ value là course_key thuần

    // Nếu vẫn trống thì coi như hợp lệ (không gây lỗi undefined value)
    if ($value === '') {
        return $result;
    }

    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_courses} WHERE course_key = %s",
        $value
    ));

    if ($exists) {
        // Hợp lệ theo DB => bỏ qua lỗi "undefined value"
        return $result;
    }

    // Nếu không tìm thấy trong DB -> báo lỗi rõ ràng
    $result->invalidate($tag, __('Khóa học không hợp lệ.', 'cf7-to-telegram'));
    return $result;
}

// Bỏ qua gửi email mặc định của CF7 cho form đăng ký (tránh lỗi mail và báo đỏ)
add_filter('wpcf7_skip_mail', function($skip, $contact_form) {
    $submission = class_exists('WPCF7_Submission') ? WPCF7_Submission::get_instance() : null;
    $data = $submission ? $submission->get_posted_data() : [];
    
    // Chỉ bỏ qua mail với form có field course-name (form đăng ký tư vấn)
    if (isset($data['course-name'])) {
        return true;
    }
    
    return $skip;
}, 10, 2);

// AJAX endpoint để lấy HTML featured courses
add_action('wp_ajax_cf7_get_featured_courses_html', 'cf7_get_featured_courses_html_ajax');
add_action('wp_ajax_nopriv_cf7_get_featured_courses_html', 'cf7_get_featured_courses_html_ajax');
function cf7_get_featured_courses_html_ajax() {
    $html = cf7_get_featured_courses_html();
    wp_send_json_success(['html' => $html]);
}

// Function lấy HTML danh sách khóa học nổi bật
function cf7_get_featured_courses_html($limit = 3) {
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    // Lấy tất cả khóa học từ database
    $courses_raw = $wpdb->get_results("SELECT course_key, data FROM $table_courses ORDER BY id ASC", ARRAY_A);
    $courses_list = [];
    
    foreach ($courses_raw as $row) {
        $course_data = json_decode($row['data'], true);
        if ($course_data) {
            $courses_list[] = [
                'key' => $row['course_key'],
                'name' => $course_data['course_name'] ?? '',
                'price' => floatval($course_data['price'] ?? 0),
                'teacher' => $course_data['teacher'] ?? '',
                'duration' => $course_data['duration'] ?? '',
                'start_date' => $course_data['start_date'] ?? '',
                'end_date' => $course_data['end_date'] ?? ''
            ];
        }
    }
    
    // Sắp xếp theo giá cao nhất
    usort($courses_list, function($a, $b) {
        return $b['price'] - $a['price'];
    });
    
    // Lấy số lượng hiển thị
    $courses_list = array_slice($courses_list, 0, $limit);
    
    if (empty($courses_list)) {
        return '';
    }
    
    $output = '<style>
    .cf7-featured-courses-wrapper {
        max-width: 1200px;
        margin: 0 auto 40px;
        padding: 0 20px;
    }
    .cf7-featured-courses-title {
        text-align: center;
        margin-bottom: 40px;
        position: relative;
    }
    .cf7-featured-courses-title h3 {
        font-size: 32px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0;
        padding: 0;
        display: inline-block;
    }
    .cf7-featured-courses-title::after {
        content: "";
        display: block;
        width: 60px;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin: 15px auto 0;
        border-radius: 2px;
    }
    .cf7-courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }
    .cf7-course-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        overflow: hidden;
        border: 1px solid #f0f0f0;
        position: relative;
    }
    .cf7-course-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2);
        border-color: #667eea;
    }
    .cf7-course-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
        color: #fff;
        position: relative;
    }
    .cf7-course-card-header::after {
        content: "";
        position: absolute;
        bottom: -20px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 20px solid transparent;
        border-right: 20px solid transparent;
        border-top: 20px solid #764ba2;
    }
    .cf7-course-name {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }
    .cf7-course-teacher {
        font-size: 14px;
        opacity: 0.9;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .cf7-course-body {
        padding: 30px 20px 20px;
    }
    .cf7-course-price {
        font-size: 28px;
        font-weight: 800;
        color: #e74c3c;
        margin: 0 0 20px 0;
        display: flex;
        align-items: baseline;
        gap: 4px;
    }
    .cf7-course-price-currency {
        font-size: 16px;
        font-weight: 600;
        color: #c0392b;
    }
    .cf7-course-meta {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    .cf7-course-meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #555;
    }
    .cf7-course-meta-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 6px;
        font-size: 12px;
    }
    .cf7-course-time {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: #fff;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 15px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
    }
    @media (max-width: 768px) {
        .cf7-courses-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .cf7-featured-courses-title h3 {
            font-size: 24px;
        }
    }
    </style>';
    
    $output .= '<div class="cf7-featured-courses-wrapper">';
    $output .= '<div class="cf7-featured-courses-title">';
    $output .= '<h3>🌟 Khóa Học Nổi Bật</h3>';
    $output .= '</div>';
    $output .= '<div class="cf7-courses-grid">';
    
    foreach ($courses_list as $course) {
        $start_formatted = !empty($course['start_date']) ? date('d/m/Y', strtotime($course['start_date'])) : '';
        $end_formatted = !empty($course['end_date']) ? date('d/m/Y', strtotime($course['end_date'])) : '';
        $time_info = ($start_formatted && $end_formatted) ? "<div class='cf7-course-time'>📅 {$start_formatted} - {$end_formatted}</div>" : '';
        
        $output .= '<div class="cf7-course-card">';
        $output .= '<div class="cf7-course-card-header">';
        $output .= '<div class="cf7-course-name">' . esc_html($course['name']) . '</div>';
        $output .= '<div class="cf7-course-teacher">👨‍🏫 ' . esc_html($course['teacher']) . '</div>';
        $output .= '</div>';
        $output .= '<div class="cf7-course-body">';
        $output .= '<div class="cf7-course-price">';
        $output .= '<span class="cf7-course-price-currency">💰</span>';
        $output .= '<span>' . number_format($course['price']) . '</span>';
        $output .= '<span class="cf7-course-price-currency" style="font-size: 14px; margin-left: 4px;">VNĐ</span>';
        $output .= '</div>';
        $output .= '<div class="cf7-course-meta">';
        $output .= '<div class="cf7-course-meta-item">';
        $output .= '<span class="cf7-course-meta-icon">⏱️</span>';
        $output .= '<span><strong>Thời lượng:</strong> ' . esc_html($course['duration']) . '</span>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= $time_info;
        $output .= '</div>';
        $output .= '</div>';
    }
    
    $output .= '</div></div>';
    
    return $output;
}

// Shortcode hiển thị danh sách khóa học nổi bật (giá cao nhất) - giữ lại để dùng ở nơi khác nếu cần
add_shortcode('cf7_featured_courses', 'cf7_display_featured_courses');
function cf7_display_featured_courses($atts) {
    $limit = isset($atts['limit']) ? intval($atts['limit']) : 3;
    return cf7_get_featured_courses_html($limit);
}

// ✅ FIX: AJAX endpoint để lấy danh sách TẤT CẢ khóa học
add_action('wp_ajax_cf7_get_all_courses', 'cf7_get_all_courses_ajax');
add_action('wp_ajax_nopriv_cf7_get_all_courses', 'cf7_get_all_courses_ajax');
function cf7_get_all_courses_ajax() {
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    // Lấy TẤT CẢ khóa học từ database
    $courses_raw = $wpdb->get_results("SELECT course_key, data FROM $table_courses ORDER BY id ASC", ARRAY_A);
    $courses_list = [];
    
    foreach ($courses_raw as $row) {
        $course_data = json_decode($row['data'], true);
        if ($course_data) {
            $courses_list[] = [
                'key' => $row['course_key'],
                'name' => $course_data['course_name'] ?? ''
            ];
        }
    }
    
    wp_send_json_success(['courses' => $courses_list]);
}

// AJAX endpoint để lấy thông tin khóa học
add_action('wp_ajax_cf7_get_course_info', 'cf7_get_course_info_ajax');
add_action('wp_ajax_nopriv_cf7_get_course_info', 'cf7_get_course_info_ajax');
function cf7_get_course_info_ajax() {
    $course_key = isset($_POST['course_key']) ? sanitize_text_field($_POST['course_key']) : '';
    
    if (empty($course_key)) {
        wp_send_json_error(['message' => 'Course key is required']);
        return;
    }
    
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    $course_row = $wpdb->get_row($wpdb->prepare(
        "SELECT data FROM {$table_courses} WHERE course_key = %s",
        $course_key
    ));
    
    if (!$course_row) {
        wp_send_json_error(['message' => 'Course not found']);
        return;
    }
    
    $course_data = json_decode($course_row->data, true);
    if (!$course_data) {
        wp_send_json_error(['message' => 'Invalid course data']);
        return;
    }
    
    $start_formatted = !empty($course_data['start_date']) ? date('d/m/Y', strtotime($course_data['start_date'])) : '';
    $end_formatted = !empty($course_data['end_date']) ? date('d/m/Y', strtotime($course_data['end_date'])) : '';
    
    wp_send_json_success([
        'start_date' => $start_formatted,
        'end_date' => $end_formatted,
        'duration' => $course_data['duration'] ?? ''
    ]);
}

// Thêm script để hiển thị thời gian khi chọn khóa học
add_action('wp_footer', 'cf7_course_time_display_script');
function cf7_course_time_display_script() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tìm select khóa học trong form CF7
        var courseSelect = document.querySelector('select[name="course-name"]');
        if (!courseSelect) return;
        // ⚠️ IMPORTANT:
        // Không tự thay đổi options của select bằng JS vì CF7 validate theo schema enum lúc render form.
        // Nếu JS thay options khác với schema -> sẽ báo "Undefined value was submitted through this field."
        
        // Tạo div để hiển thị thời gian
        var timeDisplay = document.createElement('div');
        timeDisplay.id = 'cf7-course-time-display';
        timeDisplay.style.cssText = 'margin-top: 8px; padding: 10px; background: #f4f8ff; border: 1px solid #d0e6ff; border-radius: 8px; font-size: 13px; color: #34495e; display: none;';
        
        // Chèn div vào sau select
        courseSelect.parentNode.insertBefore(timeDisplay, courseSelect.nextSibling);
        
        // Xử lý khi chọn khóa học
        courseSelect.addEventListener('change', function() {
            var selectedValue = this.value;
            if (!selectedValue) {
                timeDisplay.style.display = 'none';
                return;
            }
            
            var courseKey = selectedValue;
            
            // Hiển thị loading
            timeDisplay.style.display = 'block';
            timeDisplay.innerHTML = '⏳ Đang tải thông tin...';
            
            // Gọi AJAX để lấy thông tin khóa học
            var formData = new FormData();
            formData.append('action', 'cf7_get_course_info');
            formData.append('course_key', courseKey);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.start_date && data.data.end_date) {
                    timeDisplay.innerHTML = '📅 <strong>Thời gian khóa học:</strong> ' + data.data.start_date + ' - ' + data.data.end_date;
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
    </script>
    <?php
}

// Reset form và hiển thị thông báo thành công rõ ràng sau khi gửi
add_action('wp_footer', 'cf7_reset_form_and_message_after_submit');
function cf7_reset_form_and_message_after_submit() {
    ?>
    <style>
    /* ✅ FIX: Đảm bảo thông báo thành công hiển thị rõ ràng */
    .wpcf7-response-output.wpcf7-mail-sent-ok {
        display: block !important;
        padding: 15px 20px !important;
        margin: 20px 0 !important;
        background: #d4edda !important;
        border: 2px solid #28a745 !important;
        border-radius: 8px !important;
        color: #155724 !important;
        font-size: 16px !important;
        font-weight: 500 !important;
        text-align: center !important;
        line-height: 1.5 !important;
        min-height: auto !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* ✅ FIX: Đảm bảo text hiển thị ngay cả khi aria-hidden */
    .wpcf7-response-output.wpcf7-mail-sent-ok[aria-hidden="true"] {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    </style>
    <script>
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
    </script>
    <?php
}

// add_action('wpcf7_mail_sent', 'cf7_send_to_telegram');

function cf7_send_to_telegram() {
    if (!class_exists('WPCF7_Submission')) return;

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();
    
    // ✅ CHỈ gửi Telegram khi là form đăng ký CF7 (có field course-name và your-name)
    // KHÔNG gửi khi là form quản lý course (có field course_key, course_name, etc.)
    if (isset($data['course_key']) || isset($data['course_name']) || isset($data['cf7-course-key'])) {
        return; // Đây là form quản lý course, không phải form đăng ký CF7
    }
    
    // Kiểm tra xem có phải form đăng ký không (phải có course-name và your-name)
    $course_raw = $data['course-name'] ?? '';
    $user_name = $data['your-name'] ?? '';
    
    if (empty($course_raw) || empty($user_name)) {
        return; // Không phải form đăng ký CF7
    }
    
    // ✅ Telegram config
    $bot_token = '8546369954:AAH5cLLAbu9UWVhjN6k7I6f_JksplJakCno'; 
    $chat_id = '7262117677';
    
    // Xử lý nếu là array (CF7 đôi khi trả về array cho select)
    if (is_array($course_raw)) {
        $course_raw = !empty($course_raw) ? $course_raw[0] : '';
    }
    
    // ✅ course_key là value thuần của select
    $course_key = sanitize_text_field($course_raw);

    // ✅ LẤY DỮ LIỆU KHÓA HỌC TỪ DATABASE (NoSQL - JSON)
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    $course_info = [
        'name' => null,
        'price' => 0,
        'teacher' => null,
        'duration' => '',
        'start_date' => '',
        'end_date' => ''
    ];
    
    if (!empty($course_key)) {
        $course_row = $wpdb->get_row($wpdb->prepare(
            "SELECT data FROM {$table_courses} WHERE course_key = %s",
            $course_key
        ));
        
        if ($course_row) {
            $course_data = json_decode($course_row->data, true);
            if ($course_data) {
                $course_info = [
                    'name' => $course_data['course_name'] ?? null,
                    'price' => floatval($course_data['price'] ?? 0),
                    'teacher' => $course_data['teacher'] ?? null,
                    'duration' => $course_data['duration'] ?? '',
                    'start_date' => $course_data['start_date'] ?? '',
                    'end_date' => $course_data['end_date'] ?? ''
                ];
            }
        }
    }

    // ✅ NoSQL values (schema-less, production ready)
    $values = [
        'user' => [
            'name'   => sanitize_text_field($data['your-name'] ?? ''),
            'email'  => sanitize_email($data['your-email'] ?? ''),
            'phone'  => sanitize_text_field($data['your-phone'] ?? ''),
            'note'   => sanitize_text_field($data['your-message'] ?? ''), 
        ],

        'course' => [
            'key'     => $course_key,
            'name'    => $course_info['name'],
            'price'   => $course_info['price'],
            'teacher' => $course_info['teacher'],
        ],

        // waiting (Chờ xử lý) | deposit (Cọc) | completed (Hoàn thành) | overdue (Trễ hạn)
        'order_status' => 'waiting', 

        'payment' => [
            'status'        => 'unpaid', // unpaid (Chưa thanh toán) | deposit (Cọc) | paid (Đã thanh toán) | refund (Hoàn trả) | cancel (Hủy)
            'deposit'       => 0,
            'deposit_at'    => null, // Sẽ update khi admin xác nhận "Cọc"
            'due_date'      => null, // ✅ THÊM FIELD NÀY VÀO PAYMENT
            'paid_amount'   => 0,
            'paid_at'       => null, 
            'payment_method'=> null,
        ],

        'status' => [
            'lead'   => 'new',
            'course' => 'waiting',
        ],

        'status_history' => [],

        'meta' => [
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'source'      => 'cf7',
        ],

        'stats_meta' => [
            'year'  => date('Y'),
            'month' => date('m'),
            'week'  => date('W'),
            'day'   => date('Y-m-d'),
        ],

        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ];

    $table_name = $wpdb->prefix . 'cf7_leads';

    $wpdb->insert(
        $table_name,
        [
            'data'       => wp_json_encode($values, JSON_UNESCAPED_UNICODE),
            'created_at' => current_time('mysql'),
        ],
        ['%s', '%s']
    );    

    // ✅ Telegram notify
    $course_time_info = '';
    if (!empty($course_info['start_date']) && !empty($course_info['end_date'])) {
        $start_formatted = date('d/m/Y', strtotime($course_info['start_date']));
        $end_formatted = date('d/m/Y', strtotime($course_info['end_date']));
        $course_time_info = "\n📅 Thời gian: {$start_formatted} - {$end_formatted}";
    }
    
    $text =
        "📩 ĐƠN ĐĂNG KÝ MỚI\n"
        . "👤 Học viên: {$values['user']['name']}\n"
        . "📞 SĐT: {$values['user']['phone']}\n"
        . "📘 Khóa học: {$values['course']['name']}\n"
        . "💰 Học phí: " . number_format($values['course']['price']) . " VNĐ"
        . $course_time_info;

    wp_remote_post(
        "https://api.telegram.org/bot{$bot_token}/sendMessage",
        [
            'body' => [
                'chat_id' => $chat_id,
                'text'    => $text,
            ]
        ]
    );
}

/**
 * ✅ FIX: Ẩn menu "Quản Lý Khóa Học" nếu không phải admin
 * (Filter này đã được mở rộng trong student-ma.php, nhưng thêm vào đây để đảm bảo)
 */
add_filter('wp_get_nav_menu_items', 'cf7_filter_course_menu_for_admin_only', 10, 3);
function cf7_filter_course_menu_for_admin_only($items, $menu, $args) {
    // 1. Nếu ĐÃ đăng nhập VÀ CÓ quyền Admin (manage_options) -> Cho hiện menu bình thường
    if (is_user_logged_in() && current_user_can('manage_options')) {
        return $items;
    }

    // 2. Nếu KHÔNG phải Admin: Duyệt qua danh sách menu và xóa nút Quản Lý Khóa Học
    foreach ($items as $key => $item) {
        $title = $item->title ?? '';
        // Ẩn menu quản lý khóa học và các biến thể
        if ($title == 'Quản Lý Khóa Học' || 
            $title == 'Quản Lý' ||
            (stripos($title, 'Quản Lý') !== false && stripos($title, 'Khóa Học') !== false)) {
            unset($items[$key]);
        }
    }

    return $items;
}