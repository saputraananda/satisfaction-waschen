<?php
session_start();

if (isset($_GET['reset'])) {
  session_destroy();
  header('Location: index.php');
  exit;
}

require_once __DIR__ . '/config/db.php';

$error     = '';   // validasi format
$duplicate = '';   // nota sudah ada di DB → trigger modal

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $no_nota = trim($_POST['no_nota'] ?? '');

  if ($no_nota === '') {
    $error = 'Nomor nota tidak boleh kosong.';
  } elseif (!preg_match('/^\d{6}$/', $no_nota)) {
    $error = 'Nomor nota harus tepat <strong>6 digit angka</strong>.';
  } else {
    $no_nota_safe = htmlspecialchars($no_nota, ENT_QUOTES, 'UTF-8');
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id FROM tr_customer_satisfaction_waschen WHERE no_nota = ? LIMIT 1");
    $stmt->bind_param('s', $no_nota);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $duplicate = $no_nota_safe;   // tampilkan modal
    } else {
      $_SESSION['no_nota'] = $no_nota;
      $_SESSION['step']    = 'csat';
      header('Location: csat.php');
      exit;
    }
    $stmt->close();
    $conn->close();
  }
}

$posted_nota = htmlspecialchars($_POST['no_nota'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Survey Kepuasan — Waschen Laundry</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Poppins', 'sans-serif'] },
          animation: {
            'fade-up': 'fadeUp .55s ease-out both',
            'card-in': 'cardIn .6s ease-out both',
            'modal-in': 'modalIn .3s cubic-bezier(.175,.885,.32,1.275) both',
          },
          keyframes: {
            fadeUp   : { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
            cardIn   : { '0%': { opacity: '0', transform: 'translateY(28px) scale(.97)' }, '100%': { opacity: '1', transform: 'translateY(0) scale(1)' } },
            modalIn  : { '0%': { opacity: '0', transform: 'scale(.92) translateY(12px)' }, '100%': { opacity: '1', transform: 'scale(1) translateY(0)' } },
          },
        },
      },
    }
  </script>
  <style>
    * { font-family: 'Poppins', sans-serif; }
    body { background: linear-gradient(135deg, #5B005F 0%, #8A4A8D 100%); }
    .bubble { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.12); animation: floatUp linear infinite; }
    @keyframes floatUp {
      0%   { transform: translateY(100vh) scale(1); opacity: .12; }
      100% { transform: translateY(-20vh) scale(1.2); opacity: 0; }
    }
    input:focus { outline: none; }
    .btn-primary { background: #5B005F; transition: background .2s, transform .15s, box-shadow .2s; }
    .btn-primary:hover:not(:disabled) { background: #430046; box-shadow: 0 8px 24px rgba(91,0,95,.35); }
    .btn-primary:active:not(:disabled) { transform: scale(0.98); }
    .btn-primary:disabled { opacity: .45; cursor: not-allowed; }
    .input-field { transition: border-color .2s, box-shadow .2s, background .2s; }
    .input-field:focus { border-color: #5B005F; background: #fff; box-shadow: 0 0 0 3px rgba(91,0,95,.1); }
    .input-field.error { border-color: #EF4444; box-shadow: 0 0 0 3px rgba(239,68,68,.1); }
    /* Modal overlay */
    #modalOverlay { transition: opacity .25s; }
    #modalOverlay.hidden { display: none; }
  </style>
</head>

<body class="min-h-screen flex items-start sm:items-center justify-center p-4 py-8 relative">

  <div id="bubblesContainer" class="fixed inset-0 pointer-events-none overflow-hidden"></div>

  <!-- ===== MODAL: Nota sudah diisi ===== -->
  <?php if ($duplicate): ?>
  <div id="modalOverlay" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5);">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm px-8 py-8 animate-modal-in text-center">
      <!-- Icon -->
      <div class="flex justify-center mb-4">
        <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background:#F3E6F5;">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#5B005F;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
        </div>
      </div>
      <h3 class="text-lg font-bold text-gray-800 mb-2">Survey Sudah Diisi</h3>
      <p class="text-sm text-gray-500 mb-1">Nomor nota</p>
      <p class="text-base font-bold mb-3" style="color:#5B005F;"><?= $duplicate ?></p>
      <p class="text-sm text-gray-400 leading-relaxed mb-6">
        Nota ini sudah pernah mengisi survey.<br>Terima kasih atas partisipasi Anda!
      </p>
      <button onclick="closeModal()" class="btn-primary w-full py-3 rounded-2xl text-white font-semibold text-sm">
        Tutup
      </button>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== CARD ===== -->
  <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md px-8 py-10 animate-card-in relative z-10">

    <!-- Logo & Brand -->
    <div class="flex flex-col items-center mb-8">
      <img src="image/waschen.png" alt="Waschen Laundry" class="h-16 w-auto mb-3 object-contain">
      <h1 class="text-xl font-bold" style="color:#5B005F;">Waschen Laundry</h1>
      <p class="text-xs font-medium text-gray-400 mt-0.5 tracking-wide uppercase">Survey Kepuasan Pelanggan</p>
    </div>

    <!-- Divider -->
    <div class="flex items-center gap-3 mb-6">
      <div class="flex-1 h-px bg-gray-100"></div>
      <span class="text-xs font-semibold text-gray-300 uppercase tracking-widest">Masukkan Nota</span>
      <div class="flex-1 h-px bg-gray-100"></div>
    </div>

    <!-- Inline error (validasi format) -->
    <?php if ($error): ?>
    <div class="mb-5 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm">
      <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
      </svg>
      <span><?= $error ?></span>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" autocomplete="off" id="notaForm">
      <label class="block text-sm font-semibold text-gray-600 mb-2" for="no_nota">Nomor Nota Transaksi</label>

      <div class="relative">
        <span class="absolute inset-y-0 left-4 flex items-center text-gray-400 pointer-events-none">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </span>
        <input
          type="text"
          id="no_nota"
          name="no_nota"
          placeholder="6 digit terakhir nomor nota"
          value="<?= $posted_nota ?>"
          maxlength="6"
          inputmode="numeric"
          pattern="[0-9]{6}"
          required
          class="input-field <?= $error ? 'error' : '' ?> w-full pl-12 pr-16 py-3.5 rounded-2xl border-2 border-gray-200 text-gray-800 font-medium text-sm bg-gray-50"
          oninput="onNotaInput(this)"
        >
        <!-- Digit counter badge -->
        <span id="digitCounter"
          class="absolute inset-y-0 right-4 flex items-center text-xs font-semibold pointer-events-none"
          style="color:#CBD5E1;">
          <span id="digitCount">0</span>/6
        </span>
      </div>

      <!-- Hint below input -->
      <p id="digitHint" class="text-xs mt-1.5 ml-1 text-gray-400">Masukkan 6 digit angka dari nomor nota Anda.</p>

      <button type="submit" id="submitBtn" disabled
        class="btn-primary mt-5 w-full py-3.5 rounded-2xl text-white font-semibold text-base shadow-md flex items-center justify-center gap-2">
        <span id="btnText">Mulai Survey</span>
        <svg id="btnIcon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
        </svg>
        <svg id="btnSpinner" class="w-5 h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </button>
    </form>

    <p class="text-center text-xs text-gray-400 mt-6 leading-relaxed">
      Data Anda aman dan hanya digunakan untuk meningkatkan<br>
      kualitas layanan <span class="font-semibold" style="color:#5B005F;">Waschen Laundry</span>.
    </p>
  </div>

  <script>
    function onNotaInput(input) {
      // Strip non-digits
      input.value = input.value.replace(/\D/g, '').slice(0, 6);

      const len     = input.value.length;
      const counter = document.getElementById('digitCount');
      const hint    = document.getElementById('digitHint');
      const submit  = document.getElementById('submitBtn');
      const badge   = document.getElementById('digitCounter');

      counter.textContent = len;

      if (len === 0) {
        badge.style.color = '#CBD5E1';
        hint.textContent  = 'Masukkan 6 digit angka dari nomor nota Anda.';
        hint.style.color  = '#94a3b8';
        input.classList.remove('error');
        submit.disabled   = true;
      } else if (len < 6) {
        badge.style.color = '#F59E0B';
        hint.textContent  = `Masih kurang ${6 - len} digit lagi.`;
        hint.style.color  = '#F59E0B';
        input.classList.remove('error');
        submit.disabled   = true;
      } else {
        badge.style.color = '#10B981';
        hint.textContent  = 'Siap! Klik Mulai Survey.';
        hint.style.color  = '#10B981';
        input.classList.remove('error');
        submit.disabled   = false;
      }
    }

    // Init counter if value pre-filled (e.g. after POST error)
    const notaInput = document.getElementById('no_nota');
    if (notaInput.value.length > 0) onNotaInput(notaInput);

    function closeModal() {
      document.getElementById('modalOverlay').classList.add('hidden');
      notaInput.value = '';
      onNotaInput(notaInput);
      notaInput.focus();
    }

    document.getElementById('notaForm').addEventListener('submit', function() {
      document.getElementById('btnText').textContent = 'Memproses...';
      document.getElementById('btnIcon').classList.add('hidden');
      document.getElementById('btnSpinner').classList.remove('hidden');
      document.getElementById('submitBtn').disabled = true;
    });

    // Floating bubbles
    const container = document.getElementById('bubblesContainer');
    for (let i = 0; i < 12; i++) {
      const size  = Math.random() * 60 + 20 | 0;
      const left  = Math.random() * 100 | 0;
      const delay = (Math.random() * 8).toFixed(1);
      const dur   = (Math.random() * 10 + 9).toFixed(1);
      const el    = document.createElement('div');
      el.className = 'bubble';
      el.style.cssText = `width:${size}px;height:${size}px;left:${left}%;bottom:-${size}px;animation-duration:${dur}s;animation-delay:${delay}s;`;
      container.appendChild(el);
    }
  </script>
</body>
</html>
