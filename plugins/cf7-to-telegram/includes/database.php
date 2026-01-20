<?php
function cf7_registration_data() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // 1. Bảng lưu học viên
    $table_leads = $wpdb->prefix . 'cf7_leads';
    $sql_leads = "CREATE TABLE $table_leads (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        data longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY created_at (created_at)
    ) $charset_collate;";
    dbDelta($sql_leads);

    // 2. Bảng lưu danh mục khóa học (NoSQL - lưu JSON)
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    // Kiểm tra xem bảng đã tồn tại chưa (để biết có phải bảng mới không)
    $table_exists_before = $wpdb->get_var("SHOW TABLES LIKE '$table_courses'") === $table_courses;
    
    $sql_courses = "CREATE TABLE $table_courses (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        course_key varchar(50) NOT NULL,
        data longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY course_key (course_key)
    ) $charset_collate;";
    dbDelta($sql_courses);

    // 3. Chèn dữ liệu mẫu dạng JSON CHỈ KHI bảng mới được tạo (bảo vệ dữ liệu cũ)
    if (!$table_exists_before) {
        $check_exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_courses");
        if ($check_exists == 0) {
            // Tính toán thời gian cho các khóa học (mỗi khóa 1 tháng)
            $base_date = date('Y-m-01'); // Ngày đầu tháng hiện tại
            $next_month = date('Y-m-01', strtotime('+1 month'));
            
            // Khóa học 1: Web Design (Tháng 1)
            $course1_data = [
                'course_key' => 'web-design',
                'course_name' => 'Khóa học Web Design',
                'price' => 5000000,
                'teacher' => 'Teacher A',
                'duration' => '1 tháng',
                'start_date' => $base_date,
                'end_date' => date('Y-m-t', strtotime($base_date)), // Ngày cuối tháng
                'schedules' => [[
                    'start' => $base_date,
                    'end' => date('Y-m-t', strtotime($base_date))
                ]]
            ];
            
            // Khóa học 2: Fullstack PHP (Tháng 2)
            $course2_data = [
                'course_key' => 'fullstack-php',
                'course_name' => 'Khóa học Fullstack PHP',
                'price' => 8000000,
                'teacher' => 'Teacher B',
                'duration' => '1 tháng',
                'start_date' => $next_month,
                'end_date' => date('Y-m-t', strtotime($next_month)),
                'schedules' => [[
                    'start' => $next_month,
                    'end' => date('Y-m-t', strtotime($next_month))
                ]]
            ];
            
            // Khóa học 3: ReactJS (Tháng 3)
            $course3_start = date('Y-m-01', strtotime('+2 months'));
            $course3_data = [
                'course_key' => 'reactjs',
                'course_name' => 'Khóa học ReactJS',
                'price' => 7000000,
                'teacher' => 'Teacher C',
                'duration' => '1 tháng',
                'start_date' => $course3_start,
                'end_date' => date('Y-m-t', strtotime($course3_start)),
                'schedules' => [[
                    'start' => $course3_start,
                    'end' => date('Y-m-t', strtotime($course3_start))
                ]]
            ];
            
            // Khóa học 4: WordPress (Tháng 4)
            $course4_start = date('Y-m-01', strtotime('+3 months'));
            $course4_data = [
                'course_key' => 'wordpress',
                'course_name' => 'Khóa học WordPress',
                'price' => 4500000,
                'teacher' => 'Teacher D',
                'duration' => '1 tháng',
                'start_date' => $course4_start,
                'end_date' => date('Y-m-t', strtotime($course4_start)),
                'schedules' => [[
                    'start' => $course4_start,
                    'end' => date('Y-m-t', strtotime($course4_start))
                ]]
            ];
            
            // Chèn dữ liệu dạng JSON
            $wpdb->insert($table_courses, [
                'course_key' => 'web-design',
                'data' => wp_json_encode($course1_data, JSON_UNESCAPED_UNICODE)
            ]);
            $wpdb->insert($table_courses, [
                'course_key' => 'fullstack-php',
                'data' => wp_json_encode($course2_data, JSON_UNESCAPED_UNICODE)
            ]);
            $wpdb->insert($table_courses, [
                'course_key' => 'reactjs',
                'data' => wp_json_encode($course3_data, JSON_UNESCAPED_UNICODE)
            ]);
            $wpdb->insert($table_courses, [
                'course_key' => 'wordpress',
                'data' => wp_json_encode($course4_data, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }
}