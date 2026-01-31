<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$uploadDir = "uploads";
$defaultSkin = "default.png";

// Pastikan folder uploads ada
if (!is_dir(__DIR__ . "/" . $uploadDir)) {
    mkdir(__DIR__ . "/" . $uploadDir, 0755, true);
}

// Ambil parameter skin
$skinParam = isset($_GET['skin']) ? $_GET['skin'] : "";

// Amankan nama file
$skinFile = $skinParam ? basename($skinParam) : $defaultSkin;

// Validasi ekstensi hanya PNG
if (!preg_match('/^[a-zA-Z0-9_\-]+\.(png)$/', $skinFile)) {
    $skinFile = $defaultSkin;
}

// Path final
$skinPath = $uploadDir . "/" . $skinFile;

// Kalau file tidak ada, fallback
if (!file_exists(__DIR__ . "/" . $skinPath)) {
    $skinPath = $uploadDir . "/" . $defaultSkin;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Minecraft Skin Viewer</title>

  <style>
    :root{
      --bg:#0f0f10;
      --card:#1a1a1c;
      --text:#ffffff;
      --muted:rgba(255,255,255,.75);
      --btn:#2b2b30;
      --btnHover:#3a3a42;
      --danger:#ff4d4d;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: Arial, sans-serif;
      background:var(--bg);
      color:var(--text);
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:18px;
    }
    .wrap{
      width:min(720px, 100%);
      display:flex;
      flex-direction:column;
      gap:14px;
      align-items:center;
    }
    .card{
      width:100%;
      background:var(--card);
      border-radius:18px;
      padding:14px;
      box-shadow:0 10px 25px rgba(0,0,0,.35);
      display:flex;
      flex-direction:column;
      gap:12px;
    }
    h3{margin:0;font-size:18px}
    .panel{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      justify-content:center;
      align-items:center;
    }
    button{
      padding:10px 14px;
      border-radius:12px;
      border:none;
      cursor:pointer;
      font-weight:700;
      background:var(--btn);
      color:var(--text);
      transition:.15s;
    }
    button:hover{background:var(--btnHover)}
    button:disabled{
      opacity:.55;
      cursor:not-allowed;
    }
    input[type="file"]{
      max-width:240px;
      color:var(--muted);
    }
    .note{
      font-size:12px;
      color:var(--muted);
      text-align:center;
      word-break:break-all;
    }
    .viewerBox{
      width:100%;
      display:flex;
      justify-content:center;
      align-items:center;
    }
    canvas{
      width:min(320px, 100%);
      height:420px;
      border-radius:16px;
      background:#111;
    }
    .errorBox{
      display:none;
      padding:10px 12px;
      border-radius:12px;
      background:rgba(255,77,77,.12);
      border:1px solid rgba(255,77,77,.35);
      color:#ffd1d1;
      font-size:13px;
    }
    .errorBox.show{display:block;}
  </style>
</head>

<body>
<div class="wrap">

  <div class="card">
    <h3>Minecraft Skin</h3>

    <div id="errBox" class="errorBox"></div>

    <!-- Upload -->
    <form action="upload.php" method="POST" enctype="multipart/form-data" class="panel">
      <input type="file" name="skin" accept="image/png" required>
      <button type="submit">Upload Skin</button>
    </form>

    <!-- Controls -->
    <div class="panel">
      <button id="btnDiamond" onclick="safeArmor('diamond')">Diamond</button>
      <button id="btnIron" onclick="safeArmor('iron')">Iron</button>
      <button id="btnNetherite" onclick="safeArmor('netherite')">Netherite</button>
      <button id="btnRemove" onclick="safeRemoveArmor()">Remove Armor</button>
    </div>

    <div class="note">
      Skin aktif: <b><?php echo htmlspecialchars($skinPath, ENT_QUOTES); ?></b>
    </div>

    <div class="viewerBox">
      <canvas id="skin_container" width="320" height="420"></canvas>
    </div>
  </div>

</div>

<!-- 1) THREE external -->
<script src="javascript/three.min.js"></script>

