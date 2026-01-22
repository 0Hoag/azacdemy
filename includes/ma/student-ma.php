<?php

// 1. Cho phép chạy Shortcode và xử lý lưu dữ liệu
add_filter( 'wpcf7_form_elements', 'do_shortcode' );
add_action('wp_ajax_update_lead_status', 'cf7_handle_all_in_one');
add_action('wp_ajax_nopriv_update_lead_status', 'cf7_handle_all_in_one');

// ✅ AJAX Handlers cho CRUD học viên
add_action('wp_ajax_cf7_student_create', 'cf7_handle_student_create');
add_action('wp_ajax_cf7_student_update', 'cf7_handle_student_update');
add_action('wp_ajax_cf7_student_get', 'cf7_handle_student_get');

add_action('wp_ajax_nopriv_cf7_student_create', 'cf7_handle_student_create');
add_action('wp_ajax_nopriv_cf7_student_update', 'cf7_handle_student_update');
add_action('wp_ajax_nopriv_cf7_student_get', 'cf7_handle_student_get');

// Kiểm tra quyền admin
function cf7_check_student_admin_permission() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Không có quyền truy cập.']);
        exit;
    }
}

// CREATE - Tạo học viên mới
function cf7_handle_student_create() {
    check_ajax_referer('cf7_student_nonce', '_ajax_nonce');
    cf7_check_student_admin_permission();
    
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $course_key = isset($_POST['course_key']) ? sanitize_text_field($_POST['course_key']) : '';
    $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
    
    if (empty($name) || empty($phone) || empty($course_key)) {
        wp_send_json_error(['message' => 'Vui lòng điền đầy đủ thông tin bắt buộc.']);
    }
    
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    $table_leads = $wpdb->prefix . 'cf7_leads';
    
    // Lấy thông tin khóa học
    $course_row = $wpdb->get_row($wpdb->prepare(
        "SELECT data FROM {$table_courses} WHERE course_key = %s",
        $course_key
    ));
    
    if (!$course_row) {
        wp_send_json_error(['message' => 'Không tìm thấy khóa học.']);
    }
    
    $course_data = json_decode($course_row->data, true);
    if (!$course_data) {
        wp_send_json_error(['message' => 'Dữ liệu khóa học không hợp lệ.']);
    }
    
    // Tạo dữ liệu học viên
    $values = [
        'user' => [
            'name'   => $name,
            'email'  => $email,
            'phone'  => $phone,
            'note'   => $note,
        ],
        'course' => [
            'key'     => $course_key,
            'name'    => $course_data['course_name'] ?? '',
            'price'   => floatval($course_data['price'] ?? 0),
        ],
        'order_status' => 'waiting',
        'payment' => [
            'status'        => 'unpaid',
            'deposit'       => 0,
            'deposit_at'    => null,
            'due_date'      => null,
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
            'source'      => 'manual',
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
    
    $result = $wpdb->insert(
        $table_leads,
        [
            'data'       => wp_json_encode($values, JSON_UNESCAPED_UNICODE),
            'created_at' => current_time('mysql'),
        ],
        ['%s', '%s']
    );
    
    if ($result) {
        wp_send_json_success(['message' => 'Tạo học viên thành công.']);
    } else {
        wp_send_json_error(['message' => 'Không thể tạo học viên. Lỗi: ' . $wpdb->last_error]);
    }
}

// UPDATE - Cập nhật thông tin học viên
function cf7_handle_student_update() {
    check_ajax_referer('cf7_student_nonce', '_ajax_nonce');
    cf7_check_student_admin_permission();
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $course_key = isset($_POST['course_key']) ? sanitize_text_field($_POST['course_key']) : '';
    $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
    
    if (empty($id) || empty($name) || empty($phone) || empty($course_key)) {
        wp_send_json_error(['message' => 'Vui lòng điền đầy đủ thông tin bắt buộc.']);
    }
    
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    $table_leads = $wpdb->prefix . 'cf7_leads';
    
    // Lấy dữ liệu cũ
    $old_row = $wpdb->get_row($wpdb->prepare(
        "SELECT data FROM {$table_leads} WHERE id = %d",
        $id
    ));
    
    if (!$old_row) {
        wp_send_json_error(['message' => 'Không tìm thấy học viên.']);
    }
    
    $old_data = json_decode($old_row->data, true);
    if (!$old_data) {
        wp_send_json_error(['message' => 'Dữ liệu học viên không hợp lệ.']);
    }
    
    // Lấy thông tin khóa học mới
    $course_row = $wpdb->get_row($wpdb->prepare(
        "SELECT data FROM {$table_courses} WHERE course_key = %s",
        $course_key
    ));
    
    if (!$course_row) {
        wp_send_json_error(['message' => 'Không tìm thấy khóa học.']);
    }
    
    $course_data = json_decode($course_row->data, true);
    if (!$course_data) {
        wp_send_json_error(['message' => 'Dữ liệu khóa học không hợp lệ.']);
    }
    
    // Cập nhật dữ liệu (giữ nguyên payment và các thông tin khác)
    $old_data['user']['name'] = $name;
    $old_data['user']['phone'] = $phone;
    $old_data['user']['email'] = $email;
    $old_data['user']['note'] = $note;
    
    $old_data['course']['key'] = $course_key;
    $old_data['course']['name'] = $course_data['course_name'] ?? '';
    $old_data['course']['price'] = floatval($course_data['price'] ?? 0);
    
    // Nếu giá khóa học thay đổi, cập nhật lại deposit amount
    if ($old_data['payment']['status'] === 'deposit') {
        $new_price = floatval($course_data['price'] ?? 0);
        $old_data['payment']['deposit'] = $new_price * 0.2;
    }
    
    $old_data['updated_at'] = current_time('mysql');
    
    $result = $wpdb->update(
        $table_leads,
        ['data' => wp_json_encode($old_data, JSON_UNESCAPED_UNICODE)],
        ['id' => $id],
        ['%s'],
        ['%d']
    );
    
    if ($result !== false) {
        wp_send_json_success(['message' => 'Cập nhật học viên thành công.']);
    } else {
        wp_send_json_error(['message' => 'Không thể cập nhật học viên. Lỗi: ' . $wpdb->last_error]);
    }
}

// GET - Lấy thông tin học viên
function cf7_handle_student_get() {
    if (isset($_POST['_ajax_nonce'])) {
        check_ajax_referer('cf7_student_nonce', '_ajax_nonce');
    }
    
    cf7_check_student_admin_permission();
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (empty($id)) {
        wp_send_json_error(['message' => 'Không tìm thấy học viên.']);
    }
    
    global $wpdb;
    $table_leads = $wpdb->prefix . 'cf7_leads';
    
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id, data FROM {$table_leads} WHERE id = %d",
        $id
    ));
    
    if (!$row) {
        wp_send_json_error(['message' => 'Không tìm thấy học viên.']);
    }
    
    $data = json_decode($row->data, true);
    if (!$data) {
        wp_send_json_error(['message' => 'Dữ liệu học viên không hợp lệ.']);
    }
    
    wp_send_json_success([
        'id' => $row->id,
        'name' => $data['user']['name'] ?? '',
        'phone' => $data['user']['phone'] ?? '',
        'email' => $data['user']['email'] ?? '',
        'note' => $data['user']['note'] ?? '',
        'course_key' => $data['course']['key'] ?? '',
    ]);
}

