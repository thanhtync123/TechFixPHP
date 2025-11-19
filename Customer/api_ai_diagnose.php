<?php
header('Content-Type: application/json');

require_once '../config/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL.']);
    exit;
}

if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Lỗi tải file lên.']);
    exit;
}

$file_tmp_path = $_FILES['media_file']['tmp_name'];
$file_type = $_FILES['media_file']['type'];

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    echo json_encode(['success' => false, 'message' => 'Thiếu cấu hình GEMINI_API_KEY.']);
    exit;
}
$model = "gemini-2.5-flash";
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$apiKey";

$base64_image = base64_encode(file_get_contents($file_tmp_path));

$prompt = "
Phân tích hình ảnh này và mô tả ngắn gọn vấn đề bằng tiếng Việt.
Chỉ trả về mô tả ngắn như: 
- 'Máy tính bị màn hình xanh'
- 'Ống nước bị rò rỉ'
- 'Điều hòa bị chảy nước'
";

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt],
                [
                    "inline_data" => [
                        "mime_type" => $file_type,
                        "data" => $base64_image
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode([
        'success' => false,
        'message' => "Lỗi từ Gemini API: $http_code",
        'response' => $response
    ]);
    exit;
}

$data = json_decode($response, true);
$ai_description = $data["candidates"][0]["content"]["parts"][0]["text"] ?? "";

if (!$ai_description) {
    echo json_encode(['success' => false, 'message' => 'Gemini không trả về kết quả.']);
    exit;
}


$sql_keywords = "SELECT sk.service_id, sk.keyword, s.name 
                 FROM service_keywords sk
                 JOIN services s ON sk.service_id = s.id";

$result_keywords = $conn->query($sql_keywords);
if ($result_keywords->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Bảng service_keywords trống.']);
    exit;
}

$service_scores = [];


while ($row = $result_keywords->fetch_assoc()) {
    $sid = $row['service_id'];
    $keyword = strtolower($row['keyword']);
    $service_name = $row['name'];

    if (!isset($service_scores[$sid])) {
        $service_scores[$sid] = [
            'id' => $sid,
            'name' => $service_name,
            'score' => 0
        ];
    }

    if (strpos(strtolower($ai_description), $keyword) !== false) {
        $service_scores[$sid]['score']++;
    }
}


usort($service_scores, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

$best_match = $service_scores[0];


if ($best_match['score'] > 0) {
    echo json_encode([
        'success' => true,
        'diagnosis_text' => $ai_description,
        'service_id' => $best_match['id'],
        'service_name' => $best_match['name']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "AI đã phân tích, nhưng không tìm thấy dịch vụ phù hợp.",
        'ai_description' => $ai_description
    ]);
}

$conn->close();
?>
