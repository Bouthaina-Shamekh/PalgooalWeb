document.addEventListener("DOMContentLoaded", function () {
  const chat = document.getElementById("whatsapp-chat");
  const btn = document.getElementById("whatsapp-btn");
  const close = document.getElementById("whatsapp-close");
  const audio = document.getElementById("whatsapp-audio");
  const whatsappLink = document.getElementById("whatsapp-link");

  // فتح تلقائي بعد 60 ثانية
  setTimeout(() => openChat(), 60000);

  // فتح تلقائي إذا زار صفحات مهمة
  if (window.location.href.includes("/pricing") || window.location.href.includes("/hosting")) {
    setTimeout(() => openChat(), 15000);
  }

  // فتح/إغلاق عند الضغط
  btn.addEventListener("click", () => openChat());
  close.addEventListener("click", () => closeChat());
  document.addEventListener("click", (e) => {
    if (!chat.contains(e.target) && !btn.contains(e.target)) closeChat();
  });

  function openChat() {
    if (chat.classList.contains("hidden")) {
      chat.classList.remove("hidden");
      setTimeout(() => {
        chat.classList.remove("opacity-0", "translate-y-4");
        audio.play().catch(() => {});
        showChatSequence();
      }, 10);
    }
  }

  function closeChat() {
    chat.classList.add("opacity-0", "translate-y-4");
    setTimeout(() => chat.classList.add("hidden"), 300);
  }

  function showChatSequence() {
    const msg1 = document.getElementById("msg-1");
    const msg2 = document.getElementById("msg-2");
    const msg3 = document.getElementById("msg-3");
    const buttons = document.getElementById("support-buttons");
    const typing = document.getElementById("typing-indicator");

    typing.classList.remove("hidden");
    setTimeout(() => {
      typing.classList.add("hidden");
      msg1.classList.remove("hidden");
      setTimeout(() => {
        typing.classList.remove("hidden");
        setTimeout(() => {
          typing.classList.add("hidden");
          msg2.classList.remove("hidden");
          setTimeout(() => {
            buttons.classList.remove("hidden");
            setTimeout(() => {
              typing.classList.remove("hidden");
              setTimeout(() => {
                typing.classList.add("hidden");
                msg3.classList.remove("hidden");
              }, 800);
            }, 1500);
          }, 500);
        }, 1000);
      }, 500);
    }, 1200);

    // رسالة تحفيزية إضافية بعد 15 ثانية من فتح المحادثة
    setTimeout(() => {
      const reminder = document.createElement("div");
      reminder.className = "self-start bg-yellow-100 dark:bg-yellow-800 px-4 py-2 rounded-2xl max-w-[90%] text-yellow-900 dark:text-yellow-100 text-sm shadow";
      reminder.innerHTML = "❓ إذا كنت بحاجة لمساعدة في اختيار الخدمة المناسبة، أنا هنا لمساعدتك ✋";
      document.getElementById("whatsapp-messages").appendChild(reminder);
    }, 15000);
  }

  // رابط المحادثة مع الصفحة
  const phoneNumber = "970598663901";
  const message = `مرحبًا، لقد زرت هذه الصفحة وأرغب في الاستفسار:\n${window.location.href}`;
  whatsappLink.href = `https://api.whatsapp.com/send?phone=${phoneNumber}&text=${encodeURIComponent(message)}`;
});

function sendWhatsApp(service) {
  const phone = "970598663901";
  const page = window.location.href;
  const message = `مرحبًا، أود الحصول على دعم بخصوص: ${service}\nالصفحة: ${page}`;
  const url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(message)}`;
  window.open(url, '_blank');
}