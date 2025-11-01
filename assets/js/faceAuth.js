// js/faceAuth.js

// Biến toàn cục để giữ stream video
let videoStream;

// Hàm này tải các mô hình AI đã huấn luyện
async function loadModels() {
    // Đường dẫn này phải tính từ file HTML (login.php)
    const MODEL_URL = '../../assets/models'; 
    
    console.log("Đang tải models...");
    try {
        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        console.log("Tải models thành công!");
        return true;
    } catch (err) {
        console.error("Lỗi khi tải models: ", err);
        return false;
    }
}

// Hàm này bật camera
async function startVideo(videoId) {
    const videoEl = document.getElementById(videoId);
    if (!videoEl) {
        console.error("Không tìm thấy element video:", videoId);
        return false;
    }

    try {
        videoStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user' },
            audio: false
        });
        videoEl.srcObject = videoStream;
        return new Promise((resolve) => {
            videoEl.onloadedmetadata = () => {
                console.log("Bật camera thành công.");
                resolve(true);
            };
        });
    } catch (err) {
        console.error("Lỗi khi bật camera: ", err);
        return false;
    }
}

// Hàm này tắt camera
function stopVideo() {
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        console.log("Đã tắt camera.");
    }
}

// Hàm này chụp ảnh, phân tích và trả về "Đặc trưng" (128 con số)
async function getFaceDescriptor(videoId) {
    const videoEl = document.getElementById(videoId);
    if (!videoEl) {
        console.error("Không tìm thấy video element.");
        return null;
    }

    console.log("Đang phân tích khuôn mặt...");

    // Tùy chọn để tăng độ chính xác
    const displaySize = { width: videoEl.width, height: videoEl.height };
    
    // Phát hiện khuôn mặt
    const detection = await faceapi.detectSingleFace(videoEl)
                                 .withFaceLandmarks()
                                 .withFaceDescriptor();

    if (!detection) {
        console.log("Không tìm thấy khuôn mặt.");
        return null;
    }

    console.log("Đã tìm thấy! Đang tính toán đặc trưng...");
    // Trả về mảng 128 con số (Float32Array)
    return detection.descriptor; 
}