/**
 * Hàm core cập nhật trạng thái + tiền trong DB
 * Dùng được cho cả AJAX và form POST bình thường.
 */
function cf7_update_lead_status_core($id, $new_status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_leads';
    $id = intval($id);
    $new_status = sanitize_text_field($new_status);

    $row = $wpdb->get_row($wpdb->prepare("SELECT data FROM $table_name WHERE id = %d", $id));
    if ($row) {
        $data = json_decode($row->data, true);
        $full_price = isset($data['course']['price']) ? floatval($data['course']['price']) : 0;
        $deposit_amount = $full_price * 0.2;
        $current_status = $data['payment']['status'] ?? 'unpaid';

        // Kiểm tra: Chỉ cho phép hoàn cọc khi đã đóng cọc
        if ($new_status === 'refund' && $current_status !== 'deposit') {
            return ['success' => false, 'message' => 'Chỉ có thể hoàn cọc khi đã đóng cọc trước đó.'];
        }

        switch ($new_status) {
            case 'deposit':
                $data['payment']['status']      = 'deposit';
                $data['payment']['deposit']     = $deposit_amount;
                $data['payment']['deposit_at']  = current_time('mysql'); // Ngày đặt cọc
                // ✅ Đã cập nhật hạn thanh toán là 7 ngày kể từ hôm nay
                $data['payment']['due_date']    = date('Y-m-d H:i:s', strtotime('+7 days', current_time('timestamp')));
                $data['payment']['paid_amount'] = $deposit_amount;
                break;

            case 'paid':
                $data['payment']['status']      = 'paid';
                $data['payment']['paid_amount'] = $full_price;
                $data['payment']['paid_at']     = current_time('mysql');
                $data['payment']['due_date']    = null; // Đã xong thì xóa hạn
                break;

            case 'unpaid':
                $data['payment']['status']      = $new_status;
                $data['payment']['deposit_at']  = null;
                $data['payment']['due_date']    = null;
                $data['payment']['paid_amount'] = 0;
                break;

            case 'cancel':
                $data['payment']['status']      = 'cancel';
                $data['payment']['cancel_at']    = current_time('mysql'); // Ngày hủy
                $data['payment']['deposit_at']  = null;
                $data['payment']['due_date']    = null;
                $data['payment']['paid_amount'] = 0;
                break;

            case 'refund':
                $data['payment']['status']      = 'refund';
                $data['payment']['refund_at']   = current_time('mysql'); // Ngày hoàn cọc
                $data['payment']['deposit_at']  = null;
                $data['payment']['due_date']    = null;
                $data['payment']['paid_amount'] = 0; // Hoàn lại tiền cọc
                break;
        }

        $data['updated_at'] = current_time('mysql');

        $updated = $wpdb->update(
            $table_name,
            ['data' => wp_json_encode($data, JSON_UNESCAPED_UNICODE)],
            ['id' => $id]
        );

        if ($updated !== false) {
            return ['success' => true, 'message' => 'Cập nhật thành công.'];
        } else {
            return ['success' => false, 'message' => 'Không thể cập nhật dữ liệu.'];
        }
    }

    return ['success' => false, 'message' => 'Không tìm thấy bản ghi.'];
}

