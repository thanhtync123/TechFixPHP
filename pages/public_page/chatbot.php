<!-- chatbot.php -->
<style>
/* ===== Chatbot Button ===== */
.chatbot-btn {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background-color: #0078ff;
  color: white;
  border: none;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  font-size: 28px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  transition: transform 0.3s ease;
  z-index: 1000;
}

.chatbot-btn:hover {
  transform: scale(1.1);
}

/* ===== Chatbot Popup ===== */
.chatbot-popup {
  position: fixed;
  bottom: 90px;
  right: 20px;
  width: 340px;
  height: 420px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
  display: none;
  flex-direction: column;
  overflow: hidden;
  z-index: 1001;
}

.chatbot-header {
  background-color: #0078ff;
  color: white;
  text-align: center;
  padding: 10px;
  font-weight: bold;
  position: relative;
}

.chatbot-header button {
  position: absolute;
  right: 10px;
  top: 5px;
  background: transparent;
  color: white;
  border: none;
  font-size: 18px;
  cursor: pointer;
}

.chatbot-messages {
  flex: 1;
  padding: 10px;
  overflow-y: auto;
  background: #f5f5f5;
}

.chatbot-input {
  display: flex;
  border-top: 1px solid #ddd;
}

.chatbot-input input {
  flex: 1;
  padding: 10px;
  border: none;
  outline: none;
}

.chatbot-input button {
  background-color: #0078ff;
  color: white;
  border: none;
  padding: 0 20px;
  cursor: pointer;
}
</style>

<!-- ===== Chatbot Button ===== -->
<button id="chatbotBtn" class="chatbot-btn">💬</button>

<!-- ===== Chatbot Popup ===== -->
<div id="chatbotPopup" class="chatbot-popup">
  <div class="chatbot-header">
    Chatbot hỗ trợ
    <button id="closeChatbot">×</button>
  </div>
  <div class="chatbot-messages" id="chatMessages">
    <div><b>Bot:</b> Xin chào 👋! Tôi có thể giúp gì cho bạn hôm nay?</div>
  </div>
  <div class="chatbot-input">
    <input type="text" id="userMessage" placeholder="Nhập tin nhắn...">
    <button id="sendBtn">Gửi</button>
  </div>
</div>

<script>
const chatbotBtn = document.getElementById('chatbotBtn');
const chatbotPopup = document.getElementById('chatbotPopup');
const closeChatbot = document.getElementById('closeChatbot');
const sendBtn = document.getElementById('sendBtn');
const userMessage = document.getElementById('userMessage');
const chatMessages = document.getElementById('chatMessages');

// Mở/đóng chatbot
chatbotBtn.addEventListener('click', () => {
  chatbotPopup.style.display = 'flex';
  chatbotBtn.style.display = 'none';
});

closeChatbot.addEventListener('click', () => {
  chatbotPopup.style.display = 'none';
  chatbotBtn.style.display = 'block';
});

// Gửi tin nhắn
sendBtn.addEventListener('click', sendMessage);
userMessage.addEventListener('keypress', e => {
  if (e.key === 'Enter') sendMessage();
});

function appendMessage(sender, text) {
  const div = document.createElement('div');
  div.innerHTML = `<b>${sender}:</b> ${text}`;
  chatMessages.appendChild(div);
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Hàm gửi tin nhắn tới API (có thể thay thế bằng API của bạn)
async function sendMessage() {
  const msg = userMessage.value.trim();
  if (!msg) return;
  appendMessage('Bạn', msg);
  userMessage.value = '';

  try {
    // --- Gửi tin đến server chatbot (ví dụ: chatbot_api.php) ---
    const res = await fetch('/TechFixPHP/pages/public_page/chatbot_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg })
    });
    const data = await res.json();
    appendMessage('Bot', data.reply || 'Xin lỗi, tôi chưa hiểu ý bạn.');
  } catch (err) {
    appendMessage('Bot', 'Lỗi kết nối API.');
  }
}
</script>
