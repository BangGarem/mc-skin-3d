// ===============================
// Armor System (Optimized)
// ===============================

let currentArmorMeshes = [];
let armorTextureCache = new Map();
let armorBusy = false;

// cache THREE biar gak detect ulang terus
let _THREE_CACHE = null;
// cache TextureLoader biar gak new loader terus
let _TEX_LOADER = null;

// ===============================
// Detect THREE (lebih cepat + aman)
// ===============================
function detectTHREE(viewer) {
  if (_THREE_CACHE) return _THREE_CACHE;

  // 1) skinview3d.THREE
  if (window.skinview3d && window.skinview3d.THREE) {
    _THREE_CACHE = window.skinview3d.THREE;
    return _THREE_CACHE;
  }

  // 2) viewer.constructor.skinview3d.THREE
  if (viewer?.constructor?.skinview3d?.THREE) {
    _THREE_CACHE = viewer.constructor.skinview3d.THREE;
    return _THREE_CACHE;
  }

  // 3) viewer.renderer.constructor.THREE (fallback terakhir sesuai request)
  if (viewer?.renderer?.constructor?.THREE) {
    _THREE_CACHE = viewer.renderer.constructor.THREE;
    return _THREE_CACHE;
  }

  // BONUS fallback
  if (viewer?.renderer?.THREE) {
    _THREE_CACHE = viewer.renderer.THREE;
    return _THREE_CACHE;
  }

  // BONUS fallback terakhir: window.THREE (kalau pakai three.min.js external)
  if (window.THREE) {
    _THREE_CACHE = window.THREE;
    return _THREE_CACHE;
  }

  return null;
}

function getTextureLoader(THREE) {
  if (_TEX_LOADER) return _TEX_LOADER;
  _TEX_LOADER = new THREE.TextureLoader();
  return _TEX_LOADER;
}

// ===============================
// Texture Loader + Cache (pixel perfect)
// ===============================
function loadTextureCached(THREE, path) {
  if (armorTextureCache.has(path)) {
    return Promise.resolve(armorTextureCache.get(path));
  }

  const loader = getTextureLoader(THREE);

  return new Promise((resolve, reject) => {
    loader.load(
      path,
      (tex) => {
        // Pixel-perfect settings
        tex.magFilter = THREE.NearestFilter;
        tex.minFilter = THREE.NearestFilter;
        tex.generateMipmaps = false;

        armorTextureCache.set(path, tex);
        resolve(tex);
      },
      undefined,
      (err) => reject(err)
    );
  });
}

// ===============================
// Cleanup armor meshes (lebih bersih)
// ===============================
function removeArmor() {
  for (const mesh of currentArmorMeshes) {
    try {
      if (mesh?.parent) mesh.parent.remove(mesh);

      if (mesh?.geometry) {
        mesh.geometry.dispose();
      }

      if (mesh?.material) {
        if (Array.isArray(mesh.material)) {
          mesh.material.forEach((m) => m?.dispose && m.dispose());
        } else {
          mesh.material.dispose();
        }
      }
    } catch (e) {}
  }

  currentArmorMeshes = [];
  console.log("[armor] Armor dilepas.");
}

// ===============================
// Build material (anti tabrakan / z-fighting)
// ===============================
function createArmorMaterial(THREE, texture) {
  const mat = new THREE.MeshBasicMaterial({
    map: texture,
    transparent: true,
    alphaTest: 0.5,

    // ini penting buat ngurangin tabrakan layer dengan skin
    depthWrite: false,
    depthTest: true,

    // polygon offset = push sedikit ke depan
    polygonOffset: true,
    polygonOffsetFactor: -1,
    polygonOffsetUnits: -1,
  });

  return mat;
}