// Handler cho AJAX (nếu sau này muốn dùng, nhưng không phụ thuộc JS)
function cf7_handle_all_in_one() {
    $id         = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    $result = cf7_update_lead_status_core($id, $new_status);

    if ($result['success']) {
        wp_send_json_success();
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}

// Hàm phụ trợ chuyển đổi tiếng Việt có dấu thành không dấu
function vietnamese_to_alias($str) {
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
    $str = preg_replace("/(đ)/", 'd', $str);
    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
    $str = preg_replace("/(Đ)/", 'D', $str);
    return strtolower($str);
}

// 2. HÀM HIỂN THỊ CHÍNH
function cf7_get_table_rows_combined() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return '<tr><td colspan="8" style="text-align:center; padding:20px;">Vui lòng đăng nhập Admin để xem.</td></tr>';
    }

    global $wpdb;
    $table_leads = $wpdb->prefix . 'cf7_leads';
    $table_courses = $wpdb->prefix . 'cf7_courses';

    // --- 1. LẤY DANH SÁCH KHÓA HỌC TỪ DATABASE (NoSQL - JSON) ---
    $db_courses_raw = $wpdb->get_results("SELECT course_key, data FROM $table_courses", OBJECT_K);
    $db_courses_list = [];
    foreach ($db_courses_raw as $key => $row) {
        $course_data = json_decode($row->data, true);
        if ($course_data) {
            $db_courses_list[$key] = (object)[
                'course_key' => $key,
                'course_name' => $course_data['course_name'] ?? '',
                'price' => floatval($course_data['price'] ?? 0),
                'duration' => $course_data['duration'] ?? '',
                'start_date' => $course_data['start_date'] ?? '',
                'end_date' => $course_data['end_date'] ?? '',
                'schedules' => $course_data['schedules'] ?? []
            ];
        }
    }
    // Truy cập: $db_courses_list['wordpress']->price, $db_courses_list['wordpress']->start_date

    $current_period = isset($_GET['cf7_period']) ? sanitize_text_field(wp_unslash($_GET['cf7_period'])) : 'month';
    if (!in_array($current_period, ['day', 'week', 'month', 'year', 'all'], true)) {
        $current_period = 'month';
    }

    $filter_date   = isset($_GET['cf7_date']) ? sanitize_text_field(wp_unslash($_GET['cf7_date'])) : '';
    $filter_course = isset($_GET['cf7_course']) ? sanitize_text_field(wp_unslash($_GET['cf7_course'])) : '';
    $search_name   = isset($_GET['cf7_s']) ? sanitize_text_field(wp_unslash($_GET['cf7_s'])) : '';
    $search_alias  = vietnamese_to_alias($search_name);

    // Update trạng thái (giữ nguyên logic cũ)
    if (!empty($_GET['cf7_lead_id']) && !empty($_GET['cf7_new_status']) && wp_verify_nonce($_GET['cf7_nonce'], 'cf7_update_lead_status')) {
        cf7_update_lead_status_core($_GET['cf7_lead_id'], $_GET['cf7_new_status']);
        wp_safe_redirect(remove_query_arg(['cf7_lead_id', 'cf7_new_status', 'cf7_nonce']));
        exit;
    }

    // Phân trang
    $per_page = 10;
    $current_page = isset($_GET['cf7_page']) ? max(1, intval($_GET['cf7_page'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Lấy tất cả records để lọc
    $all_results = $wpdb->get_results("SELECT * FROM $table_leads ORDER BY created_at DESC", ARRAY_A);
    
    // ✅ CSS và JavaScript để đảm bảo table và header kéo dài hết chiều rộng
    $output = '<style>
    .table-quan-ly {
        width: 100% !important;
        table-layout: auto !important;
        border-collapse: collapse !important;
    }
    .table-quan-ly thead {
        width: 100% !important;
        display: table-header-group !important;
    }
    .table-quan-ly thead tr {
        width: 100% !important;
        display: table-row !important;
    }
    .table-quan-ly thead th {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%) !important;
        color: #fff !important;
        padding: 15px 12px !important;
        text-align: left !important;
        font-weight: 600 !important;
        white-space: nowrap;
    }
    /* ✅ Tăng khoảng cách dòng cho thoáng */
    .table-quan-ly tbody td {
        padding: 20px 15px !important;
        vertical-align: middle !important;
        border-bottom: 1px solid #f1f2f6 !important;
        color: #34495e !important;
    }
    
    /* --- CSS USER PROVIDED --- */
    /* --- 1. PHÁ KHUNG & ĐỀ NỀN --- */ 
    .quan-ly-container-fluid { 
        width: 100vw; position: relative; left: 50%; right: 50%; 
        margin-left: -50vw; margin-right: -50vw; background: #f4f7fa; padding: 0 40px; 
    } 
    
    /* --- 2. THANH CÔNG CỤ CĂN CHỈNH TUYỆT ĐỐI --- */ 
    .quan-ly-filter-row td { 
        padding: 30px 20px !important; 
        background: #ffffff !important; 
    } 
    
    /* Container chính sử dụng Flexbox */ 
    .quan-ly-filter-row td > div { 
        display: flex !important; 
        align-items: center !important; /* Căn giữa trục dọc tuyệt đối cho tất cả thành phần */ 
        justify-content: space-between; 
        gap: 20px; 
    } 
    
    /* Nhóm chức năng bên trái */ 
    .filter-group-left { 
        display: flex !important; 
        align-items: center !important; /* Đảm bảo con bên trong cũng căn giữa */ 
        gap: 15px; 
    } 
    
    /* ĐỒNG BỘ CHIỀU CAO TẤT CẢ INPUT/SELECT/BUTTON */ 
    .quan-ly-datepicker input[type="date"], 
    .quan-ly-coursefilter select, 
    #cf7_search_input, 
    button[onclick*="cf7_open_student_modal"], 
    #btn_cf7_search_exec { 
        height: 42px !important; /* Cố định chiều cao tuyệt đối */ 
        line-height: 42px !important; /* Căn giữa chữ bên trong */ 
        padding: 0 15px !important; 
        border: 1px solid #e2e8f0 !important; 
        border-radius: 10px !important; 
        font-size: 13px !important; 
        background: #f8fafc !important; 
        box-sizing: border-box !important; 
        margin: 0 !important; 
    } 
    
    /* --- HỒI PHỤC & NÂNG CẤP NÚT LỌC (TABS) --- */ 
    .quan-ly-filter-tabs { 
        display: flex !important; 
        align-items: center !important; 
        gap: 8px; 
    } 
    
    .filter-tab { 
        height: 36px !important; /* Thấp hơn input một chút để tạo nhịp điệu UI */ 
        display: inline-flex !important; /* Dùng inline-flex để căn giữa chữ */ 
        align-items: center !important; 
        justify-content: center !important; 
        padding: 0 18px !important; 
        border-radius: 50px !important; 
        font-size: 13px !important; 
        font-weight: 600 !important; 
        text-decoration: none !important; 
        border: 1px solid #e2e8f0 !important; /* Khôi phục khung viền bị mất */ 
        color: #64748b !important; 
        background: #ffffff !important; 
        transition: all 0.25s ease !important; 
        cursor: pointer !important; 
    } 
    
    .filter-tab:hover { 
        background: #f1f5f9 !important; 
        border-color: #cbd5e1 !important; 
        color: #1e293b !important; 
    } 
    
    .filter-tab.active { 
        background: #3b82f6 !important; 
        color: #ffffff !important; 
        border-color: #2563eb !important; 
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3) !important; 
    } 
    
    /* Tinh chỉnh riêng cho nút Tìm kiếm để dính liền input */ 
    .quan-ly-search-box { 
        display: flex !important; 
        align-items: center !important; 
    } 
    
    #cf7_search_input { 
        border-radius: 10px 0 0 10px !important; 
        border-right: none !important; 
    } 
    
    #btn_cf7_search_exec { 
        border-radius: 0 10px 10px 0 !important; 
        background: #1e293b !important; 
        color: #fff !important; 
        border: none !important; 
        cursor: pointer !important; 
    } 
    
    /* Nút Thêm Học Viên Pro */ 
    button[onclick*="cf7_open_student_modal"] { 
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%) !important; 
        color: #fff !important; 
        border: none !important; 
        font-weight: 700 !important; 
    } 
    
    /* --- 3. ANALYTICS DASHBOARD (PHẦN DƯỚI CÙNG) --- */ 
    .quan-ly-summary td { 
        background: #ffffff !important; 
        border-top: 3px solid #3b82f6 !important; 
        padding: 40px !important; 
    } 
    
    .analytics-grid { 
        display: grid; 
        grid-template-columns: repeat(4, 1fr); 
        gap: 25px; 
        margin-bottom: 35px; 
    } 
    
    .analytics-card { 
        padding: 24px; 
        border-radius: 16px; 
        background: #f8fafc; 
        border: 1px solid #e2e8f0; 
    }
    </style>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // ✅ Fix: Thêm cột "Thao tác" vào header nếu thiếu
        var thead = document.querySelector(".table-quan-ly thead tr");
        var tbodyFirstRow = document.querySelector(".table-quan-ly tbody tr:not(.quan-ly-filter-row)");
        
        if (thead && tbodyFirstRow) {
            var headerCells = thead.querySelectorAll("th");
            var bodyCells = tbodyFirstRow.querySelectorAll("td");
            
            // Nếu header có 7 cột nhưng body có 8 cột, thêm cột "Thao tác" vào header
            if (headerCells.length === 7 && bodyCells.length === 8) {
                var th = document.createElement("th");
                th.textContent = "Thao tác";
                th.style.background = "linear-gradient(135deg, #3498db 0%, #2980b9 100%)";
                th.style.color = "#fff";
                th.style.padding = "15px 12px";
                th.style.textAlign = "left";
                th.style.fontWeight = "600";
                thead.appendChild(th);
            }
        }
    });
    </script>';

    $base_url = remove_query_arg(['cf7_lead_id', 'cf7_new_status', 'cf7_nonce', 'cf7_period', 'cf7_date', 'cf7_course', 'cf7_page']);
    $periods = ['day' => 'Hôm nay', 'week' => 'Tuần này', 'month' => 'Tháng này', 'year' => 'Năm nay', 'all' => 'Tất cả'];

    $output .= "<tr class='quan-ly-filter-row'><td colspan='8'>";
    $output .= "<div style='display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;'>";
    $output .= "<div style='display:flex; align-items:center; gap:12px;'>";
    
    // ✅ Nút Thêm học viên
    $output .= "<button type='button' onclick='cf7_open_student_modal()' style='padding:8px 16px; background:linear-gradient(135deg, #3498db 0%, #2980b9 100%); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.3s;' onmouseover='this.style.transform=\"scale(1.05)\"' onmouseout='this.style.transform=\"scale(1)\"'>➕ Thêm Học Viên</button>";
    
    // --- TOOLBAR: DATE PICKER ---
    $output .= "<div class='quan-ly-datepicker'><input type='date' id='cf7_filter_date' value='".esc_attr($filter_date)."' onchange='cf7_exec_combined_filter()' style='padding:6px 10px; border:1px solid #d0e6ff; border-radius:999px; font-size:13px; outline:none; background:#f4f8ff;'></div>";

    // --- TOOLBAR: DROPDOWN KHÓA HỌC TỪ DB ---
    $output .= "<div class='quan-ly-coursefilter'>
        <select id='cf7_filter_course' onchange='cf7_exec_combined_filter()' style='padding:6px 12px; border:1px solid #d0e6ff; border-radius:999px; font-size:13px; outline:none; background:#f4f8ff; cursor:pointer;'>
            <option value=''>-- Tất cả khóa học --</option>";
            foreach ($db_courses_list as $c) {
                $output .= "<option value='".esc_attr($c->course_key)."' ".selected($filter_course, $c->course_key, false).">".esc_html($c->course_name)."</option>";
            }
    $output .= "</select></div>";

    // --- TOOLBAR: TABS THỜI GIAN ---
    $output .= "<div class='quan-ly-filter-tabs'>";
    foreach ($periods as $key => $label) {
        $url = add_query_arg(['cf7_period' => $key], $base_url);
        $active_class = ($current_period === $key && empty($filter_date)) ? ' active' : '';
        $output .= "<a href='" . esc_url($url) . "' class='filter-tab{$active_class}'>{$label}</a>";
    }
    $output .= "</div></div>";

    // JS XỬ LÝ LỌC
    $output .= "<script>
        function cf7_exec_combined_filter() {
            var date = document.getElementById('cf7_filter_date').value;
            var course = document.getElementById('cf7_filter_course').value;
            var url = new URL(window.location.href);
            if(date) { url.searchParams.set('cf7_date', date); url.searchParams.delete('cf7_period'); } else { url.searchParams.delete('cf7_date'); }
            if(course) url.searchParams.set('cf7_course', course); else url.searchParams.delete('cf7_course');
            url.searchParams.delete('cf7_page'); // Reset về trang 1 khi lọc
            window.location.href = url.href;
        }
    </script>";

    // SEARCH BOX (Chống submit CF7)
    $output .= "<div class='quan-ly-search-box' style='display:flex; align-items:center; border:1px solid #e1e8ed; border-radius:8px; overflow:hidden; background:#fff; box-shadow:0 2px 4px rgba(0,0,0,0.08);'>
        <input type='text' id='cf7_search_input' placeholder='🔍 Tìm tên học viên...' value='".esc_attr($search_name)."' style='border:none; padding:10px 16px; outline:none; font-size:14px; width:200px; height:40px; background:transparent;'>
        <button type='button' id='btn_cf7_search_exec' style='background:linear-gradient(135deg, #3498db 0%, #2980b9 100%); color:#fff; border:none; padding:0 20px; cursor:pointer; font-size:13px; font-weight:600; height:40px;'>Tìm kiếm</button>
        <script>
            (function($){ $(document).ready(function(){
                function do_s(e){ if(e) e.preventDefault(); var v=$('#cf7_search_input').val(); var u=new URL(window.location.href); if(v) u.searchParams.set('cf7_s',v); else u.searchParams.delete('cf7_s'); u.searchParams.delete('cf7_page'); window.location.href=u.href; return false; }
                $('#btn_cf7_search_exec').on('click', do_s);
                $('#cf7_search_input').on('keypress', function(e){ if(e.which==13) do_s(e); });
            }); })(jQuery);
        </script>
    </div></div></td></tr>";

    // Lọc dữ liệu trước
    $filtered_results = [];
    foreach ($all_results as $row) {
        $val = json_decode($row['data'], true);
            $u_name = $val['user']['name'] ?? '';
        $c_key  = $val['course']['key'] ?? '';
        
        // Lọc theo search
        if ($search_name !== '' && stripos(vietnamese_to_alias($u_name), $search_alias) === false) continue;
        if ($filter_course !== '' && $c_key !== $filter_course) continue;

            $stats = $val['stats_meta'] ?? [];
            $include = true;
            $today = date('Y-m-d'); $year = date('Y'); $month = date('m'); $week = date('W');

        if (!empty($filter_date)) {
            $include = (($stats['day'] ?? '') === $filter_date);
        } else {
            switch ($current_period) {
                case 'day': $include = (($stats['day'] ?? '') === $today); break;
                case 'week': $include = (($stats['week'] ?? '') === $week) && (($stats['year'] ?? '') === $year); break;
                case 'month': $include = (($stats['month'] ?? '') === $month) && (($stats['year'] ?? '') === $year); break;
                case 'year': $include = (($stats['year'] ?? '') === $year); break;
                default: $include = true; break;
            }
        }
            if (!$include) continue;

        $filtered_results[] = $row;
    }
    
    // Tính toán phân trang
    $total_records = count($filtered_results);
    $total_pages = ceil($total_records / $per_page);
    $filtered_results = array_slice($filtered_results, $offset, $per_page);
    
    // Chuyển lại thành object để tương thích với code cũ
    $results = [];
    foreach ($filtered_results as $row) {
        $obj = new stdClass();
        $obj->id = $row['id'];
        $obj->data = $row['data'];
        $obj->created_at = $row['created_at'];
        $results[] = $obj;
    }

    if ($results) {
        // $total_students = 0; $total_course_price = 0; $total_deposit_value = 0; $total_must_pay = 0; // Đã chuyển sang trang thống kê riêng

        foreach ($results as $row) {
            $val = json_decode($row->data, true);
            $u_name = $val['user']['name'] ?? '';
            $c_key  = $val['course']['key'] ?? '';
            
            // --- 2. TRA CỨU DỮ LIỆU KHÓA HỌC TỪ DANH SÁCH DATABASE ĐÃ LẤY (JSON) ---
            $course_info = $db_courses_list[$c_key] ?? null;
            $full_price = $course_info ? $course_info->price : 0;
            $course_display_name = $course_info ? $course_info->course_name : ($val['course']['name'] ?? 'N/A');

            // --- TÍNH TOÁN ---
            $status = $val['payment']['status'] ?? 'unpaid';
            $paid_amount = floatval($val['payment']['paid_amount'] ?? 0);
            $deposit_saved = floatval($val['payment']['deposit'] ?? ($full_price * 0.2));
            $must_pay_remaining = max($full_price - $paid_amount, 0);

            // $total_students++; $total_course_price += $full_price; $total_deposit_value += $deposit_saved; $total_must_pay += $must_pay_remaining;

            // --- RENDER ---
            $status_labels = [
                'unpaid'  => '<span style="color:#f39c12;">🟠 Chờ xử lý</span>',
                'deposit' => '<span style="color:#3498db;">🔵 Đã cọc (20%)</span>',
                'paid'    => '<span style="color:#2ecc71;">🟢 Hoàn thành</span>',
                'refund'  => '<span style="color:#9b59b6;">🟣 Hoàn cọc</span>',
                'cancel'  => '<span style="color:#e74c3c;">🔴 Hủy</span>'
            ];

            $output .= "<tr>";
            $output .= "<td>" . date('d/m', strtotime($row->created_at)) . "</td>";
            $output .= "<td><div style='font-weight:bold; margin-bottom:2px;'>" . esc_html($u_name ?: 'N/A') . "</div><div style='font-size:0.9em; color:#555;'>" . esc_html($val['user']['phone'] ?? '') . "</div></td>";
            
            // Hiển thị tên khóa học và thời gian
            $course_time_info = '';
            if ($course_info && !empty($course_info->start_date) && !empty($course_info->end_date)) {
                $start_formatted = date('d/m/Y', strtotime($course_info->start_date));
                $end_formatted = date('d/m/Y', strtotime($course_info->end_date));
                $course_time_info = "<div style='font-size:0.9em; color:#7f8c8d; margin-top:2px;'>📅 {$start_formatted} - {$end_formatted}</div>";
            }
            $output .= "<td><div style='font-weight:bold;'>" . esc_html($course_display_name) . "</div>{$course_time_info}</td>";
            
            // Hiển thị thông tin cọc, hoàn cọc hoặc hủy
            if ($status === 'refund') {
                $refund_at = !empty($val['payment']['refund_at']) ? date('d/m/y', strtotime($val['payment']['refund_at'])) : '---';
                $output .= "<td><b>" . number_format($full_price * 0.2) . "đ</b><br><span class='date-badge' style='color:#9b59b6;'>🔄 Hoàn cọc: {$refund_at}</span></td>";
            } elseif ($status === 'cancel') {
                $cancel_at = !empty($val['payment']['cancel_at']) ? date('d/m/y', strtotime($val['payment']['cancel_at'])) : '---';
                $output .= "<td><b>" . number_format($full_price * 0.2) . "đ</b><br><span class='date-badge' style='color:#e74c3c;'>❌ Hủy: {$cancel_at}</span></td>";
            } else {
                $deposit_at = !empty($val['payment']['deposit_at']) ? date('d/m/y', strtotime($val['payment']['deposit_at'])) : '---';
                $output .= "<td><b>" . number_format($full_price * 0.2) . "đ</b><br><span class='date-badge'>💰 Cọc: {$deposit_at}</span></td>";
            }

            // Hiển thị thông tin thanh toán - chỉ hủy mới hiển thị ở đây
            if ($status === 'cancel') {
                $cancel_at = !empty($val['payment']['cancel_at']) ? date('d/m/y', strtotime($val['payment']['cancel_at'])) : '---';
                $output .= "<td><b>" . number_format($paid_amount) . "đ</b><br><span class='date-badge' style='color:#e74c3c;'>❌ Hủy: {$cancel_at}</span><small style='display:block; color:#7f8c8d; margin-top:2px;'>Đơn đã bị hủy</small></td>";
            } else {
                $due_date = !empty($val['payment']['due_date']) ? date('d/m/y', strtotime($val['payment']['due_date'])) : '---';
                $is_overdue = (!empty($val['payment']['due_date']) && strtotime($val['payment']['due_date']) < current_time('timestamp') && $status !== 'paid') ? ' overdue' : '';
                $output .= "<td><b>" . number_format($paid_amount) . "đ</b><br><span class='date-badge{$is_overdue}'>⏳ Hạn: {$due_date}</span><small style='display:block; color:#7f8c8d; margin-top:2px;'>Còn: " . number_format($must_pay_remaining) . "đ</small></td>";
            }

            $output .= "<td>" . ($status_labels[$status] ?? $status) . "</td>";

            $nonce = wp_create_nonce('cf7_update_lead_status');
            $onchange = "var st=this.value; if(!st){return;} window.location.href='".esc_js(remove_query_arg(['cf7_lead_id', 'cf7_new_status', 'cf7_nonce']))."' + (window.location.search?'&':'?') + 'cf7_lead_id=" . intval($row->id) . "&cf7_new_status=' + encodeURIComponent(st) + '&cf7_nonce=" . esc_js($nonce) . "';";
            
            // Chỉ hiển thị option "Hoàn cọc" khi đã đóng cọc hoặc đang ở trạng thái refund
            $refund_option = '';
            if ($status === 'deposit' || $status === 'refund') {
                $refund_option = "<option value='refund' ".selected($status,'refund',false).">Hoàn cọc</option>";
            }

            // ✅ Cột Thao tác: Dropdown trạng thái
            $output .= "<td style='vertical-align: middle; text-align: center;'>";
            $output .= "<select class='change-status' onchange=\"" . $onchange . "\" style='padding: 0 10px; border: 1px solid #dfe6e9; border-radius: 6px; font-size: 13px; cursor: pointer; background: #fff; height: 34px; line-height: 34px; vertical-align: middle; margin: 0; display: inline-block; box-sizing: border-box;'>
                <option value='unpaid' ".selected($status,'unpaid',false).">Chờ xử lý</option>
                <option value='deposit' ".selected($status,'deposit',false).">Đóng cọc</option>
                <option value='paid' ".selected($status,'paid',false).">Hoàn thành</option>
                " . $refund_option . "
                <option value='cancel' ".selected($status,'cancel',false).">Hủy</option>
                </select>";
            $output .= "</td>";
            
            // ✅ Cột Thao tác: Nút Sửa
            $output .= "<td style='vertical-align: middle; text-align: center; white-space:nowrap;'>";
            $output .= "<button type='button' onclick='cf7_edit_student(" . intval($row->id) . ")' style='padding: 0 12px; background:#3498db; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; height: 34px; line-height: 34px; vertical-align: middle; margin: 0; display: inline-block; box-sizing: border-box;'>✏️ Sửa</button>";
            $output .= "</td>";
            
            $output .= "</tr>";
        }

        // Phân trang
        if ($total_pages > 1) {
            $output .= "<tr class='quan-ly-pagination'><td colspan='8' style='padding:20px; text-align:center; background:#f9f9f9;'>";
            $output .= "<div style='display:inline-flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:center;'>";
            
            // Nút Previous
            if ($current_page > 1) {
                $prev_url = add_query_arg('cf7_page', $current_page - 1, $base_url);
                $output .= "<a href='" . esc_url($prev_url) . "' style='padding:8px 16px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none; font-weight:600; transition:all 0.3s;' onmouseover='this.style.background=\"#2980b9\"' onmouseout='this.style.background=\"#3498db\"'>‹ Trước</a>";
            } else {
                $output .= "<span style='padding:8px 16px; background:#ecf0f1; color:#95a5a6; border-radius:6px; cursor:not-allowed;'>‹ Trước</span>";
            }
            
            // Số trang
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1) {
                $first_url = add_query_arg('cf7_page', 1, $base_url);
                $output .= "<a href='" . esc_url($first_url) . "' style='padding:8px 12px; background:#fff; color:#34495e; border:1px solid #ddd; border-radius:6px; text-decoration:none; font-weight:600;'>1</a>";
                if ($start_page > 2) {
                    $output .= "<span style='padding:8px 4px; color:#7f8c8d;'>...</span>";
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $current_page) {
                    $output .= "<span style='padding:8px 12px; background:linear-gradient(135deg, #3498db 0%, #2980b9 100%); color:#fff; border-radius:6px; font-weight:700;'>" . $i . "</span>";
                } else {
                    $page_url = add_query_arg('cf7_page', $i, $base_url);
                    $output .= "<a href='" . esc_url($page_url) . "' style='padding:8px 12px; background:#fff; color:#34495e; border:1px solid #ddd; border-radius:6px; text-decoration:none; font-weight:600; transition:all 0.3s;' onmouseover='this.style.borderColor=\"#3498db\"; this.style.color=\"#3498db\"' onmouseout='this.style.borderColor=\"#ddd\"; this.style.color=\"#34495e\"'>" . $i . "</a>";
                }
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    $output .= "<span style='padding:8px 4px; color:#7f8c8d;'>...</span>";
                }
                $last_url = add_query_arg('cf7_page', $total_pages, $base_url);
                $output .= "<a href='" . esc_url($last_url) . "' style='padding:8px 12px; background:#fff; color:#34495e; border:1px solid #ddd; border-radius:6px; text-decoration:none; font-weight:600;'>" . $total_pages . "</a>";
            }
            
            // Nút Next
            if ($current_page < $total_pages) {
                $next_url = add_query_arg('cf7_page', $current_page + 1, $base_url);
                $output .= "<a href='" . esc_url($next_url) . "' style='padding:8px 16px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none; font-weight:600; transition:all 0.3s;' onmouseover='this.style.background=\"#2980b9\"' onmouseout='this.style.background=\"#3498db\"'>Sau ›</a>";
            } else {
                $output .= "<span style='padding:8px 16px; background:#ecf0f1; color:#95a5a6; border-radius:6px; cursor:not-allowed;'>Sau ›</span>";
            }
            
            $output .= "</div>";
            $output .= "<div style='margin-top:12px; color:#7f8c8d; font-size:13px;'>Trang " . $current_page . " / " . $total_pages . " (Tổng: " . number_format($total_records) . " học viên)</div>";
            $output .= "</td></tr>";
        }
    } else {
        $output .= '<tr><td colspan="8" style="text-align:center; padding:20px; color:#7f8c8d;">Chưa có dữ liệu học viên.</td></tr>';
    }
    return $output;
}
add_shortcode('danh_sach_hoc_vien_html', 'cf7_get_table_rows_combined');

