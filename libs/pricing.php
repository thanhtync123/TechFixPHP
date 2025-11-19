<?php
// /TechFixPHP/libs/pricing.php

/**
 * Tính toán giá cuối cùng dựa trên dịch vụ, khu vực, ngày và khung giờ.
 *
 * @return array [finalPrice(float), notes(array<string>)]
 */
function calculateSmartQuote(float $basePrice, string $district, string $date, ?string $slot = null): array
{
    $notes = [];
    $final = $basePrice;

    $district = trim($district);
    if (strcasecmp($district, 'Quận 1') === 0) {
        $surcharge = $basePrice * 0.15;
        $final += $surcharge;
        $notes[] = 'Phụ phí trung tâm +15%';
    } elseif (strcasecmp($district, 'Hóc Môn') === 0) {
        $final += 50000;
        $notes[] = 'Phụ phí khu vực xa +50.000đ';
    }

    $dayOfWeek = DateTime::createFromFormat('Y-m-d', $date);
    if ($dayOfWeek && (int) $dayOfWeek->format('N') >= 6) {
        $weekendFee = $basePrice * 0.2;
        $final += $weekendFee;
        $notes[] = 'Phụ phí cuối tuần +20%';
    }

    if ($slot === '18:00:00') {
        $peakFee = $basePrice * 0.1;
        $final += $peakFee;
        $notes[] = 'Giờ cao điểm +10%';
    }

    return [$final, $notes];
}

