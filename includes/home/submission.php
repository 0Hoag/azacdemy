<?php

add_action('wpcf7_submit', 'cf7_send_to_telegram');

// ✅ FIX: Populate select field course-name từ database động
add_filter('wpcf7_form_tag', 'cf7_populate_course_select', 10, 2);

// ✅ FIX: Hiển thị tên khóa học thay vì course_key trong Email
add_filter('wpcf7_mail_tag_replacement', 'cf7_replace_course_name_in_email', 10, 4);
function cf7_replace_course_name_in_email($replaced, $submitted, $html, $mail_tag) {
    if ($mail_tag->field_name() === 'course-name') {
        return "HOOK_IS_WORKING_BUT_LOGIC_FAILED (Input: " . print_r($submitted, true) . ")";

        // Lấy value (course_key)
        $course_key = $submitted;
        
        // Nếu là array (select multiple)
        if (is_array($course_key)) {
            $course_key = reset($course_key);
        }

        // Clean key
        $course_key = trim((string)$course_key);

        if (empty($course_key)) return $replaced;

        // Truy vấn DB lấy tên
        global $wpdb;
        $table_courses = $wpdb->prefix . 'cf7_courses';
        
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT data FROM $table_courses WHERE course_key = %s", 
            $course_key
        ));

        if ($row && isset($row->data)) {
            $data = json_decode($row->data, true);
            if (!empty($data['course_name'])) {
                return $data['course_name'];
            }
        }
        
        
        // Debug output if not found (TEMPORARY)
        return $replaced . " (Debug: Key='{$course_key}' Len=" . strlen($course_key) . " - Not Found)";
    }
    return $replaced;
}
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
    
    // ✅ XÓA HOÀN TOÀN options cũ
    $tag->values = [];
    $tag->labels = [];
    
    // Không thêm option mặc định rỗng, để nó tự chọn cái đầu tiên
    // $tag->values[] = '';
    // $tag->labels[] = 'Chọn khóa học *';
    
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
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
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
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
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
        box-shadow: 0 12px 40px rgba(52, 152, 219, 0.2);
        border-color: #3498db;
    }
    .cf7-course-card-header {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
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
        border-top: 20px solid #2980b9;
    }
    .cf7-course-name {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 8px 0;
        line-height: 1.3;
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
        $time_info = ($start_formatted) ? "<div class='cf7-course-time'>📅 Khai giảng: {$start_formatted}</div>" : '';
        
        $output .= '<div class="cf7-course-card">';
        $output .= '<div class="cf7-course-card-header">';
        $output .= '<div class="cf7-course-name">' . esc_html($course['name']) . '</div>';
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
    
    // Xử lý schedules nếu có
    $schedules_list = [];
    $today = date('Y-m-d'); // Lấy ngày hiện tại

    // ✅ Count students per schedule
    $student_counts = [];
    $table_leads = $wpdb->prefix . 'cf7_leads';
    
    // Get all leads for this course to count schedules (optimize with specific query later if needed)
    // Note: We need to parse JSON data column
    $leads = $wpdb->get_results($wpdb->prepare(
        "SELECT data FROM {$table_leads} WHERE data LIKE %s",
        '%"key":"' . $course_key . '"%'
    ));

    foreach ($leads as $lead) {
        $l_data = json_decode($lead->data, true);
        if ($l_data && isset($l_data['course']['schedule_index'])) {
            $idx = intval($l_data['course']['schedule_index']);
            if ($idx >= 0) {
                if (!isset($student_counts[$idx])) $student_counts[$idx] = 0;
                $student_counts[$idx]++;
            }
        }
    }

    // ✅ Context check: If 'admin_edit', show ALL schedules. Otherwise filter expired.
    $context = isset($_POST['context']) ? sanitize_text_field($_POST['context']) : '';
    
    if (!empty($course_data['schedules']) && is_array($course_data['schedules'])) {
        foreach ($course_data['schedules'] as $idx => $sch) {
            if (empty($sch['start'])) continue;
            
            // ✅ Chỉ lấy lịch chưa bắt đầu (start_date >= today) nếu không phải Admin Edit
            if ($context !== 'admin_edit' && $sch['start'] < $today) continue;

            // ✅ Use stored label if available, otherwise fallback to K{index+1}
            $label = !empty($sch['label']) ? $sch['label'] : 'K' . ($idx + 1);

            $schedules_list[] = [
                'index' => $idx,
                'start' => $sch['start'],
                'start_fmt' => date('d/m/Y', strtotime($sch['start'])),
                'label' => $label,
                'student_count' => $student_counts[$idx] ?? 0 // ✅ Return student count
            ];
        }
    }

    // ✅ Kiểm tra và xử lý Start Date (nếu quá hạn thì ẩn)
    $is_expired = false;
    if (!empty($course_data['start_date'])) {
        if ($course_data['start_date'] < $today) {
            // Nếu ngày bắt đầu chính đã quá hạn
            // Kiểm tra xem có lịch nào trong tương lai không?
            if (empty($schedules_list)) {
                $is_expired = true;
                $start_formatted = ''; // Xóa ngày hiển thị để frontend biết
            }
        } else {
            // Nếu ngày chưa quá hạn, thử tìm K-label tương ứng để hiển thị (nếu frontend fallback về đây)
            // (Optional enhancement)
        }
    } else {
        // Nếu không có start_date mà cũng không có schedules -> Coi như expired/invalid
        if (empty($schedules_list)) {
            $is_expired = true;
        }
    }

    if ($is_expired) {
        wp_send_json_success([
            'is_expired' => true,
            'message' => 'Khóa học đã kết thúc đăng ký.',
            'schedules' => []
        ]);
        return;
    }

    wp_send_json_success([
        'start_date' => $start_formatted,
        'end_date' => $end_formatted,
        'duration' => $course_data['duration'] ?? '',
        'schedules' => $schedules_list
    ]);
}

