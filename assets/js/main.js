window.addEventListener("DOMContentLoaded", async () => {
  try {
    await Crypto.loadKeyPair();
    Chat.init(); // your existing chat initialization
  } catch (e) {
    alert("Failed to initialize keys: " + e.message);
  }
});
