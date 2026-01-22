<?php

// Static flag để đảm bảo modal chỉ render 1 lần
$GLOBALS['cf7_course_modal_rendered'] = false;

// AJAX Handlers cho CRUD khóa học
add_action('wp_ajax_cf7_course_create', 'cf7_handle_course_create');
add_action('wp_ajax_cf7_course_update', 'cf7_handle_course_update');
add_action('wp_ajax_cf7_course_delete', 'cf7_handle_course_delete');
add_action('wp_ajax_cf7_course_get', 'cf7_handle_course_get');

add_action('wp_ajax_nopriv_cf7_course_create', 'cf7_handle_course_create');
add_action('wp_ajax_nopriv_cf7_course_update', 'cf7_handle_course_update');
add_action('wp_ajax_nopriv_cf7_course_delete', 'cf7_handle_course_delete');
add_action('wp_ajax_nopriv_cf7_course_get', 'cf7_handle_course_get');

// Kiểm tra quyền admin
function cf7_check_admin_permission() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Không có quyền truy cập.']);
        exit;
    }
}

// CREATE - Tạo khóa học mới
function cf7_handle_course_create() {
    // ✅ FIX: Thêm verify nonce cho CREATE
    check_ajax_referer('cf7_course_nonce', '_ajax_nonce');
    
    cf7_check_admin_permission();
    
    $course_key = isset($_POST['course_key']) ? sanitize_text_field($_POST['course_key']) : '';
    // ✅ FIX: course_key phải là slug (không space/ký tự lạ) để dùng ổn định cho CF7 + DB
    $course_key = sanitize_title($course_key);
    $course_name = isset($_POST['course_name']) ? sanitize_text_field($_POST['course_name']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    
    if (empty($course_key) || empty($course_name)) {
        wp_send_json_error(['message' => 'Vui lòng điền đầy đủ thông tin.']);
    }
    
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    // Kiểm tra course_key đã tồn tại chưa
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_courses} WHERE course_key = %s",
        $course_key
    ));
    
    if ($exists > 0) {
        wp_send_json_error(['message' => 'Mã khóa học đã tồn tại.']);
    }
    
    // Lấy duration (số buổi) nhập thủ công
    $duration = isset($_POST['duration']) ? sanitize_text_field($_POST['duration']) : '';
    
    // ✅ FIX: Tạo schedules từ start_date và end_date
    $schedules = [];
    if (!empty($start_date) && !empty($end_date)) {
        $schedules[] = [
            'start' => $start_date,
            'end' => $end_date
        ];
    }
    
    $course_data = [
        'course_key' => $course_key,
        'course_name' => $course_name,
        'price' => $price,
        'duration' => $duration,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'description' => $description,
        'schedules' => $schedules
    ];
    
    $result = $wpdb->insert(
        $table_courses,
        [
            'course_key' => $course_key,
            'data' => wp_json_encode($course_data, JSON_UNESCAPED_UNICODE)
        ]
    );
    
    if ($result) {
        wp_send_json_success(['message' => 'Tạo khóa học thành công.']);
    } else {
        wp_send_json_error(['message' => 'Không thể tạo khóa học. Lỗi: ' . $wpdb->last_error]);
    }
}

