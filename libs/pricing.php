<?php
// File: /TechFixPHP/libs/pricing.php

/**
 * =================================================================
 * PHẦN 1: TÍNH TOÁN GIÁ DỊCH VỤ
 * =================================================================
 */

/**
 * Tính toán giá cuối cùng dựa trên dịch vụ, khu vực, ngày và khung giờ.
 *
 * @param float $basePrice Giá gốc dịch vụ
 * @param string $district Quận/Huyện
 * @param string $date Ngày hẹn (Y-m-d)
 * @param string|null $slot Khung giờ (H:i:s)
 * @return array [finalPrice(float), notes(array<string>)]
 */
function calculateSmartQuote(float $basePrice, string $district, string $date, ?string $slot = null): array
{
    $notes = [];
    $final = $basePrice;

    $district = trim($district);
    
    // 1. Phụ phí khu vực
    if (strcasecmp($district, 'Quận 1') === 0) {
        $surcharge = $basePrice * 0.15;
        $final += $surcharge;
        $notes[] = 'Phụ phí trung tâm +15%';
    } elseif (strcasecmp($district, 'Hóc Môn') === 0) {
        $final += 50000;
        $notes[] = 'Phụ phí khu vực xa +50.000đ';
    }

    // 2. Phụ phí cuối tuần (Thứ 7, CN)
    $dayOfWeek = DateTime::createFromFormat('Y-m-d', $date);
    if ($dayOfWeek && (int) $dayOfWeek->format('N') >= 6) {
        $weekendFee = $basePrice * 0.2;
        $final += $weekendFee;
        $notes[] = 'Phụ phí cuối tuần +20%';
    }

    // 3. Phụ phí giờ cao điểm
    if ($slot === '18:00:00') {
        $peakFee = $basePrice * 0.1;
        $final += $peakFee;
        $notes[] = 'Giờ cao điểm +10%';
    }

    return [$final, $notes];
}

/**
 * =================================================================
 * PHẦN 2: GEOLOCATION (CHO TÍNH NĂNG BẢN ĐỒ)
 * =================================================================
 */

/**
 * Lấy tọa độ (Latitude, Longitude) từ địa chỉ bằng OpenStreetMap (Nominatim API).
 * Hoàn toàn miễn phí, không cần API Key.
 * * @param string $address Địa chỉ cụ thể + Quận/Huyện
 * @return array|null Trả về ['lat' => float, 'lng' => float] hoặc null nếu không tìm thấy
 */
function getCoordinates(string $address): ?array
{
    // Thêm 'Vietnam' để tăng độ chính xác
    $addressFull = $address . ', Vietnam';
    $search_query = urlencode($addressFull);
    
    // Nominatim yêu cầu phải có User-Agent để tránh bị chặn
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: TechFixProject/1.0 (contact@techfix.com)\r\n"
        ]
    ];
    
    $context = stream_context_create($opts);
    $url = "https://nominatim.openstreetmap.org/search?q={$search_query}&format=json&limit=1";
    
    try {
        // Gọi API
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null; // Lỗi mạng
        }

        $data = json_decode($response, true);
        
        // Nếu tìm thấy kết quả
        if (!empty($data) && isset($data[0])) {
            return [
                'lat' => (float) $data[0]['lat'],
                'lng' => (float) $data[0]['lon']
            ];
        }
    } catch (Exception $e) {
        error_log("Geo Error: " . $e->getMessage());
    }
    
    return null; // Không tìm thấy tọa độ
}
?>