<!-- 2) Skinview3D -->
<script src="javascript/skinview3d.bundle.js"></script>

<script>
(function () {
  const errBox = document.getElementById("errBox");
  const showError = (msg) => {
    console.error(msg);
    errBox.textContent = msg;
    errBox.classList.add("show");
  };

  if (!window.THREE) {
    showError("THREE tidak ter-load. Pastikan javascript/three.min.js ada dan path benar.");
    return;
  }

  if (!window.skinview3d) {
    showError("skinview3d.bundle.js gagal load. Pastikan file ada di javascript/skinview3d.bundle.js");
    return;
  }

  const canvas = document.getElementById("skin_container");
  if (!canvas) {
    showError("Canvas #skin_container tidak ditemukan.");
    return;
  }

  const SKIN_URL = "<?php echo htmlspecialchars($skinPath, ENT_QUOTES); ?>";
  console.log("[debug] skin url:", SKIN_URL);

  // ===== Create viewer (global) =====
  window.viewer = new skinview3d.SkinViewer({
    canvas,
    width: canvas.clientWidth || 320,
    height: canvas.clientHeight || 420,
    skin: SKIN_URL
  });

  // ===== Performance settings =====
  viewer.autoRotate = true;
  viewer.autoRotateSpeed = 0.8;
  viewer.zoom = 0.9;
  viewer.fov = 70;

  // DPR adaptif (biar ringan)
  try {
    const dpr = Math.min(window.devicePixelRatio || 1, 1.25);
    viewer.renderer.setPixelRatio(dpr);
  } catch (e) {}

  // Resize responsif
  function resizeViewer() {
    try {
      const w = Math.max(260, Math.floor(canvas.clientWidth || 320));
      const h = Math.max(360, Math.floor(canvas.clientHeight || 420));
      viewer.setSize(w, h);
    } catch (e) {}
  }
  window.addEventListener("resize", resizeViewer, { passive: true });
  resizeViewer();

  // Pause render saat tab tidak aktif (hemat CPU)
  document.addEventListener("visibilitychange", () => {
    if (!window.viewer) return;
    if (document.hidden) {
      viewer.autoRotate = false;
    } else {
      viewer.autoRotate = true;
    }
  });

  // waitForViewer (buat armor.js)
  window.waitForViewer = function(timeoutMs = 6000) {
    return new Promise((resolve, reject) => {
      const start = performance.now();

      const tick = () => {
        if (window.viewer && window.viewer.playerObject && window.viewer.scene && window.viewer.renderer) {
          return resolve(window.viewer);
        }
        if (performance.now() - start > timeoutMs) {
          return reject(new Error("Timeout: viewer belum siap"));
        }
        requestAnimationFrame(tick);
      };

      tick();
    });
  };

  console.log("[debug] viewer ready:", window.viewer);

  // ===== Safe UI wrapper =====
  const btns = [
    document.getElementById("btnDiamond"),
    document.getElementById("btnIron"),
    document.getElementById("btnNetherite"),
    document.getElementById("btnRemove"),
  ].filter(Boolean);

  function lockButtons(lock) {
    btns.forEach(b => b.disabled = !!lock);
  }

  window.safeArmor = async function(type) {
    if (!window.setArmor) {
      showError("armor.js belum ter-load atau setArmor tidak ditemukan.");
      return;
    }
    lockButtons(true);
    try {
      await window.waitForViewer();
      await window.setArmor(type);
    } catch (e) {
      console.warn(e);
    } finally {
      lockButtons(false);
    }
  };

  window.safeRemoveArmor = async function() {
    lockButtons(true);
    try {
      if (window.removeArmor) window.removeArmor();
    } finally {
      lockButtons(false);
    }
  };
})();
</script>

<!-- 3) Armor -->
<script src="javascript/armor.js"></script>

</body>
</html>
