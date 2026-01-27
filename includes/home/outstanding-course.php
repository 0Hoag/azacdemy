<?php

// Shortcode hiển thị danh sách khóa học nổi bật
add_shortcode('cf7_highlight_courses', 'cf7_render_highlight_courses');

function cf7_render_highlight_courses($atts) {
    // Lấy dữ liệu khóa học từ database
    global $wpdb;
    $table_courses = $wpdb->prefix . 'cf7_courses';
    
    // Kiểm tra bảng tồn tại
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_courses'") != $table_courses) {
        return '<p>Chưa có dữ liệu khóa học.</p>';
    }

    $results = $wpdb->get_results("SELECT * FROM $table_courses ORDER BY id ASC");

    if (empty($results)) {
        return '<div class="course-highlight-wrapper"><h2 class="course-main-title">Khóa học nổi bật</h2><p style="text-align:center;">Chưa có khóa học nào.</p></div>';
    }

    ob_start();
    ?>
    <div class="course-highlight-wrapper">
        <h2 class="course-main-title">Khóa học nổi bật</h2>
        
        <div id="dynamic-course-list" class="course-grid">
            <?php foreach ($results as $row): 
                $data = json_decode($row->data, true);
                $name = isset($data['course_name']) ? $data['course_name'] : '';
                
                // Ưu tiên lấy field 'content', nếu không có thì lấy 'description'
                $desc = isset($data['content']) ? $data['content'] : (isset($data['description']) ? $data['description'] : '');
                
                $price = isset($data['price']) ? floatval($data['price']) : 0;
                
                // Lấy giá gốc từ DB nếu có, nếu không thì tự tính
                if (isset($data['original_price']) && !empty($data['original_price'])) {
                    $original_price = floatval($data['original_price']);
                } else {
                    $original_price = $price * 1.3; 
                }
                
                $course_key = isset($data['course_key']) ? $data['course_key'] : '';
                $start_date = isset($data['start_date']) ? $data['start_date'] : '';
                $end_date = isset($data['end_date']) ? $data['end_date'] : '';
                $duration = isset($data['duration']) ? $data['duration'] : '';
                $schedules = isset($data['schedules']) ? $data['schedules'] : [];

                // Backward compatibility: If no schedules but has start/end date, create one
                if (empty($schedules) && (!empty($start_date) || !empty($end_date))) {
                    $schedules[] = ['start' => $start_date, 'end' => $end_date];
                }
                
                // ✅ FILTER: Ẩn các lịch đã quá hạn (start_date < today)
                if (!empty($schedules)) {
                    $today_date = date('Y-m-d');
                    foreach ($schedules as $k => $sc) {
                        if (!empty($sc['start']) && $sc['start'] < $today_date) {
                            unset($schedules[$k]);
                        }
                    }
                }
            ?>
            <div class="course-card">
                <h3 class="course-title"><?php echo esc_html($name); ?></h3>
                <div class="course-desc">
                    <?php echo wp_kses_post(wpautop($desc)); ?>
                </div>
                
                <?php if (!empty($schedules) || !empty($duration)): ?>
                <div class="course-date-info">
                    <?php if (!empty($duration)): ?>
                        <div class="date-item"><span class="date-label">⏱️</span> <?php echo esc_html($duration); ?></div>
                    <?php endif; ?>
                    
                    <?php foreach ($schedules as $idx => $sch): 
                        $s = isset($sch['start']) ? $sch['start'] : '';
                        if (empty($s)) continue;
                    ?>
                    <div class="date-item">
                        <span class="date-label">📅 Khai giảng<?php echo count($schedules) > 1 ? ' K' . ($idx+1) : ''; ?>:</span> 
                        <?php echo date_i18n('d/m/Y', strtotime($s)); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="course-divider"></div>
                
                <div class="course-pricing">
                    <div class="price-original">Giá gốc: <?php echo number_format($original_price, 0, ',', '.'); ?>đ</div>
                    <div class="price-sale">
                        <span class="amount"><?php echo number_format($price, 0, ',', '.'); ?></span>
                        <span class="unit">/ Khóa</span>
                    </div>
                </div>
                
                <button type="button" class="course-register-btn" onclick="cf7_open_register_modal('<?php echo esc_attr($course_key); ?>', '<?php echo esc_attr($name); ?>')">
                    Đăng ký ngay
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    .course-highlight-wrapper {
        max-width: 1200px;
        margin: 40px auto;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    }
    
    .course-main-title {
        text-align: center;
        font-size: 28px;
        color: #333;
        margin-bottom: 40px;
        font-weight: 700;
        text-transform: uppercase;
    }
    
    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        padding: 0 15px;
    }
    
    .course-card {
        background: #fff;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        border: 1px solid #eee;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    }
    
    .course-title {
        font-size: 22px;
        color: #444;
        margin-bottom: 20px;
        font-weight: 700;
        text-align: center;
        line-height: 1.4;
        min-height: 60px; /* Đồng bộ chiều cao tiêu đề */
    }
    
    .course-desc {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 20px;
        flex-grow: 1; /* Đẩy giá xuống dưới cùng */
        text-align: justify;
    }
    
    .course-date-info {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
        color: #555;
        border-left: 3px solid #3498db;
    }
    
    .course-date-info .date-item {
        margin-bottom: 5px;
    }
    
    .course-date-info .date-item:last-child {
        margin-bottom: 0;
    }
    
    .course-date-info .date-label {
        font-weight: 700;
        color: #333;
        margin-right: 5px;
    }
    
    .course-divider {
        height: 1px;
        background: #3498db;
        width: 100%; /* Full width theo yêu cầu */
        margin: 0 0 20px 0;
    }
    
    .course-pricing {
        margin-bottom: 25px;
    }
    
    .price-original {
        font-size: 14px;
        color: #999;
        text-decoration: line-through;
        margin-bottom: 5px;
    }
    
    .price-sale {
        color: #333;
        display: flex;
        align-items: baseline;
    }
    
    .price-sale .amount {
        font-size: 32px;
        font-weight: 700;
        color: #444;
    }
    
    .price-sale .unit {
        font-size: 14px;
        color: #666;
        margin-left: 5px;
    }
    
    .course-register-btn {
        width: 100%;
        padding: 8px 15px; /* Giảm padding xuống 8px để nút ngắn hơn theo chiều dọc */
        background: #0064E0;
        color: #FFFFFF;
        border: none;
        border-radius: 50px; /* Bo tròn kiểu Pill shape như mẫu */
        font-weight: 700;
        font-size: 15.52px; /* Theo yêu cầu chính xác */
        font-family: -apple-system, "system-ui", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        line-height: 1.2; /* Đảm bảo chiều cao dòng gọn gàng */
        cursor: pointer;
        transition: all 0.3s ease;
        box-sizing: border-box; /* Đảm bảo kích thước tính cả padding */
    }
    
    .course-register-btn:hover {
        background: #0056b3;
        box-shadow: 0 5px 15px rgba(0, 100, 224, 0.4);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .course-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
    function cf7_open_register_modal(courseKey, courseName) {
        // Hàm này sẽ được gọi khi click ĐĂNG KÝ
        // Bạn có thể tích hợp mở Popup Contact Form 7 ở đây
        // Ví dụ: Tìm form CF7 trong trang và điền thông tin
        console.log('Đăng ký khóa học:', courseName, courseKey);
        
        // Demo: Scroll tới form đăng ký nếu có
        var registerForm = document.querySelector('.wpcf7-form');
        if (registerForm) {
            registerForm.scrollIntoView({behavior: 'smooth'});
            // Nếu có field select khóa học trong CF7, hãy điền value vào
            // var select = registerForm.querySelector('select[name="course-select"]');
            // if(select) select.value = courseKey;
        } else {
            alert('Vui lòng liên hệ hotline để đăng ký khóa học: ' + courseName);
        }
    }
    </script>
    <?php
    return ob_get_clean();
}