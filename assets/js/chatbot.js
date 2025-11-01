document.getElementById("open-chat").onclick = () => {
  document.getElementById("chatbot-popup").style.display = "flex";
  document.getElementById("open-chat").style.display = "none";
};
document.getElementById("close-chat").onclick = () => {
  document.getElementById("chatbot-popup").style.display = "none";
  document.getElementById("open-chat").style.display = "block";
};

document.getElementById("send-btn").onclick = async () => {
  const input = document.getElementById("user-input");
  const message = input.value.trim();
  if (!message) return;

  const chatbox = document.getElementById("chatbox");
  chatbox.innerHTML += `<div><b>Bạn:</b> ${message}</div>`;
  input.value = "";

  const response = await fetch("chatbot.php", {
    method: "POST",
    headers: {"Content-Type": "application/json"},
    body: JSON.stringify({message})
  });
  const data = await response.json();
  chatbox.innerHTML += `<div style="color:#0099ff;"><b>TechFix:</b> ${data.reply}</div>`;
  chatbox.scrollTop = chatbox.scrollHeight;
};