// UPDATE - Cập nhật khóa học
function cf7_handle_course_update() {
    check_ajax_referer('cf7_course_nonce', '_ajax_nonce');
    cf7_check_admin_permission();
    
    $course_key = isset($_POST['course_key']) ? sanitize_text_field($_POST['course_key']) : '';
    // ✅ FIX: course_key phải là slug (không space/ký tự lạ)
    $course_key = sanitize_title($course_key);
    $course_name = isset($_POST['course_name']) ? sanitize_text_field($_POST['course_name']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    
    if (empty($course_key) || empty($course_name)) {
        wp_send_json_error(['message' => 'Vui lòng điền đầy đủ thông tin.']);
    }
    
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    // Lấy dữ liệu cũ
    $old_course = $wpdb->get_row($wpdb->prepare(
        "SELECT data FROM {$table_courses} WHERE course_key = %s",
        $course_key
    ));
    
    if (!$old_course) {
        wp_send_json_error(['message' => 'Không tìm thấy khóa học.']);
    }
    
    $old_data = json_decode($old_course->data, true);
    
    // Lấy duration (số buổi) nhập thủ công
    $duration = isset($_POST['duration']) ? sanitize_text_field($_POST['duration']) : '';
    
    // ✅ FIX: Cập nhật schedules khi start_date hoặc end_date thay đổi
    $old_schedules = $old_data['schedules'] ?? [];
    $new_schedules = $old_schedules;
    
    // Nếu start_date hoặc end_date thay đổi, cập nhật schedule đầu tiên
    if (!empty($start_date) && !empty($end_date)) {
        if (empty($new_schedules)) {
            // Nếu chưa có schedules, tạo mới
            $new_schedules[] = [
                'start' => $start_date,
                'end' => $end_date
            ];
        } else {
            // Cập nhật schedule đầu tiên
            $new_schedules[0] = [
                'start' => $start_date,
                'end' => $end_date
            ];
        }
    }
    
    $course_data = [
        'course_key' => $course_key,
        'course_name' => $course_name,
        'price' => $price,
        'duration' => $duration,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'description' => $description,
        'schedules' => $new_schedules
    ];
    
    // ✅ FIX: Bỏ kiểm tra "không có thay đổi" vì gây lỗi logic
    // Luôn thực hiện UPDATE để đảm bảo dữ liệu được lưu
    
    $json_data = wp_json_encode($course_data, JSON_UNESCAPED_UNICODE);
    
    $result = $wpdb->update(
        $table_courses,
        ['data' => $json_data],
        ['course_key' => $course_key],
        ['%s'],
        ['%s']
    );
    
    if ($wpdb->last_error) {
        wp_send_json_error(['message' => 'Lỗi database: ' . $wpdb->last_error]);
        return;
    }
    
    // $result có thể là 0 (không có thay đổi) hoặc 1 (có thay đổi)
    // Cả hai trường hợp đều là thành công
    if ($result === false) {
        wp_send_json_error(['message' => 'Không thể cập nhật khóa học. Lỗi: ' . $wpdb->last_error]);
    } else {
        wp_send_json_success(['message' => 'Cập nhật khóa học thành công.']);
    }
}

// DELETE - Xóa khóa học (có điều kiện)
function cf7_handle_course_delete() {
    check_ajax_referer('cf7_course_nonce', '_ajax_nonce');
    cf7_check_admin_permission();
    
    $course_key = isset($_POST['course_key']) ? sanitize_text_field($_POST['course_key']) : '';
    
    if (empty($course_key)) {
        wp_send_json_error(['message' => 'Không tìm thấy khóa học.']);
    }
    
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    $table_leads = $wpdb->prefix . 'cf7_leads';
    
    // Kiểm tra xem khóa học có học viên không
    $all_leads = $wpdb->get_results("SELECT data FROM {$table_leads}", ARRAY_A);
    $has_students = false;
    
    foreach ($all_leads as $lead_row) {
        $lead_data = json_decode($lead_row['data'], true);
        if ($lead_data) {
            $lead_course_key = $lead_data['course']['key'] ?? '';
            if ($lead_course_key === $course_key) {
                $has_students = true;
                break;
            }
        }
    }
    
    if ($has_students) {
        wp_send_json_error(['message' => 'Không thể xóa khóa học này vì đã có học viên đăng ký.']);
        }
    
    $result = $wpdb->delete(
        $table_courses,
        ['course_key' => $course_key]
    );
    
    if ($result) {
        wp_send_json_success(['message' => 'Xóa khóa học thành công.']);
    } else {
        wp_send_json_error(['message' => 'Không thể xóa khóa học.']);
    }
}

// GET - Lấy thông tin khóa học
function cf7_handle_course_get() {
    if (isset($_POST['_ajax_nonce'])) {
        check_ajax_referer('cf7_course_nonce', '_ajax_nonce');
    }
    
    cf7_check_admin_permission();
    
    $course_key = isset($_POST['course_key']) ? sanitize_text_field($_POST['course_key']) : '';
    
    if (empty($course_key)) {
        wp_send_json_error(['message' => 'Không tìm thấy khóa học.']);
    }
    
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    $course_row = $wpdb->get_row($wpdb->prepare(
        "SELECT course_key, data FROM {$table_courses} WHERE course_key = %s",
        $course_key
    ));
    
    if (!$course_row) {
        wp_send_json_error(['message' => 'Không tìm thấy khóa học.']);
    }
    
    $course_data = json_decode($course_row->data, true);
    wp_send_json_success($course_data);
}

// Shortcode hiển thị thông tin tổng quan trong info bar
add_shortcode('course_info_bar', 'cf7_display_course_info_bar');
function cf7_display_course_info_bar($atts) {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '';
    }

    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    $table_leads = $wpdb->prefix . 'cf7_leads';

    // Đếm tổng số khóa học
    $total_courses = $wpdb->get_var("SELECT COUNT(*) FROM {$table_courses}");
    
    // Đếm tổng số học viên đã đóng cọc hoặc hoàn thành
    $all_leads = $wpdb->get_results("SELECT data FROM {$table_leads}", ARRAY_A);
    $total_students = 0;
    
    foreach ($all_leads as $lead_row) {
        $lead_data = json_decode($lead_row['data'], true);
        if ($lead_data) {
            $lead_status = $lead_data['payment']['status'] ?? '';
            if (in_array($lead_status, ['deposit', 'paid'])) {
            $total_students++;
        }
    }
    }

    $output = '<span><strong>Tổng khóa học:</strong> ' . number_format($total_courses) . '</span>';
    $output .= '<span><strong>Tổng học viên:</strong> <span class="student-count-badge">' . number_format($total_students) . ' học viên</span></span>';

    return $output;
}

