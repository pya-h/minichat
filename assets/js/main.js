window.addEventListener("DOMContentLoaded", async () => {
  try {
    await Crypto.loadKeyPair();
    Chat.init();
  } catch (e) {
    alert("Failed to initialize keys: " + e.message);
  }
});