// ===============================
// Attach helper (1 mesh = 1 box)
// ===============================
function attachBoxArmor(THREE, bone, tex, size, dilation) {
  if (!bone) return null;

  const geo = new THREE.BoxGeometry(
    size[0] + dilation,
    size[1] + dilation,
    size[2] + dilation
  );

  const mat = createArmorMaterial(THREE, tex);

  const mesh = new THREE.Mesh(geo, mat);

  // sedikit offset supaya gak “nempel banget”
  // (kalau terlalu nempel, flicker)
  mesh.position.set(0, 0, 0);

  bone.add(mesh);
  currentArmorMeshes.push(mesh);

  return mesh;
}

// ===============================
// Main setArmor
// ===============================
async function setArmor(type) {
  console.log("[armor] Mencoba memasang armor:", type);

  if (armorBusy) return;
  armorBusy = true;

  try {
    // Proteksi viewer
    if (!window.viewer) {
      console.warn("[armor] Viewer belum siap.");
      return;
    }

    // Tunggu viewer siap (kalau index.php punya waitForViewer)
    if (window.waitForViewer) {
      try {
        await window.waitForViewer();
      } catch (e) {
        console.warn("[armor] Viewer belum siap (timeout).");
        return;
      }
    }

    const v = window.viewer;
    const player = v.playerObject;

    if (!player || !player.skin) {
      console.warn("[armor] playerObject/skin belum siap.");
      return;
    }

    // Detect THREE
    const THREE = detectTHREE(v);
    if (!THREE) {
      console.error("[armor] Gagal mendapatkan akses ke THREE engine.");
      console.log("[armor] DEBUG viewer:", v);
      console.log("[armor] DEBUG window.skinview3d:", window.skinview3d);
      console.log("[armor] DEBUG window.THREE:", window.THREE);
      return;
    }

    // bersihkan armor lama
    removeArmor();

    // FIX PATH: Minecraft armor texture itu lowercase
    const armorName = String(type).toLowerCase();
    const path1 = `assets/minecraft/textures/models/armor/${armorName}_layer_1.png`;
    const path2 = `assets/minecraft/textures/models/armor/${armorName}_layer_2.png`;

    let tex1, tex2;

    // load layer_1 wajib ada
    try {
      tex1 = await loadTextureCached(THREE, path1);
    } catch (err) {
      console.error("[armor] Gagal load texture layer_1:", path1, err);
      return;
    }

    // layer_2 opsional (fallback ke layer_1)
    try {
      tex2 = await loadTextureCached(THREE, path2);
    } catch (err) {
      console.warn("[armor] layer_2 tidak ada, fallback ke layer_1:", path2);
      tex2 = tex1;
    }

    // ===== Pasang armor (box overlay) =====
    // NOTE: angka dilation kecil = lebih rapih + lebih ringan
    // (yang terlalu besar bikin tabrakan parah)
    const dHead = 0.25;
    const dBody = 0.22;
    const dArm = 0.20;
    const dLeg = 0.18;

    // Layer 1 (helmet/chest/boots)
    attachBoxArmor(THREE, player.skin.head, tex1, [8, 8, 8], dHead);
    attachBoxArmor(THREE, player.skin.body, tex1, [8, 12, 4], dBody);
    attachBoxArmor(THREE, player.skin.rightArm, tex1, [4, 12, 4], dArm);
    attachBoxArmor(THREE, player.skin.leftArm, tex1, [4, 12, 4], dArm);
    attachBoxArmor(THREE, player.skin.rightLeg, tex1, [4, 12, 4], dLeg);
    attachBoxArmor(THREE, player.skin.leftLeg, tex1, [4, 12, 4], dLeg);

    // Layer 2 (leggings)
    // biasanya leggings = layer_2
    attachBoxArmor(THREE, player.skin.body, tex2, [8, 12, 4], 0.14);
    attachBoxArmor(THREE, player.skin.rightLeg, tex2, [4, 12, 4], 0.12);
    attachBoxArmor(THREE, player.skin.leftLeg, tex2, [4, 12, 4], 0.12);

    console.log("[armor] Armor terpasang:", armorName);

  } finally {
    // unlock (biar gak double click spam)
    setTimeout(() => {
      armorBusy = false;
    }, 80);
  }
}

window.setArmor = setArmor;
window.removeArmor = removeArmor;