// Shortcode hiển thị nút thêm khóa học
add_shortcode('course_add_button', 'cf7_display_course_add_button');
function cf7_display_course_add_button($atts) {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '';
    }
    
    return '<div class="course-action-bar" style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
        <button type="button" class="btn-add-course" onclick="cf7_open_course_modal()" style="padding:10px 20px; background:#0064E0; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; transition:all 0.3s;" onmouseover="this.style.background=\'#0056b3\'; this.style.transform=\'scale(1.05)\'" onmouseout="this.style.background=\'#0064E0\'; this.style.transform=\'scale(1)\'">➕ Thêm Khóa Học</button>
    </div>';
}

// Shortcode render modal và JavaScript (chỉ cần gọi 1 lần)
add_shortcode('course_modal_js', 'cf7_display_course_modal_js');
function cf7_display_course_modal_js($atts) {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '';
    }
    
    // Sử dụng global flag để đảm bảo chỉ render 1 lần
    if (!empty($GLOBALS['cf7_course_modal_rendered'])) {
        return ''; // Chỉ render 1 lần
    }
    $GLOBALS['cf7_course_modal_rendered'] = true;
    
    return cf7_course_modal_html() . cf7_course_js();
}

// ✅ FIX: Inject modal vào body ngay khi body mở (sớm nhất có thể)
add_action('wp_body_open', 'cf7_inject_course_modal_body', 1);
function cf7_inject_course_modal_body() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return;
    }
    
    // Sử dụng global flag để đảm bảo chỉ render 1 lần
    if (!empty($GLOBALS['cf7_course_modal_rendered'])) {
        return; // Đã render rồi, không cần inject
    }
    $GLOBALS['cf7_course_modal_rendered'] = true;
    
    // Inject modal HTML và JavaScript vào body (sớm hơn footer)
    echo cf7_course_modal_html() . cf7_course_js();
}

// ✅ FIX: Backup method - Inject modal vào footer nếu wp_body_open không có
add_action('wp_footer', 'cf7_inject_course_modal_footer', 1);
function cf7_inject_course_modal_footer() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return;
    }
    
    // Sử dụng global flag để đảm bảo chỉ render 1 lần
    if (!empty($GLOBALS['cf7_course_modal_rendered'])) {
        return; // Đã render rồi, không cần inject
    }
    $GLOBALS['cf7_course_modal_rendered'] = true;
    
    // Inject modal HTML và JavaScript trực tiếp vào footer
    echo cf7_course_modal_html() . cf7_course_js();
}

// Shortcode hiển thị nút thêm khóa học và bảng (backward compatibility)
add_shortcode('course_table_wrapper', 'cf7_display_course_table_wrapper');
function cf7_display_course_table_wrapper($atts) {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<div style="text-align:center; padding:20px;">Vui lòng đăng nhập Admin để xem.</div>';
    }
    
    $output = do_shortcode('[course_add_button]');
    $output .= do_shortcode('[course_table_row]');
    $output .= do_shortcode('[course_modal_js]');
    
    return $output;
}

