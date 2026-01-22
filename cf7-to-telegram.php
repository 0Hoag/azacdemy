<?php
/*
Plugin Name: CF7 To Telegram
*/

if (!defined('ABSPATH')) exit;

// Định nghĩa đường dẫn
define('CF7_TELE_PATH', plugin_dir_path(__FILE__));

// Import các thành phần
require_once CF7_TELE_PATH . 'includes/database.php';
require_once CF7_TELE_PATH . 'includes/sales-statistics.php';
require_once CF7_TELE_PATH . 'includes/home/submission.php';
require_once CF7_TELE_PATH . 'includes/home/outstanding-course.php';
require_once CF7_TELE_PATH . 'includes/ma/student-ma.php';
require_once CF7_TELE_PATH . 'includes/ma/course-ma.php';

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
}