// ✅ Modal và JavaScript cho CRUD học viên
add_action('wp_body_open', 'cf7_inject_student_modal_body', 1);
function cf7_inject_student_modal_body() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return;
    }
    
    static $injected = false;
    if ($injected) {
        return;
    }
    $injected = true;
    
    echo cf7_student_modal_html() . cf7_student_js();
}

add_action('wp_footer', 'cf7_inject_student_modal_footer', 1);
function cf7_inject_student_modal_footer() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return;
    }
    
    static $injected = false;
    if ($injected) {
        return;
    }
    $injected = true;
    
    echo cf7_student_modal_html() . cf7_student_js();
}

// Modal HTML cho form thêm/sửa học viên
function cf7_student_modal_html() {
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    $courses_raw = $wpdb->get_results("SELECT course_key, data FROM $table_courses ORDER BY id ASC", ARRAY_A);
    $courses_options = '<option value="">-- Chọn khóa học --</option>';
    
    foreach ($courses_raw as $row) {
        $course_data = json_decode($row['data'], true);
        if ($course_data) {
            $course_key = $row['course_key'];
            $course_name = $course_data['course_name'] ?? '';
            $courses_options .= '<option value="' . esc_attr($course_key) . '">' . esc_html($course_name) . '</option>';
        }
    }
    
    return '
    <div id="cf7-student-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:30px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <h3 id="cf7-student-modal-title" style="margin:0 0 20px 0; font-size:20px; color:#34495e;">Thêm Học Viên</h3>
            <form id="cf7-student-form" onsubmit="return false;" action="#" method="post">
                <input type="hidden" id="cf7-student-action" value="create">
                <input type="hidden" id="cf7-student-id-hidden" name="student_id_hidden">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Họ và tên *</label>
                    <input type="text" id="cf7-student-name" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Số điện thoại *</label>
                    <input type="tel" id="cf7-student-phone" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Email</label>
                    <input type="email" id="cf7-student-email" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Khóa học *</label>
                    <select id="cf7-student-course" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                        ' . $courses_options . '
                    </select>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600; color:#555;">Ghi chú</label>
                    <textarea id="cf7-student-note" rows="3" placeholder="Nhập ghi chú..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px;"></textarea>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="cf7_close_student_modal()" style="padding:10px 20px; background:#95a5a6; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Hủy</button>
                    <button type="submit" style="padding:10px 20px; background:linear-gradient(135deg, #3498db 0%, #2980b9 100%); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Lưu</button>
                </div>
            </form>
        </div>
    </div>';
}