// Shortcode hiển thị TẤT CẢ các khóa học trong bảng
add_shortcode('course_table_row', 'cf7_display_course_table_row');
function cf7_display_course_table_row($atts) {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<tr><td colspan="6" style="text-align:center; padding:20px;">Vui lòng đăng nhập Admin để xem.</td></tr>';
    }

    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    $table_leads = $wpdb->prefix . 'cf7_leads';

    // ✅ Phân trang
    $per_page = 10;
    $current_page = isset($_GET['cf7_course_page']) ? max(1, intval($_GET['cf7_course_page'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Lấy TẤT CẢ khóa học từ database để đếm
    $all_courses = $wpdb->get_results("SELECT course_key, data FROM {$table_courses} ORDER BY created_at ASC", ARRAY_A);
    
    if (empty($all_courses)) {
        return '<tr><td colspan="6" style="text-align:center; padding:20px; color:#e74c3c;">Chưa có khóa học nào.</td></tr>';
    }

    // Tính toán phân trang
    $total_records = count($all_courses);
    $total_pages = ceil($total_records / $per_page);
    $all_courses = array_slice($all_courses, $offset, $per_page);
    
    $base_url = remove_query_arg(['cf7_course_page']);

    // Lấy TẤT CẢ học viên để đếm và kiểm tra có học viên không
    $all_leads = $wpdb->get_results("SELECT data FROM {$table_leads}", ARRAY_A);
    
    // Đếm số học viên cho mỗi khóa học và kiểm tra có học viên không
    $course_student_counts = [];
    $course_has_students = [];
    foreach ($all_leads as $lead_row) {
        $lead_data = json_decode($lead_row['data'], true);
        if ($lead_data) {
            $lead_course_key = $lead_data['course']['key'] ?? '';
            $lead_status = $lead_data['payment']['status'] ?? '';
            
            if (!empty($lead_course_key)) {
                $course_has_students[$lead_course_key] = true;
                
                if (in_array($lead_status, ['deposit', 'paid'])) {
                    if (!isset($course_student_counts[$lead_course_key])) {
                        $course_student_counts[$lead_course_key] = 0;
                    }
                    $course_student_counts[$lead_course_key]++;
                }
            }
        }
    }

    $output = '';
    
    // Render từng khóa học
    foreach ($all_courses as $course_row) {
        $course_data = json_decode($course_row['data'], true);
        if (!$course_data) {
            continue;
        }

        $course_key = $course_row['course_key'];
        $course_name = $course_data['course_name'] ?? 'N/A';
        $duration = $course_data['duration'] ?? 'N/A';
        
        // Format ngày để hiển thị (giữ nguyên string)
        $start_date_display = !empty($course_data['start_date']) ? date('d/m/Y', strtotime($course_data['start_date'])) : 'N/A';
        $end_date_display = !empty($course_data['end_date']) ? date('d/m/Y', strtotime($course_data['end_date'])) : 'N/A';
        
        // Đếm số học viên cho khóa học này
        $student_count = $course_student_counts[$course_key] ?? 0;
        $has_students = isset($course_has_students[$course_key]);

        // Xác định trạng thái khóa học
        $course_status = 'Sắp khai giảng';
        $status_class = 'course-status-upcoming';
        if (!empty($course_data['start_date'])) {
            // ✅ FIX: Sử dụng DateTime để so sánh chính xác hơn
            try {
                $start_date_obj = new DateTime($course_data['start_date'], new DateTimeZone('Asia/Ho_Chi_Minh'));
                $end_date_obj = !empty($course_data['end_date']) ? new DateTime($course_data['end_date'], new DateTimeZone('Asia/Ho_Chi_Minh')) : null;
                $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
                
                // Chỉ so sánh ngày, không so sánh giờ
                $start_date_obj->setTime(0, 0, 0);
                if ($end_date_obj) {
                    $end_date_obj->setTime(23, 59, 59); // Kết thúc vào cuối ngày
                }
                $now->setTime(0, 0, 0);
                
                if ($now < $start_date_obj) {
                    $course_status = 'Sắp khai giảng';
                    $status_class = 'course-status-upcoming';
                } elseif ($now >= $start_date_obj && ($end_date_obj === null || $now <= $end_date_obj)) {
                    $course_status = 'Đang diễn ra';
                $status_class = 'course-status-ongoing';
                } else {
                    $course_status = 'Đã kết thúc';
                $status_class = 'course-status-completed';
                }
            } catch (Exception $e) {
                // Nếu có lỗi parse date, mặc định là "Sắp khai giảng"
                $course_status = 'Sắp khai giảng';
                $status_class = 'course-status-upcoming';
            }
        }

        $output .= '<tr data-course-key="' . esc_attr($course_key) . '">';
        $output .= '<td><strong>' . esc_html($course_name) . '</strong><br><small style="color:#7f8c8d;">👨‍🎓 ' . number_format($student_count) . ' học viên</small></td>';
        $output .= '<td>' . esc_html($duration) . '</td>';
        $output .= '<td>' . esc_html($start_date_display) . '</td>';
        $output .= '<td>' . esc_html($end_date_display) . '</td>';
        $output .= '<td><span class="course-status-label ' . esc_attr($status_class) . '">' . esc_html($course_status) . '</span></td>';
        
        // Cột thao tác
        $output .= '<td style="white-space:nowrap;">';
        $output .= '<button type="button" onclick="cf7_edit_course(\'' . esc_js($course_key) . '\')" style="padding:6px 12px; margin-right:5px; background:#0064E0; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; transition:all 0.3s;" onmouseover="this.style.background=\'#0056b3\'" onmouseout="this.style.background=\'#0064E0\'">✏️ Sửa</button>';
        
        if ($has_students) {
            $output .= '<button type="button" disabled title="Không thể xóa vì đã có học viên" style="padding:6px 12px; background:#95a5a6; color:#fff; border:none; border-radius:6px; cursor:not-allowed; font-size:12px; font-weight:600; opacity:0.6;">🗑️ Xóa</button>';
        } else {
            $output .= '<button type="button" onclick="cf7_delete_course(\'' . esc_js($course_key) . '\')" style="padding:6px 12px; background:#e74c3c; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;">🗑️ Xóa</button>';
        }
        $output .= '</td>';
        
        $output .= '</tr>';
    }

    if (empty($output)) {
        return '<tr><td colspan="6" style="text-align:center; padding:20px; color:#e74c3c;">Không có dữ liệu khóa học hợp lệ.</td></tr>';
}

    // ✅ Phân trang
    if ($total_pages > 1) {
        $output .= "<tr class='quan-ly-pagination'><td colspan='6' style='padding:20px; text-align:center; background:#f9f9f9;'>";
        $output .= "<div style='display:inline-flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:center;'>";
        
        // Nút Previous
        if ($current_page > 1) {
            $prev_url = add_query_arg('cf7_course_page', $current_page - 1, $base_url);
            $output .= "<a href='" . esc_url($prev_url) . "' style='padding:8px 16px; background:#0064E0; color:#fff; border-radius:6px; text-decoration:none; font-weight:600; transition:all 0.3s;' onmouseover='this.style.background=\"#0056b3\"' onmouseout='this.style.background=\"#0064E0\"'>‹ Trước</a>";
        } else {
            $output .= "<span style='padding:8px 16px; background:#ecf0f1; color:#95a5a6; border-radius:6px; cursor:not-allowed;'>‹ Trước</span>";
        }
        
        // Số trang
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            $first_url = add_query_arg('cf7_course_page', 1, $base_url);
            $output .= "<a href='" . esc_url($first_url) . "' style='padding:8px 12px; background:#fff; color:#34495e; border:1px solid #ddd; border-radius:6px; text-decoration:none; font-weight:600;'>1</a>";
            if ($start_page > 2) {
                $output .= "<span style='padding:8px 4px; color:#7f8c8d;'>...</span>";
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
            $output .= "<span style='padding:8px 12px; background:#0064E0 !important; color:#fff !important; border-radius:6px; font-weight:700;'>" . $i . "</span>";
        } else {
            $page_url = add_query_arg('cf7_course_page', $i, $base_url);
            $output .= "<a href='" . esc_url($page_url) . "' style='padding:8px 12px; background:#fff; color:#34495e; border:1px solid #ddd; border-radius:6px; text-decoration:none; font-weight:600; transition:all 0.3s;' onmouseover='this.style.borderColor=\"#0064E0\"; this.style.color=\"#0064E0\"' onmouseout='this.style.borderColor=\"#ddd\"; this.style.color=\"#34495e\"'>" . $i . "</a>";
        }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                $output .= "<span style='padding:8px 4px; color:#7f8c8d;'>...</span>";
            }
            $last_url = add_query_arg('cf7_course_page', $total_pages, $base_url);
            $output .= "<a href='" . esc_url($last_url) . "' style='padding:8px 12px; background:#fff; color:#34495e; border:1px solid #ddd; border-radius:6px; text-decoration:none; font-weight:600;'>" . $total_pages . "</a>";
        }
        
        // Nút Next
        if ($current_page < $total_pages) {
            $next_url = add_query_arg('cf7_course_page', $current_page + 1, $base_url);
            $output .= "<a href='" . esc_url($next_url) . "' style='padding:8px 16px; background:#0064E0; color:#fff; border-radius:6px; text-decoration:none; font-weight:600; transition:all 0.3s;' onmouseover='this.style.background=\"#0056b3\"' onmouseout='this.style.background=\"#0064E0\"'>Sau ›</a>";
        } else {
            $output .= "<span style='padding:8px 16px; background:#ecf0f1; color:#95a5a6; border-radius:6px; cursor:not-allowed;'>Sau ›</span>";
        }
        
        $output .= "</div>";
        $output .= "<div style='margin-top:12px; color:#7f8c8d; font-size:13px;'>Trang " . $current_page . " / " . $total_pages . " (Tổng: " . number_format($total_records) . " khóa học)</div>";
        $output .= "</td></tr>";
    }

    return $output;
}

