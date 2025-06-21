const Crypto = (() => {
  // Cache of loaded public keys: username -> CryptoKey (public)
  const publicKeyCache = new Map();

  // Our own RSA key pair
  let privateKey = null;
  let publicKey = null;

  // Current logged-in username (must be set from outside)
  let currentUser = null;

  // --- Helpers ---

  // Convert JWK RSA public key JSON object to CryptoKey
  async function importPublicKey(jwk) {
    return crypto.subtle.importKey(
      "jwk",
      jwk,
      {
        name: "RSA-OAEP",
        hash: "SHA-256",
      },
      true,
      ["encrypt"]
    );
  }

  // Export CryptoKey public key to JWK object
  async function exportPublicKey(key) {
    return crypto.subtle.exportKey("jwk", key);
  }

  // Generate a new RSA key pair and save private key to localStorage
  async function generateKeyPair() {
    const keyPair = await crypto.subtle.generateKey(
      {
        name: "RSA-OAEP",
        modulusLength: 2048,
        publicExponent: new Uint8Array([1, 0, 1]),
        hash: "SHA-256",
      },
      true,
      ["encrypt", "decrypt"]
    );

    const exportedPublic = await exportPublicKey(keyPair.publicKey);
    const exportedPrivate = await crypto.subtle.exportKey(
      "jwk",
      keyPair.privateKey
    );

    localStorage.setItem("minichat_public_key", JSON.stringify(exportedPublic));
    localStorage.setItem(
      "minichat_private_key",
      JSON.stringify(exportedPrivate)
    );

    return keyPair;
  }

  // Load RSA key pair from localStorage or generate new
  async function loadKeyPair() {
    currentUser = window.CURRENT_USER; // must be set globally

    let pubStr = localStorage.getItem("minichat_public_key");
    let privStr = localStorage.getItem("minichat_private_key");

    if (pubStr && privStr) {
      try {
        const pubJwk = JSON.parse(pubStr);
        const privJwk = JSON.parse(privStr);
        const pubKey = await crypto.subtle.importKey(
          "jwk",
          pubJwk,
          {
            name: "RSA-OAEP",
            hash: "SHA-256",
          },
          true,
          ["encrypt"]
        );
        const privKey = await crypto.subtle.importKey(
          "jwk",
          privJwk,
          {
            name: "RSA-OAEP",
            hash: "SHA-256",
          },
          true,
          ["decrypt"]
        );
        publicKey = pubKey;
        privateKey = privKey;
      } catch {
        // corrupted keys, generate new
        const pair = await generateKeyPair();
        publicKey = pair.publicKey;
        privateKey = pair.privateKey;
      }
    } else {
      const pair = await generateKeyPair();
      publicKey = pair.publicKey;
      privateKey = pair.privateKey;
    }

    // Upload public key to server (save_key.php)
    await uploadPublicKey();

    return { publicKey, privateKey };
  }

  // Upload your own public key JSON to server
  async function uploadPublicKey() {
    if (!publicKey) throw new Error("No public key loaded");
    const jwk = await exportPublicKey(publicKey);

    const res = await fetch("api/save_key.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ publicKey: jwk }),
    });
    if (!res.ok) {
      const err = await res.json();
      throw new Error(err.error || "Failed to upload public key");
    }
  }

  // Fetch public key JSON from server for a username
  async function fetchPublicKey(username) {
    const res = await fetch(
      `api/get_public_key.php?username=${encodeURIComponent(username)}`
    );
    if (!res.ok) return null;
    const data = await res.json();
    if (!data.publicKey) return null;
    try {
      return JSON.parse(data.publicKey);
    } catch {
      return null;
    }
  }

  // Create public key for a user on server (dummy placeholder)
  async function createPublicKeyForUser(username) {
    const res = await fetch("api/create_key_for_user.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username }),
    });
    if (!res.ok) {
      const err = await res.json();
      throw new Error(err.error || "Failed to create public key for user.");
    }
    const data = await res.json();
    if (!data.publicKey)
      throw new Error("No public key returned from create_key_for_user.php");
    return JSON.parse(data.publicKey);
  }

  // Get CryptoKey for username, fetch or create if missing
  async function getPublicKey(username) {
    if (username === currentUser) return publicKey;
    if (publicKeyCache.has(username)) return publicKeyCache.get(username);

    let jwk = await fetchPublicKey(username);
    if (!jwk) {
      jwk = await createPublicKeyForUser(username);
    }
    if (!jwk)
      throw new Error(`Public key for user ${username} not found or created`);

    const cryptoKey = await importPublicKey(jwk);
    publicKeyCache.set(username, cryptoKey);
    return cryptoKey;
  }

  // Encrypt plaintext for username
  async function encrypt(plaintext, username) {
    const key = await getPublicKey(username);
    const enc = new TextEncoder();
    const data = enc.encode(plaintext);
    const encrypted = await crypto.subtle.encrypt(
      { name: "RSA-OAEP" },
      key,
      data
    );
    // Convert ArrayBuffer to base64
    return btoa(String.fromCharCode(...new Uint8Array(encrypted)));
  }

  // Decrypt base64 ciphertext with our private key
  async function decrypt(base64) {
    if (!privateKey) throw new Error("Private key not loaded");
    const binary = Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
    const decrypted = await crypto.subtle.decrypt(
      { name: "RSA-OAEP" },
      privateKey,
      binary
    );
    const dec = new TextDecoder();
    return dec.decode(decrypted);
  }

  return {
    loadKeyPair,
    encrypt,
    decrypt,
  };
})();
