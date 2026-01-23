<?php
/*
Plugin Name: CF7 To Telegram
*/

if (!defined('ABSPATH')) exit;

// Định nghĩa đường dẫn
define('CF7_TELE_PATH', plugin_dir_path(__FILE__));

// Import các thành phần
require_once CF7_TELE_PATH . 'pkg/env-loader.php';
require_once CF7_TELE_PATH . 'includes/database.php';
require_once CF7_TELE_PATH . 'includes/sales-statistics.php';
require_once CF7_TELE_PATH . 'includes/home/submission.php';
require_once CF7_TELE_PATH . 'includes/home/outstanding-course.php';
require_once CF7_TELE_PATH . 'includes/ma/student-ma.php';
require_once CF7_TELE_PATH . 'includes/ma/course-ma.php';
require_once CF7_TELE_PATH . 'includes/landing/landing-handler.php';

// Kích hoạt tạo bảng khi plugin được activate
register_activation_hook(__FILE__, 'cf7_registration_data');

// Tự động kiểm tra và tạo bảng khi WordPress init (cho trường hợp restart WordPress)
add_action('init', 'cf7_auto_check_tables', 1);
function cf7_auto_check_tables() {
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    // Kiểm tra xem bảng có tồn tại không bằng cách query trực tiếp
    $wpdb->suppress_errors(true);
    $table_check = $wpdb->get_var("SHOW TABLES LIKE '{$table_courses}'");
    $wpdb->suppress_errors(false);
    
    // Nếu bảng courses không tồn tại, tự động tạo
    // dbDelta() sẽ chỉ tạo bảng nếu chưa có, không xóa dữ liệu cũ
    if ($table_check !== $table_courses) {
        cf7_registration_data();
    }
    
    // Ensure Google Ads course exists
    $google_course = $wpdb->get_row("SELECT id FROM {$table_courses} WHERE course_key = 'google-ads'");
    if (!$google_course) {
        $wpdb->insert($table_courses, [
            'course_key' => 'google-ads',
            'course_name' => 'Khóa học Google Ads',
            'content' => 'Khi khách chủ động tìm bạn trên Google, nhiệm vụ của bạn chỉ là xuất hiện đúng lúc. Khóa học Google Ads giúp bạn đưa sản phẩm lên top tìm kiếm, tiếp cận đúng người đang cần mua – và biến lượt tìm kiếm thành lượt bán.',
            'price' => 4900000,
            'original_price' => 8400000,
            'duration' => '12 buổi',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'schedules' => json_encode([['start' => '2026-02-01', 'end' => '2026-02-28']])
        ]);
    }

    // Ensure Facebook Ads course exists
    $facebook_course = $wpdb->get_row("SELECT id FROM {$table_courses} WHERE course_key = 'facebook-ads'");
    if (!$facebook_course) {
        $wpdb->insert($table_courses, [
            'course_key' => 'facebook-ads',
            'course_name' => 'Khóa học Facebook Ads',
            'content' => 'Làm chủ công cụ quảng cáo Facebook Ads để tiếp cận hàng triệu khách hàng tiềm năng.',
            'price' => 4500000,
            'original_price' => 7500000,
            'duration' => '10 buổi',
            'start_date' => '2026-02-05',
            'end_date' => '2026-03-05',
            'schedules' => json_encode([['start' => '2026-02-05', 'end' => '2026-03-05']])
        ]);
    }
}