// JavaScript xử lý CRUD học viên
function cf7_student_js() {
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('cf7_student_nonce');
    return '
    <script>
    var CF7_STUDENT_AJAX_URL = "' . esc_js($ajax_url) . '";
    var CF7_STUDENT_NONCE = "' . esc_js($nonce) . '";
    
    // Mở modal
    window.cf7_open_student_modal = function(studentId) {
        console.log("🔓 Opening student modal, studentId:", studentId);
        
        function findModalAndForm(retries) {
            retries = retries || 0;
            var modal = document.getElementById("cf7-student-modal");
            var form = document.getElementById("cf7-student-form");
            
            if (!modal || !form) {
                if (retries < 10) {
                    setTimeout(function() {
                        findModalAndForm(retries + 1);
                    }, 100);
                    return;
                } else {
                    console.error("❌ Modal or form not found after 10 retries!");
                    alert("Lỗi: Không tìm thấy modal hoặc form. Vui lòng refresh trang.");
                    return;
                }
            }
            
            openModalWithElements(modal, form, studentId);
        }
        
        function openModalWithElements(modal, form, studentId) {
            console.log("✅ Modal and form found!");
            var action = document.getElementById("cf7-student-action");
            var title = document.getElementById("cf7-student-modal-title");
            var idHidden = document.getElementById("cf7-student-id-hidden");
            
            if (studentId) {
                // Chế độ sửa
                console.log("📝 Edit mode for student:", studentId);
                action.value = "update";
                title.textContent = "Sửa Học Viên";
                if (idHidden) {
                    idHidden.value = studentId;
                }
                
                // Lấy dữ liệu học viên
                var formData = new FormData();
                formData.append("action", "cf7_student_get");
                formData.append("_ajax_nonce", CF7_STUDENT_NONCE);
                formData.append("id", studentId);
                
                fetch(CF7_STUDENT_AJAX_URL, {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log("📦 GET Response:", data);
                    
                    if (data.success && data.data) {
                        var student = data.data;
                        
                        document.getElementById("cf7-student-name").value = student.name || "";
                        document.getElementById("cf7-student-phone").value = student.phone || "";
                        document.getElementById("cf7-student-email").value = student.email || "";
                        document.getElementById("cf7-student-course").value = student.course_key || "";
                        document.getElementById("cf7-student-note").value = student.note || "";
                        
                        modal.style.display = "flex";
                        modal.style.visibility = "visible";
                        modal.setAttribute("aria-hidden", "false");
                        
                        setTimeout(function() {
                            attachStudentFormSubmitHandler();
                        }, 50);
                    } else {
                        alert("Không thể tải thông tin học viên.");
                    }
                })
                .catch(error => {
                    console.error("❌ GET Error:", error);
                    alert("Có lỗi xảy ra khi tải thông tin học viên.");
                });
            } else {
                // Chế độ thêm mới
                console.log("➕ Create mode");
                action.value = "create";
                title.textContent = "Thêm Học Viên";
                if (idHidden) {
                    idHidden.value = "";
                }
                
                form.reset();
                modal.style.display = "flex";
                modal.style.visibility = "visible";
                modal.setAttribute("aria-hidden", "false");
                
                setTimeout(function() {
                    attachStudentFormSubmitHandler();
                }, 50);
            }
        }
        
        findModalAndForm(0);
    };
    
    // Đóng modal
    window.cf7_close_student_modal = function() {
        console.log("🚪 Closing student modal...");
        var modal = document.getElementById("cf7-student-modal");
        if (modal) {
            modal.style.display = "none";
            modal.style.visibility = "hidden";
            modal.setAttribute("aria-hidden", "true");
        }
    };
    
    // Sửa học viên
    window.cf7_edit_student = function(studentId) {
        cf7_open_student_modal(studentId);
    };
    
    // Hàm attach form submit handler
    function attachStudentFormSubmitHandler() {
        var form = document.getElementById("cf7-student-form");
        
        if (!form) {
            console.warn("⚠️ Student form not found yet");
            return false;
        }
        
        if (form.hasAttribute("data-submit-handler-attached")) {
            return true;
        }
        
        console.log("✅ Student form found, attaching submit handler...");
        form.setAttribute("data-submit-handler-attached", "true");
        
        form.addEventListener("submit", function(e) {
            console.log("=== 🚀 STUDENT FORM SUBMIT STARTED ===");
            
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            var actionEl = document.getElementById("cf7-student-action");
            if (!actionEl) {
                alert("Lỗi: Không tìm thấy action.");
                return false;
            }
            
            var action = actionEl.value;
            var idHidden = document.getElementById("cf7-student-id-hidden");
            var id = action === "update" ? (idHidden ? idHidden.value : 0) : 0;
            var name = document.getElementById("cf7-student-name").value.trim();
            var phone = document.getElementById("cf7-student-phone").value.trim();
            var email = document.getElementById("cf7-student-email").value.trim();
            var courseKey = document.getElementById("cf7-student-course").value;
            var note = document.getElementById("cf7-student-note").value || "";
            
            if (!name || !phone || !courseKey) {
                alert("Vui lòng điền đầy đủ thông tin bắt buộc.");
                return false;
            }
            
            var formData = new FormData();
            var ajaxAction = action === "create" ? "cf7_student_create" : "cf7_student_update";
            formData.append("action", ajaxAction);
            formData.append("_ajax_nonce", CF7_STUDENT_NONCE);
            
            if (action === "update") {
                formData.append("id", id);
            }
            
            formData.append("name", name);
            formData.append("phone", phone);
            formData.append("email", email);
            formData.append("course_key", courseKey);
            formData.append("note", note);
            
            var submitBtn = form.querySelector("button[type=submit]");
            var originalBtnText = submitBtn ? submitBtn.textContent : "Lưu";
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Đang xử lý...";
            }
            
            fetch(CF7_STUDENT_AJAX_URL, {
                method: "POST",
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                var isSuccess = false;
                if (data.success === true || data.success === "true" || data.success === 1) {
                    isSuccess = true;
                }
                
                if (isSuccess) {
                    var message = data.data && data.data.message ? data.data.message : "Thành công!";
                    alert(message);
                    
                    var modal = document.getElementById("cf7-student-modal");
                    if (modal) {
                        modal.style.display = "none";
                        modal.style.visibility = "hidden";
                        modal.setAttribute("aria-hidden", "true");
                    }
                    window.cf7_close_student_modal();
                    
                    setTimeout(function() {
                        location.reload();
                    }, 300);
                } else {
                    var errorMsg = "Có lỗi xảy ra.";
                    if (data.data) {
                        if (typeof data.data === "string") {
                            errorMsg = data.data;
                        } else if (data.data.message) {
                            errorMsg = data.data.message;
                        }
                    }
                    alert(errorMsg);
                    
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalBtnText;
                    }
                }
            })
            .catch(function(error) {
                console.error("❌ Fetch Error:", error);
                alert("Lỗi: " + error.message);
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            });
            
            return false;
        }, true);
        
        return true;
    }
    
    // Khởi tạo khi DOM ready
    document.addEventListener("DOMContentLoaded", function() {
        attachStudentFormSubmitHandler();
        
        var modal = document.getElementById("cf7-student-modal");
        if (modal) {
            modal.addEventListener("click", function(e) {
                if (e.target === modal) {
                    cf7_close_student_modal();
                }
            });
        }
    });
    </script>';
}

/**
 * DÙNG ĐÚNG LOGIC KIỂM TRA ADMIN ĐỂ ẨN NÚT TRÊN MENU
 */
add_filter('wp_get_nav_menu_items', 'cf7_filter_menu_for_admin_only', 10, 3);

function cf7_filter_menu_for_admin_only($items, $menu, $args) {
    
    // 1. Nếu ĐÃ đăng nhập VÀ CÓ quyền Admin (manage_options) -> Cho hiện menu bình thường
    if (is_user_logged_in() && current_user_can('manage_options')) {
        return $items;
    }

    // 2. Nếu KHÔNG phải Admin: Duyệt qua danh sách menu và xóa nút Quản lý
    foreach ($items as $key => $item) {
        // Kiểm tra tiêu đề nút - Ẩn cả "Quản Lý Học Viên" và "Quản Lý Khóa Học" (và các biến thể)
        $title = $item->title ?? '';
        if ($title == 'Quản Lý Học Viên' || 
            $title == 'Quản Lý Khóa Học' || 
            $title == 'Quản Lý' ||
            stripos($title, 'Quản Lý') !== false) {
            unset($items[$key]);
        }
    }

    return $items;
}