// Hook to enqueue scripts and styles for standard forms
add_action('wp_enqueue_scripts', 'cf7_standard_form_assets');

function cf7_standard_form_assets() {
    // Enqueue Common Form CSS
    wp_enqueue_style(
        'cf7-form-style', 
        plugins_url('../../assets/css/cf7-form.css', __FILE__), 
        [], 
        time() // Force reload CSS
    );

    // Enqueue Common Form CSS (Reset & Success Message)
    wp_enqueue_script(
        'cf7-form-script', 
        plugins_url('../../assets/js/cf7-form.js', __FILE__), 
        [], 
        time(), // Force reload JS
        true
    );

    // Enqueue Standard Form Logic (Select Dropdown behavior)
    wp_enqueue_script(
        'cf7-standard-form-script', 
        plugins_url('../../assets/js/cf7-standard-form.js', __FILE__), 
        [], 
        time(), // Force reload JS
        true
    );
    
    // Landing JS - Remove strict template check to ensure loading
    // if ( is_page_template('page-templates/landing-page.php') || is_page_template('page-templates/landing-page-new.php') ) {
        wp_enqueue_script('cf7-landing-script', plugins_url('../../assets/js/landing.js', __FILE__), ['jquery'], time(), true);
        
        wp_localize_script('cf7-landing-script', 'cf7_ajax_obj', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    // }

    // Pass AJAX URL for standard form
    wp_localize_script('cf7-standard-form-script', 'cf7_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
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
    $bot_token = cf7_get_env('TELEGRAM_BOT_TOKEN'); 
    $chat_id = cf7_get_env('TELEGRAM_CHAT_ID');

    if (empty($bot_token) || empty($chat_id)) {
        // Fallback keys if .env is missing
        $bot_token = '8546369954:AAH5cLLAbu9UWVhjN6k7I6f_JksplJakCno'; 
        $chat_id = '7262117677';
    }
    
    // Xử lý nếu là array (CF7 đôi khi trả về array cho select)
    if (is_array($course_raw)) {
        $course_raw = !empty($course_raw) ? $course_raw[0] : '';
    }
    
    // ✅ course_key là value thuần của select
    $course_key = sanitize_text_field($course_raw);
    
    // Parse index từ hidden input
    $schedule_index = isset($_POST['cf7-course-schedule-index']) && $_POST['cf7-course-schedule-index'] !== '' 
        ? intval($_POST['cf7-course-schedule-index']) 
        : -1;

    // ✅ LẤY DỮ LIỆU KHÓA HỌC TỪ DATABASE (NoSQL - JSON)
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    $course_info = [
        'name' => null,
        'price' => 0,
        'duration' => '',
        'start_date' => '',
        'end_date' => '',
        'schedule_label' => ''
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
                    'duration' => $course_data['duration'] ?? '',
                    'start_date' => $course_data['start_date'] ?? '',
                    'end_date' => $course_data['end_date'] ?? '',
                    'schedule_label' => ''
                ];
                
                if ($schedule_index >= 0 && !empty($course_data['schedules'][$schedule_index])) {
                    // Case 1: Có chọn schedule cụ thể
                    $sch = $course_data['schedules'][$schedule_index];
                    $course_info['start_date'] = $sch['start'] ?? '';
                    $course_info['end_date'] = $sch['end'] ?? '';
                    // ✅ Ưu tiên lấy label từ DB (đã cấu hình), nếu không có mới tự sinh
                    $course_info['schedule_label'] = !empty($sch['label']) ? $sch['label'] : ('K' . ($schedule_index + 1));
                }
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
            'schedule_index' => $schedule_index,
            // 'schedule_label' => $course_info['schedule_label'], // ❌ REMOVED: Don't store static label
            'start_date' => $course_info['start_date'], // Lưu lại ngày bắt đầu cụ thể
            'end_date' => $course_info['end_date'] // Lưu lại ngày kết thúc cụ thể
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
    if (!empty($course_info['start_date'])) {
        $start_formatted = date('d/m/Y', strtotime($course_info['start_date']));
        $course_time_info = "\n📅 Khai giảng: {$start_formatted}";
    }
    
    $course_name_display = $values['course']['name'];
    // ✅ Fix: Use local variable since we removed it from DB storage
    if (!empty($course_info['schedule_label'])) {
        $course_name_display .= " - " . $course_info['schedule_label'];
    }

    $text =
        "📩 ĐƠN ĐĂNG KÝ MỚI\n"
        . "👤 Học viên: {$values['user']['name']}\n"
        . "📞 SĐT: {$values['user']['phone']}\n"
        . "📘 Khóa học: {$course_name_display}\n"
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
    // 1. Nếu ĐÃ đăng nhập VÀ CÓ quyền Admin (manage_options) HOẶC Quản lý (view_admin_menu) -> Cho hiện menu bình thường
    if (is_user_logged_in() && (current_user_can('manage_options') || current_user_can('view_admin_menu'))) {
        return $items;
    }

    // 2. Nếu KHÔNG phải Admin: Duyệt qua danh sách menu và xóa nút Quản Lý Khóa Học
    foreach ($items as $key => $item) {
        $title = $item->title ?? '';
        // Ẩn menu quản lý khóa học và các biến thể
        if ($title == 'Quản Lý Khóa Học' || 
            $title == 'Quản Lý' ||
            (stripos($title, 'Quản Lý') !== false && stripos($title, 'Khóa Học') !== false) ||
            stripos($title, 'Thống kê') !== false ||
            stripos($title, 'Doanh thu') !== false) {
            unset($items[$key]);
        }
    }

    return $items;
}