<?php

// Shortcode hiển thị thống kê doanh thu
add_shortcode('cf7_revenue_stats', 'cf7_render_revenue_stats');

function cf7_render_revenue_stats() {
    if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('view_admin_menu'))) {
        return '<div style="text-align:center; padding:20px;">Vui lòng đăng nhập Admin để xem thống kê.</div>';
    }

    global $wpdb;
    $table_leads = $wpdb->prefix . 'cf7_leads';
    $table_courses = $wpdb->prefix . 'cf7_courses';

    // --- LẤY DANH SÁCH KHÓA HỌC ĐỂ TRA CỨU GIÁ ---
    $db_courses_raw = $wpdb->get_results("SELECT course_key, data FROM $table_courses", OBJECT_K);
    $db_courses_list = [];
    foreach ($db_courses_raw as $key => $row) {
        $course_data = json_decode($row->data, true);
        if ($course_data) {
            $db_courses_list[$key] = (object)[
                'price' => floatval($course_data['price'] ?? 0),
                'course_name' => $course_data['course_name'] ?? ''
            ];
        }
    }

    // --- XỬ LÝ LỌC ---
    $current_period = isset($_GET['stats_period']) ? sanitize_text_field(wp_unslash($_GET['stats_period'])) : 'month';
    $filter_date = isset($_GET['stats_date']) ? sanitize_text_field(wp_unslash($_GET['stats_date'])) : '';
    $filter_course = isset($_GET['stats_course']) ? trim(sanitize_text_field(wp_unslash($_GET['stats_course']))) : ''; 
    
    // URL hiện tại (xóa params lọc để build lại link)
    $base_url = remove_query_arg(['stats_period', 'stats_date', 'stats_course']);

    // Query dữ liệu
    $all_leads = $wpdb->get_results("SELECT data, created_at FROM $table_leads", ARRAY_A);

    // Biến thống kê tổng hợp
    $debug_logs = [];
    $total_students = 0;
    $total_revenue = 0; // Tổng giá trị khóa học
    $total_paid_full = 0; // Thực thu: Đã thanh toán 100%
    $total_deposited_only = 0; // Thực thu: Chỉ mới cọc
    $total_remaining = 0; // Tổng phải thu (Công nợ)
    
    // Biến cho biểu đồ tròn (Payment Status)
    $paid_count = 0;
    $deposit_count = 0;
    $unpaid_count = 0;
    $cancel_count = 0;
    $refund_count = 0;

    // Biến cho biểu đồ cột (Trend)
    $chart_trend_data = []; 
    
    $today = date('Y-m-d');
    $this_year = date('Y');
    $this_month = date('m');
    $this_week = date('W');

    // Khởi tạo khung dữ liệu biểu đồ cột dựa trên Period
    $trend_labels = [];
    $trend_values = [];
    
    if ($current_period == 'week') {
        // Tuần này: Mon -> Sun
        $start_week = strtotime('monday this week');
        for ($i=0; $i<7; $i++) {
            $d = date('Y-m-d', strtotime("+$i days", $start_week));
            $chart_trend_data[$d] = 0;
            $trend_labels[$d] = date('d/m', strtotime($d)); // Label ngắn gọn
        }
    } elseif ($current_period == 'day') {
        // Hôm nay: 0h -> 23h
        for ($i=0; $i<24; $i++) {
            $h = str_pad($i, 2, '0', STR_PAD_LEFT);
            $key = $h;
            $chart_trend_data[$key] = 0;
            $trend_labels[$key] = $h . "h";
        }
    } elseif ($current_period == 'month') {
        // Tháng này: 1 -> End
        $days_in_month = date('t');
        for ($i=1; $i<=$days_in_month; $i++) {
            $d = date('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT);
            $chart_trend_data[$d] = 0;
            $trend_labels[$d] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }
    } elseif ($current_period == 'year') {
        // Năm nay: Jan -> Dec
        for ($i=1; $i<=12; $i++) {
            $m = str_pad($i, 2, '0', STR_PAD_LEFT);
            $key = date('Y') . "-$m";
            $chart_trend_data[$key] = 0;
            $trend_labels[$key] = "T$i";
        }
    }
    // Note: 'day' và 'all' xử lý dynamic bên dưới hoặc mặc định

    foreach ($all_leads as $row) {
        $data = json_decode($row['data'], true);
        if (!$data) continue;

        $stats = $data['stats_meta'] ?? [];
        $created_at = $data['created_at'] ?? $row['created_at']; 
        $row_date_ymd = date('Y-m-d', strtotime($created_at));
        $row_month_ym = date('Y-m', strtotime($created_at));
        $row_year = date('Y', strtotime($created_at));

        // Logic lọc thời gian
        $include = true;
        if (!empty($filter_date)) {
            // Updated to use stats_meta for consistency with student-ma.php
            if (($stats['day'] ?? '') !== $filter_date) $include = false;
        } else {
            switch ($current_period) {
                case 'day':
                    if (($stats['day'] ?? '') !== $today) $include = false;
                    break;
                case 'week':
                    if (($stats['week'] ?? '') !== $this_week || ($stats['year'] ?? '') !== $this_year) $include = false;
                    break;
                case 'month':
                    if (($stats['month'] ?? '') !== $this_month || ($stats['year'] ?? '') !== $this_year) $include = false;
                    break;
                case 'year':
                    if (($stats['year'] ?? '') !== $this_year) $include = false;
                    break;
                case 'all':
                default:
                    break;
            }
        }

        if (!$include) continue;

        // Tính toán
        // Tính toán
        $c_key = $data['course']['key'] ?? '';
        
        // Filter by course if selected
        if (!empty($filter_course)) {
            // Case-insensitive comparison for robustness
            if (strtolower(trim($c_key)) !== strtolower(trim($filter_course))) {
                continue;
            }
        }

        $course_info = $db_courses_list[$c_key] ?? null;
        $full_price = $course_info ? $course_info->price : floatval($data['course']['price'] ?? 0);

        $status = $data['payment']['status'] ?? 'unpaid';
        $paid_amount = floatval($data['payment']['paid_amount'] ?? 0);
        $deposit_amount = floatval($data['payment']['deposit'] ?? ($full_price * 0.2));

        // Bỏ qua đơn hủy/hoàn cọc khỏi doanh thu dự kiến
        if ($status === 'cancel' || $status === 'refund') {
            if ($status === 'cancel') $cancel_count++;
            if ($status === 'refund') $refund_count++;
            continue; 
        }

        $total_students++;
        $total_revenue += $full_price;
        
        if ($status === 'paid') {
            $total_paid_full += $full_price; 
            $paid_count++;
        } elseif ($status === 'deposit') {
            $total_deposited_only += $deposit_amount; 
            $total_remaining += ($full_price - $deposit_amount);
            $deposit_count++;
        } else { // unpaid
            $total_remaining += $full_price;
            $unpaid_count++;
        }

        // --- Aggregation cho Chart Trend ---
        // Nếu mode 'year', group theo tháng. Các mode khác group theo ngày.
        if ($current_period == 'year') {
            if (isset($chart_trend_data[$row_month_ym])) {
                $chart_trend_data[$row_month_ym] += $full_price;
            }
        } elseif ($current_period == 'week' || $current_period == 'month') {
            if (isset($chart_trend_data[$row_date_ymd])) {
                $chart_trend_data[$row_date_ymd] += $full_price;
            }
        } elseif ($current_period == 'day') {
            $h = date('H', strtotime($created_at));
            if (isset($chart_trend_data[$h])) {
                $chart_trend_data[$h] += $full_price;
            }
        } else {
            // Day hoặc All -> Dynamic accumulation
            // Với 'All', ta group theo Năm hoặc Tháng (chọn Tháng cho chi tiết vừa phải)
             if ($current_period == 'all') {
                 if (!isset($chart_trend_data[$row_month_ym])) {
                     $chart_trend_data[$row_month_ym] = 0;
                     $trend_labels[$row_month_ym] = $row_month_ym;
                 }
                 $chart_trend_data[$row_month_ym] += $full_price;
             }
        }
    }

    // Sort lại chart data cho 'all' (các mode kia đã init theo thứ tự)
    if ($current_period == 'all') {
        ksort($chart_trend_data);
        ksort($trend_labels);
    }

    // --- RENDER UI ---
    ob_start();
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-dashboard {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px 0;
            background: #f4f7fa;
            padding: 20px;
            border-radius: 12px;
            /* Full width breakout */
            width: 98vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -49vw;
            margin-right: -49vw;
            box-sizing: border-box;
        }
        .stats-dashboard-inner {
            max-width: 1400px;
            margin: 0 auto;
        }
        .stats-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .stats-filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            color: #64748b;
            background: #fff;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .stats-filter-btn.active, .stats-filter-btn:hover {
            background: #0064E0;
            color: #fff;
            border-color: #0064E0;
        }
        .stats-grid-top {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .stats-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }
        .stats-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stats-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }
        .stats-card .sub-value {
            font-size: 13px;
            color: #95a5a6;
            margin-top: 5px;
        }
        .stats-card.highlight .value { color: #0064E0; }
        .stats-card.success .value { color: #2ecc71; }
        .stats-card.warning .value { color: #f39c12; }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .charts-container { grid-template-columns: 1fr; }
        }
        .chart-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .chart-box h4 { margin: 0 0 15px 0; color: #34495e; align-self: flex-start; }
    </style>

    <div class="stats-dashboard">
        <div class="stats-dashboard-inner">
        <!-- Filter Bar -->
        <div class="stats-filters">
            <strong style="margin-right:10px;">Thống kê theo:</strong>
            <?php
            $periods = ['day' => 'Hôm nay', 'week' => 'Tuần này', 'month' => 'Tháng này', 'year' => 'Năm nay', 'all' => 'Tất cả'];
            foreach ($periods as $k => $label) {
                // Keep the current course filter when changing period
                $url = add_query_arg(['stats_period' => $k], $base_url);
                if (!empty($filter_course)) {
                    $url = add_query_arg('stats_course', $filter_course, $url);
                }
                
                $active = ($current_period === $k && empty($filter_date)) ? 'active' : '';
                echo "<a href='" . esc_url($url) . "' class='stats-filter-btn {$active}'>{$label}</a>";
            }
            ?>
            
            <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
                <select id="stats_filter_course" onchange="cf7_stats_apply_filter()" style="padding: 0 15px; border-radius: 20px; border: 1px solid #e2e8f0; height: 35px; outline:none; cursor:pointer; font-size:13px; color:#64748b; background:#fff; max-width: 180px;">
                    <option value="">-- Tất cả khóa học --</option>
                    <?php foreach ($db_courses_list as $k => $c): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($filter_course, $k); ?>><?php echo esc_html($c->course_name); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="date" id="stats_filter_date" value="<?php echo esc_attr($filter_date); ?>" onchange="cf7_stats_apply_filter()" style="padding: 0 15px; border-radius: 20px; border: 1px solid #e2e8f0; height: 35px; outline:none; font-size:13px; color:#64748b; background:#fff;">
                
                <!-- Hidden inputs to store current state for JS -->
                <input type="hidden" id="stats_current_period" value="<?php echo esc_attr($current_period); ?>">
            </div>


        </div>

        <div class="stats-grid-top">
            <div class="stats-card">
                <h3>Tổng Học Viên</h3>
                <div class="value"><?php echo number_format($total_students); ?></div>
                <div class="sub-value">Đơn đăng ký hợp lệ</div>
            </div>
            <div class="stats-card highlight">
                <h3>Doanh Thu (Dự kiến)</h3>
                <div class="value"><?php echo number_format($total_revenue); ?>đ</div>
                <div class="sub-value">Tổng giá trị khóa học</div>
            </div>
            <div class="stats-card success">
                <h3>Thực Thu (Đã xong)</h3>
                <div class="value"><?php echo number_format($total_paid_full); ?>đ</div>
                <div class="sub-value">Đã thanh toán 100%</div>
            </div>
            <div class="stats-card" style="border-bottom: 3px solid #3498db;">
                <h3 style="color:#3498db;">Tiền Cọc (Đã nhận)</h3>
                <div class="value" style="color:#3498db;"><?php echo number_format($total_deposited_only); ?>đ</div>
                <div class="sub-value">Chỉ tính số tiền cọc</div>
            </div>
            <div class="stats-card warning">
                <h3>Công Nợ (Phải thu)</h3>
                <div class="value"><?php echo number_format($total_remaining); ?>đ</div>
                <div class="sub-value">Số tiền còn thiếu</div>
            </div>
        </div>
        
        <?php if ($total_students == 0): ?>
            <div style="text-align:center; padding: 40px; background:#fff; border-radius:12px; color:#7f8c8d; border: 1px dashed #dce4ec;">
                <div style="font-size:40px; margin-bottom:10px;">📭</div>
                <div style="font-size:16px; font-weight:600;">Chưa có dữ liệu</div>
                <div style="font-size:13px; margin-top:5px;">Không tìm thấy học viên nào phù hợp với bộ lọc hiện tại.</div>
            </div>
        <?php else: ?>
        
        <!-- Charts Area -->
        <div class="charts-container">
            <!-- Column Chart: Revenue Trend -->
            <div class="chart-box">
                <h4>Biểu đồ Doanh Thu (<?php echo $current_period == 'year' ? 'Theo tháng' : ($current_period == 'all' ? 'Theo tháng' : ($current_period == 'day' ? 'Theo giờ' : 'Theo ngày')); ?>)</h4>
                <div style="width: 100%; height: 250px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart: Payment Status -->
            <div class="chart-box">
                <h4>Tỷ lệ Thanh toán</h4>
                <div style="width: 100%; height: 250px; position: relative;">
                    <canvas id="paymentChart"></canvas>
                </div>
                <div style="margin-top:15px; font-size:13px; color:#666;">
                    Đã hủy: <b style="color:#e74c3c"><?php echo $cancel_count; ?></b> | Hoàn cọc: <b style="color:#9b59b6"><?php echo $refund_count; ?></b>
                </div>
            </div>
        </div>
        <?php endif; ?>
        </div>
    </div>

    <script>
    // Global filter function
    function cf7_stats_apply_filter() {
        var course = document.getElementById('stats_filter_course').value;
        var date   = document.getElementById('stats_filter_date').value;
        var period = document.getElementById('stats_current_period').value;

        // Reset period if date is selected (logic from original code)
        if (date) {
            period = ''; 
        }

        var url = new URL(window.location.href);
        
        if (course) { url.searchParams.set('stats_course', course); } 
        else { url.searchParams.delete('stats_course'); }

        if (date) { 
            url.searchParams.set('stats_date', date); 
            url.searchParams.delete('stats_period'); // Ensure period is removed if date is present
        } else {
             url.searchParams.delete('stats_date');
             if (period) url.searchParams.set('stats_period', period);
        }

        window.location.href = url.toString();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Payment Chart (Doughnut) ---
        const ctxPayment = document.getElementById('paymentChart').getContext('2d');
        new Chart(ctxPayment, {
            type: 'doughnut',
            data: {
                labels: ['Hoàn thành', 'Đã cọc', 'Chờ xử lý'],
                datasets: [{
                    data: [<?php echo $paid_count; ?>, <?php echo $deposit_count; ?>, <?php echo $unpaid_count; ?>],
                    backgroundColor: ['#2ecc71', '#0064E0', '#f39c12'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 15, font: { size: 12 } }
                    }
                }
            }
        });

        // --- 2. Trend Chart (Bar) ---
        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        const trendLabels = <?php echo json_encode(array_values($trend_labels)); ?>;
        const trendData = <?php echo json_encode(array_values($chart_trend_data)); ?>;

        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: trendData,
                    backgroundColor: '#0064E0',
                    borderRadius: 4,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumSignificantDigits: 3 }).format(value);
                            },
                            font: { size: 10 }
                        },
                        grid: { borderDash: [2, 4], color: '#f0f0f0' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
