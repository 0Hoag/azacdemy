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
            
            // Mốc thời gian (Mỗi khóa 1 tháng nối tiếp nhau)
            $m1_start = date('Y-m-01'); // Tháng hiện tại
            $m1_end   = date('Y-m-t');
            
            $m2_start = date('Y-m-01', strtotime('+1 month'));
            $m2_end   = date('Y-m-t', strtotime('+1 month'));
            
            $m3_start = date('Y-m-01', strtotime('+2 month'));
            $m3_end   = date('Y-m-t', strtotime('+2 month'));

            $courses_to_insert = [
                // 1. Khóa Facebook Ads
                [
                    'course_key' => 'facebook-ads',
                    'data' => [
                        'course_name' => 'Khóa học Facebook Ads',
                        'content' => 'Bạn không cần phải là dân marketing, chỉ cần có sản phẩm và mong muốn bán được hàng. Khóa học Facebook Ads giúp bạn biết cách chạy quảng cáo đúng tệp khách, đúng thông điệp, đúng ngân sách để mỗi đồng bỏ ra đều mang lại đơn hàng thật.',
                        'price' => 4900000,
                        'original_price' => 8600000,
                        'duration' => '12 buổi',
                        'start_date' => $m1_start,
                        'end_date' => $m1_end
                    ]
                ],
                // 2. Khóa Google Ads
                [
                    'course_key' => 'google-ads',
                    'data' => [
                        'course_name' => 'Khóa học Google Ads',
                        'content' => 'Khi khách chủ động tìm bạn trên Google, nhiệm vụ của bạn chỉ là xuất hiện đúng lúc. Khóa học Google Ads giúp bạn đưa sản phẩm lên top tìm kiếm, tiếp cận đúng người đang cần mua – và biến lượt tìm kiếm thành lượt bán.',
                        'price' => 4900000,
                        'original_price' => 8400000,
                        'duration' => '12 buổi',
                        'start_date' => $m2_start,
                        'end_date' => $m2_end
                    ]
                ],
                // 3. Khóa Tiktok Ads
                [
                    'course_key' => 'tiktok-ads',
                    'data' => [
                        'course_name' => 'Khóa học Tiktok Ads',
                        'content' => 'TikTok không chỉ để xem giải trí, mà là mỏ vàng bán hàng nếu bạn biết cách khai thác. Khóa học TikTok Ads hướng dẫn bạn tạo nội dung dễ viral, chạy quảng cáo đúng insight và chốt đơn ngay cả khi bạn mới bắt đầu từ con số “0”.',
                        'price' => 4900000,
                        'original_price' => 8400000,
                        'duration' => '12 buổi',
                        'start_date' => $m3_start,
                        'end_date' => $m3_end
                    ]
                ],
                // 4. Combo Facebook & Tiktok (Học tháng 1 và tháng 3)
                [
                    'course_key' => 'combo-fb-tt',
                    'data' => [
                        'course_name' => 'Combo Facebook Ads & Tiktok Ads',
                        'content' => 'Kết hợp nhu cầu chủ động và bị động, giúp thương hiệu xuất hiện đúng lúc – đúng nơi – đúng khách hàng, tối đa hóa tỷ lệ chuyển đổi quảng cáo trực tuyến.',
                        'price' => 8500000,
                        'original_price' => 17000000,
                        'duration' => '36 buổi',
                        'start_date' => $m1_start,
                        'end_date' => $m3_end
                    ]
                ],
                // 5. Combo Facebook & Google (Học tháng 1 và tháng 2)
                [
                    'course_key' => 'combo-fb-gg',
                    'data' => [
                        'course_name' => 'Combo Facebook Ads & Google Ads',
                        'content' => 'Làm chủ hai nền tảng bán hàng mạnh nhất hiện nay, xây dựng hệ thống quảng cáo đa kênh giúp mở rộng tệp khách hàng và tối ưu doanh thu bền vững.',
                        'price' => 8500000,
                        'original_price' => 17000000,
                        'duration' => '24 buổi',
                        'start_date' => $m1_start,
                        'end_date' => $m2_end
                    ]
                ],
                // 6. Marketing toàn diện (Học tất cả 3 tháng)
                [
                    'course_key' => 'marketing-total',
                    'data' => [
                        'course_name' => 'Khóa học Marketing toàn diện',
                        'content' => 'Giải pháp đào tạo toàn diện 3 nền tảng quảng cáo lớn nhất facebook, tiktok, Google, giúp bạn xây dựng hệ thống marketing đa kênh và tăng trưởng doanh thu dài hạn.',
                        'price' => 13900000,
                        'original_price' => 25400000,
                        'duration' => '36 buổi',
                        'start_date' => $m1_start,
                        'end_date' => $m3_end
                    ]
                ]
            ];

            // Thực hiện chèn dữ liệu
            foreach ($courses_to_insert as $item) {
                $wpdb->insert($table_courses, [
                    'course_key' => $item['course_key'],
                    'data' => wp_json_encode($item['data'], JSON_UNESCAPED_UNICODE)
                ]);
            }
        }
    }
}