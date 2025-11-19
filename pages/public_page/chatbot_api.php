<?php
header('Content-Type: application/json');

// ====== Lấy tin nhắn người dùng ======
$data = json_decode(file_get_contents("php://input"), true);
$message = trim($data["message"] ?? "");

if ($message === "") {
    echo json_encode(["reply" => "⚠️ Vui lòng nhập nội dung tin nhắn."]);
    exit;
}

// ====== Cấu hình Gemini API ======
$apiKey = "AIzaSyBXWyyldE0HBLMv3m5xJWv5FjLr2FniKE8";
// Mới (ĐÚNG):
$model = "gemini-2.0-flash";
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$apiKey";

// ====== Gửi yêu cầu đến Gemini ======
$payload = json_encode([
    "contents" => [[
        "role" => "user",
        "parts" => [[
            "text" => "Bạn là chatbot hỗ trợ khách hàng TECHFIX, nói chuyện thân thiện, tự nhiên, và chỉ trả lời bằng tiếng Việt.\n\nNgười dùng: $message"
        ]]
    ]]
]);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ====== Kiểm tra phản hồi ======
if ($httpcode !== 200) {
    echo json_encode([
        "reply" => "❌ Lỗi API ($httpcode): " . $response
    ]);
    exit;
}

$result = json_decode($response, true);
$reply = $result["candidates"][0]["content"]["parts"][0]["text"] ?? "Xin lỗi, tôi chưa hiểu ý bạn.";
echo json_encode(["reply" => $reply]);
?>