// Modal HTML cho form thêm/sửa khóa học
function cf7_course_modal_html() {
    return '
    <div id="cf7-course-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:30px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <h3 id="cf7-modal-title" style="margin:0 0 20px 0; font-size:20px; color:#34495e;">Thêm Khóa Học</h3>
            <form id="cf7-course-form" onsubmit="return false;" action="#" method="post">
                <input type="hidden" id="cf7-course-action" value="create">
                <input type="hidden" id="cf7-course-key-hidden" name="course_key_hidden">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Mã khóa học *</label>
                    <input type="text" id="cf7-course-key" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                    <small style="color:#7f8c8d;">Ví dụ: wordpress, fullstack-php</small>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Tên khóa học *</label>
                    <input type="text" id="cf7-course-name" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Học phí (đ) *</label>
                    <input type="number" id="cf7-course-price" required min="0" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Số buổi học (tùy chọn)</label>
                    <input type="text" id="cf7-course-duration" placeholder="Ví dụ: 10 buổi, 12 buổi..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Ngày bắt đầu *</label>
                    <input type="date" id="cf7-course-start-date" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Ngày kết thúc *</label>
                    <input type="date" id="cf7-course-end-date" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Mô tả khóa học (tùy chọn)</label>
                    <textarea id="cf7-course-description" rows="4" placeholder="Nhập mô tả về khóa học..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;"></textarea>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="cf7_close_course_modal()" style="padding:10px 20px; background:#95a5a6; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Hủy</button>
                    <button type="submit" style="padding:10px 20px; background:#0064E0; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600; transition:all 0.3s;" onmouseover="this.style.background=\'#0056b3\'" onmouseout="this.style.background=\'#0064E0\'">Lưu</button>
                </div>
            </form>
        </div>
    </div>';
}

// JavaScript xử lý CRUD - DEBUG VERSION
function cf7_course_js() {
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('cf7_course_nonce');
    return '
    <script>
    var CF7_AJAX_URL = "' . esc_js($ajax_url) . '";
    var CF7_NONCE = "' . esc_js($nonce) . '";
        
    // Mở modal
        window.cf7_open_course_modal = function(courseKey) {
        console.log("🔓 Opening modal, courseKey:", courseKey);
            
        // Hàm helper để tìm modal và form với retry
        function findModalAndForm(retries) {
            retries = retries || 0;
                var modal = document.getElementById("cf7-course-modal");
                var form = document.getElementById("cf7-course-form");
            
            if (!modal || !form) {
                if (retries < 10) {
                    console.log("⏳ Waiting for modal/form... (retry " + (retries + 1) + "/10)");
                    setTimeout(function() {
                        findModalAndForm(retries + 1);
                    }, 100);
                    return;
                } else {
                    console.error("❌ Modal or form not found after 10 retries!");
                    alert("Lỗi: Không tìm thấy modal hoặc form. Vui lòng đảm bảo shortcode [course_modal_js] đã được thêm vào trang và refresh lại.");
                    return;
                }
            }
            
            // Tìm thấy modal và form, tiếp tục xử lý
            openModalWithElements(modal, form, courseKey);
            }
            
        // Hàm mở modal sau khi tìm thấy elements
        function openModalWithElements(modal, form, courseKey) {
            console.log("✅ Modal and form found!");
            var action = document.getElementById("cf7-course-action");
            var title = document.getElementById("cf7-modal-title");
                
                if (courseKey) {
                // Chế độ sửa
                console.log("📝 Edit mode for:", courseKey);
                    action.value = "update";
                    title.textContent = "Sửa Khóa Học";
                    var keyInput = document.getElementById("cf7-course-key");
                var keyHidden = document.getElementById("cf7-course-key-hidden");
                    if (keyInput) {
                        keyInput.disabled = true;
                }
                if (keyHidden) {
                    keyHidden.value = courseKey;
                    }
                    
                // Lấy dữ liệu khóa học
                var formData = new FormData();
                formData.append("action", "cf7_course_get");
                formData.append("_ajax_nonce", CF7_NONCE);
                formData.append("course_key", courseKey);
                    
                fetch(CF7_AJAX_URL, {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                    .then(data => {
                    console.log("📦 GET Response:", data);
                    
                        if (data.success && data.data) {
                        var course = data.data;
                        
                        document.getElementById("cf7-course-key").value = course.course_key || courseKey;
                        document.getElementById("cf7-course-name").value = course.course_name || "";
                        document.getElementById("cf7-course-price").value = course.price || 0;
                        document.getElementById("cf7-course-duration").value = course.duration || "";
                        document.getElementById("cf7-course-start-date").value = course.start_date || "";
                        document.getElementById("cf7-course-end-date").value = course.end_date || "";
                        document.getElementById("cf7-course-description").value = course.description || "";
                        
                        modal.style.display = "flex";
                        modal.style.visibility = "visible";
                        modal.setAttribute("aria-hidden", "false");
                        
                        // Đảm bảo handler được attach sau khi modal hiển thị
                        setTimeout(function() {
                            attachFormSubmitHandler();
                        }, 50);
                        } else {
                        alert("Không thể tải thông tin khóa học.");
                        }
                    })
                .catch(error => {
                    console.error("❌ GET Error:", error);
                    alert("Có lỗi xảy ra khi tải thông tin khóa học.");
                    });
                } else {
                // Chế độ thêm mới
                console.log("➕ Create mode");
                    action.value = "create";
                    title.textContent = "Thêm Khóa Học";
                    var keyInput = document.getElementById("cf7-course-key");
                var keyHidden = document.getElementById("cf7-course-key-hidden");
                    if (keyInput) {
                        keyInput.disabled = false;
                    }
                if (keyHidden) {
                    keyHidden.value = "";
                }
                
                        form.reset();
                    modal.style.display = "flex";
                modal.style.visibility = "visible";
                modal.setAttribute("aria-hidden", "false");
                
                // Đảm bảo handler được attach sau khi modal hiển thị
                setTimeout(function() {
                    attachFormSubmitHandler();
                }, 50);
            }
        }
        
        // Bắt đầu tìm modal và form
        findModalAndForm(0);
    };
    
    // Đóng modal
        window.cf7_close_course_modal = function() {
        console.log("🚪 Closing modal...");
            var modal = document.getElementById("cf7-course-modal");
            if (modal) {
                modal.style.display = "none";
            modal.style.visibility = "hidden";
            modal.setAttribute("aria-hidden", "true");
            console.log("✅ Modal closed");
            }
        };
        
    // Sửa khóa học
    window.cf7_edit_course = function(courseKey) {
        cf7_open_course_modal(courseKey);
        };
        
    // Xóa khóa học
    window.cf7_delete_course = function(courseKey) {
        if (!confirm("Bạn có chắc chắn muốn xóa khóa học này?")) {
            return;
        }
            
        var formData = new FormData();
        formData.append("action", "cf7_course_delete");
        formData.append("_ajax_nonce", CF7_NONCE);
        formData.append("course_key", courseKey);
            
        fetch(CF7_AJAX_URL, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
            .then(data => {
                if (data.success) {
                alert("Xóa khóa học thành công!");
                    location.reload();
                } else {
                alert(data.data.message || "Không thể xóa khóa học.");
                }
            })
        .catch(error => {
            console.error("Delete error:", error);
            alert("Có lỗi xảy ra khi xóa khóa học.");
            });
        };
        
    // Hàm attach form submit handler
    function attachFormSubmitHandler() {
            var form = document.getElementById("cf7-course-form");
        
        if (!form) {
            console.warn("⚠️ Form not found yet");
            return false;
        }
        
        // Kiểm tra xem đã attach chưa
        if (form.hasAttribute("data-submit-handler-attached")) {
            console.log("✅ Form handler already attached");
            return true;
        }
        
        console.log("✅ Form found, attaching submit handler...");
        form.setAttribute("data-submit-handler-attached", "true");
        
        // Sử dụng capture phase để chạy TRƯỚC Contact Form 7
                form.addEventListener("submit", function(e) {
            console.log("=== 🚀 FORM SUBMIT STARTED ===");
            
            // Ngăn tất cả các handler khác (bao gồm Contact Form 7)
                    e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            console.log("✅ Event prevented and stopped");
            
            // Lấy action
            var actionEl = document.getElementById("cf7-course-action");
            if (!actionEl) {
                console.error("❌ Action element not found");
                alert("Lỗi: Không tìm thấy action.");
                return false;
            }
            
            var action = actionEl.value;
            console.log("📝 Action:", action);
            
            // Lấy course_key
            var courseKey;
            if (action === "update") {
                var keyHidden = document.getElementById("cf7-course-key-hidden");
                courseKey = keyHidden ? keyHidden.value : document.getElementById("cf7-course-key").value.trim();
            } else {
                courseKey = document.getElementById("cf7-course-key").value.trim();
            }
            
            var courseName = document.getElementById("cf7-course-name").value.trim();
            var price = document.getElementById("cf7-course-price").value || 0;
            var duration = document.getElementById("cf7-course-duration").value.trim();
            var startDate = document.getElementById("cf7-course-start-date").value;
            var endDate = document.getElementById("cf7-course-end-date").value;
            var description = document.getElementById("cf7-course-description").value || "";
            
            console.log("📦 Data:", {
                courseKey: courseKey,
                courseName: courseName,
                price: price,
                startDate: startDate,
                endDate: endDate
            });
            
            // Validate
            if (!courseKey || !courseName || !startDate || !endDate) {
                console.error("❌ Validation failed");
                alert("Vui lòng điền đầy đủ thông tin bắt buộc.");
                return false;
            }
            
            // Tạo FormData
            var formData = new FormData();
            var ajaxAction = action === "create" ? "cf7_course_create" : "cf7_course_update";
            formData.append("action", ajaxAction);
            formData.append("_ajax_nonce", CF7_NONCE);
            formData.append("course_key", courseKey);
            formData.append("course_name", courseName);
            formData.append("price", price);
            formData.append("duration", duration);
            formData.append("start_date", startDate);
            formData.append("end_date", endDate);
            formData.append("description", description);
            
            console.log("📤 Sending AJAX:", ajaxAction);
            
            // Disable button
            var submitBtn = form.querySelector("button[type=submit]");
            var originalBtnText = submitBtn ? submitBtn.textContent : "Lưu";
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Đang xử lý...";
                console.log("🔒 Button disabled");
            }
            
            // Gửi request
            fetch(CF7_AJAX_URL, {
                method: "POST",
                body: formData
            })
            .then(function(response) {
                console.log("📡 Response status:", response.status);
                if (!response.ok) {
                    throw new Error("HTTP " + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log("📥 Response data:", data);
                console.log("📊 data.success:", data.success);
                console.log("📊 typeof data.success:", typeof data.success);
                
                // DEBUG: Kiểm tra nhiều trường hợp
                var isSuccess = false;
                if (data.success === true || data.success === "true" || data.success === 1) {
                    isSuccess = true;
                }
                
                console.log("✅ isSuccess:", isSuccess);
                
                if (isSuccess) {
                    var message = "Thành công!";
                    if (data.data && data.data.message) {
                        message = data.data.message;
                    }
                    
                    console.log("🎉 SUCCESS! Message:", message);
                    
                    // Đóng modal NGAY LẬP TỨC
                    console.log("🚪 Closing modal immediately...");
                    var modal = document.getElementById("cf7-course-modal");
                    if (modal) {
                        modal.style.display = "none";
                        modal.style.visibility = "hidden";
                        modal.setAttribute("aria-hidden", "true");
                    }
                    window.cf7_close_course_modal();
                    
                    // Hiển thị thông báo và reload
                    alert(message);
                    
                    console.log("🔄 Reloading page...");
                            setTimeout(function() {
                                location.reload();
                    }, 300);
                        } else {
                    console.error("❌ FAILED");
                    
                    var errorMsg = "Có lỗi xảy ra.";
                    if (data.data) {
                        if (typeof data.data === "string") {
                            errorMsg = data.data;
                        } else if (data.data.message) {
                            errorMsg = data.data.message;
                        }
                    }
                    
                    console.error("Error message:", errorMsg);
                    alert(errorMsg);
                    
                    // Enable lại button
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalBtnText;
                        console.log("🔓 Button enabled");
                    }
                        }
                    })
            .catch(function(error) {
                console.error("❌ Fetch Error:", error);
                alert("Lỗi: " + error.message);
                
                // Enable lại button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            });
            
            return false;
        }, true); // Capture phase - chạy TRƯỚC các handler khác
        
        console.log("✅ Submit handler attached successfully");
        return true;
    }
    
    // Khởi tạo khi DOM ready
    document.addEventListener("DOMContentLoaded", function() {
        console.log("📋 DOM Content Loaded");
        
        // Thử attach ngay
        if (attachFormSubmitHandler()) {
            console.log("✅ Form handler attached on DOM ready");
        }
        
        // Đóng modal khi click bên ngoài
            var modal = document.getElementById("cf7-course-modal");
            if (modal) {
                modal.addEventListener("click", function(e) {
                    if (e.target === modal) {
                        cf7_close_course_modal();
                    }
                });
            }
    });
    </script>';
}
