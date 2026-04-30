<?php
session_start();

if (empty($_SESSION['no_nota']))    { header('Location: index.php'); exit; }
if (empty($_SESSION['csat_score'])) { header('Location: csat.php');  exit; }
if (!isset($_SESSION['nps_score'])) { header('Location: nps.php');   exit; }

require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed_tags = [
        'Kecepatan Layanan', 'Kualitas Cucian', 'Harga / Tarif',
        'Keramahan Staff',   'Kemudahan Pemesanan', 'Kebersihan Tempat',
        'Ketepatan Waktu',   'Packaging / Pembungkusan',
    ];

    $raw_tags = $_POST['tags'] ?? [];
    $clean_tags = [];
    if (is_array($raw_tags)) {
        foreach ($raw_tags as $tag) {
            if (in_array($tag, $allowed_tags, true)) $clean_tags[] = $tag;
        }
    }

    $feedback_tags = implode(', ', $clean_tags);
    $feedback_text = trim(htmlspecialchars($_POST['feedback_text'] ?? '', ENT_QUOTES, 'UTF-8'));
    if (strlen($feedback_text) > 2000) $feedback_text = substr($feedback_text, 0, 2000);

    $no_nota      = $_SESSION['no_nota'];
    $csat_score   = (int)$_SESSION['csat_score'];
    $csat_label   = $_SESSION['csat_label'];
    $nps_score    = (int)$_SESSION['nps_score'];
    $nps_category = $_SESSION['nps_category'];
    $ip           = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ua           = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    $conn = getConnection();
    $stmt = $conn->prepare(
        "INSERT INTO tr_customer_satisfaction_waschen
            (no_nota, csat_score, csat_label, nps_score, nps_category, feedback_tags, feedback_text, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sisisssss', $no_nota, $csat_score, $csat_label, $nps_score, $nps_category, $feedback_tags, $feedback_text, $ip, $ua);

    if ($stmt->execute()) {
        $_SESSION['done_csat']  = $csat_score;
        $_SESSION['done_label'] = $csat_label;
        $_SESSION['done_nps']   = $nps_score;
        $_SESSION['done_cat']   = $nps_category;

        unset($_SESSION['csat_score'], $_SESSION['csat_label'],
              $_SESSION['nps_score'],  $_SESSION['nps_category'], $_SESSION['step']);

        header('Location: thankyou.php');
        exit;
    } else {
        $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        error_log('DB error: ' . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}

$no_nota    = htmlspecialchars($_SESSION['no_nota'],      ENT_QUOTES, 'UTF-8');
$csat_score = (int)$_SESSION['csat_score'];
$csat_label = htmlspecialchars($_SESSION['csat_label'],   ENT_QUOTES, 'UTF-8');
$nps_score  = (int)$_SESSION['nps_score'];
$nps_cat    = htmlspecialchars($_SESSION['nps_category'], ENT_QUOTES, 'UTF-8');
$csat_emoji = ['','😭','😞','😐','😊','🤩'][$csat_score] ?? '';
$nps_emoji  = $nps_score <= 6 ? '😔' : ($nps_score <= 8 ? '😌' : '🤩');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masukan Anda — Waschen Alora</title>
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
            'fade-up': 'fadeUp .5s ease-out both',
            'card-in': 'cardIn .55s ease-out both',
          },
          keyframes: {
            fadeUp: { '0%': { opacity: '0', transform: 'translateY(18px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
            cardIn: { '0%': { opacity: '0', transform: 'translateY(28px) scale(.97)' }, '100%': { opacity: '1', transform: 'translateY(0) scale(1)' } },
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
      0%   { transform: translateY(100vh); opacity: .12; }
      100% { transform: translateY(-20vh); opacity: 0; }
    }
    .tag-chip { cursor: pointer; transition: all .2s; user-select: none; }
    .tag-chip:hover { transform: scale(1.04); }
    .tag-chip.selected { transform: scale(1.06); box-shadow: 0 4px 14px rgba(91,0,95,.2); }
    textarea:focus { outline: none; border-color: #5B005F !important; box-shadow: 0 0 0 3px rgba(91,0,95,.1); }
    .btn-primary { background: #5B005F; transition: background .2s, transform .15s, box-shadow .2s; }
    .btn-primary:hover { background: #430046; box-shadow: 0 8px 24px rgba(91,0,95,.3); }
    .btn-primary:active { transform: scale(0.98); }
    .char-counter { transition: color .2s; }
    .progress-bar { transition: width .5s cubic-bezier(.4,0,.2,1); }
  </style>
</head>
<body class="min-h-screen flex items-start sm:items-center justify-center p-4 py-8 relative">

  <div id="bubblesContainer" class="fixed inset-0 pointer-events-none overflow-hidden"></div>

  <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg px-8 py-10 animate-card-in relative z-10">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-5">
      <a href="nps.php?back=1" class="text-gray-400 hover:text-gray-600 transition-colors p-1" title="Kembali ke NPS">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </a>
      <div class="flex-1">
        <p class="text-xs font-semibold uppercase tracking-widest" style="color:#5B005F;">Waschen Alora</p>
        <p class="text-xs text-gray-400">Nota: <strong class="text-gray-600"><?= $no_nota ?></strong></p>
      </div>
      <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-3 py-1 rounded-full">3 / 3</span>
    </div>

    <!-- Progress -->
    <div class="w-full bg-gray-100 rounded-full h-1.5 mb-7 overflow-hidden">
      <div class="progress-bar h-1.5 rounded-full" style="width:100%; background:#5B005F;"></div>
    </div>

    <!-- Recap strip -->
    <div class="flex gap-3 mb-7 animate-fade-up" style="animation-delay:.05s">
      <div class="flex-1 flex items-center gap-2 rounded-2xl px-3 py-2.5 border" style="background:#F6F1F7;border-color:#E9D5EA;">
        <span class="text-xl"><?= $csat_emoji ?></span>
        <div>
          <p class="text-xs font-medium text-gray-400">CSAT</p>
          <p class="text-xs font-semibold text-gray-700"><?= $csat_label ?></p>
        </div>
      </div>
      <div class="flex-1 flex items-center gap-2 rounded-2xl px-3 py-2.5 border" style="background:#F6F1F7;border-color:#E9D5EA;">
        <span class="text-xl"><?= $nps_emoji ?></span>
        <div>
          <p class="text-xs font-medium text-gray-400">NPS</p>
          <p class="text-xs font-semibold text-gray-700"><?= $nps_score ?>/10 &middot; <?= $nps_cat ?></p>
        </div>
      </div>
    </div>

    <!-- Title -->
    <div class="text-center mb-6 animate-fade-up" style="animation-delay:.1s">
      <div class="inline-flex items-center gap-2 text-xs font-semibold px-4 py-1.5 rounded-full mb-3" style="background:#F3E6F5;color:#5B005F;">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
        Langkah Terakhir
      </div>
      <h2 class="text-xl font-bold text-gray-800 leading-snug">
        Ada yang ingin Anda <span style="color:#5B005F;">sampaikan</span>?
      </h2>
      <p class="text-gray-400 text-sm mt-1.5">Pilih area masukan <span class="font-medium text-gray-500">(opsional)</span></p>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm">
      <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
      </svg>
      <?= $error ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="feedbackForm" autocomplete="off">

      <!-- Tag chips -->
      <div class="flex flex-wrap gap-2 mb-6">
        <?php
        $tags = [
          ['label'=>'Kecepatan Layanan',      'icon'=>'⚡'],
          ['label'=>'Parfume','icon'=>'🌸'],
          ['label'=>'Kualitas Cucian',         'icon'=>'✨'],
          ['label'=>'Harga / Tarif',           'icon'=>'💰'],
          ['label'=>'Keramahan Staff',         'icon'=>'😊'],
          ['label'=>'Kemudahan Pemesanan',     'icon'=>'📱'],
          ['label'=>'Kebersihan Tempat',       'icon'=>'🧹'],
          ['label'=>'Ketepatan Waktu',         'icon'=>'⏰'],
          ['label'=>'Packaging','icon'=>'📦'],
        ];
        foreach ($tags as $tag):
          $id = 'chip_' . preg_replace('/\W/', '_', $tag['label']);
        ?>
        <label class="tag-chip flex items-center gap-1.5 px-3.5 py-2 rounded-full border-2 border-gray-200 bg-white text-gray-600 text-xs font-semibold" id="<?= $id ?>">
          <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tag['label'], ENT_QUOTES) ?>" class="hidden" onchange="toggleChip(this)">
          <span class="text-base leading-none"><?= $tag['icon'] ?></span>
          <span><?= htmlspecialchars($tag['label'], ENT_QUOTES) ?></span>
        </label>
        <?php endforeach; ?>
      </div>

      <!-- Selected tags preview -->
      <div id="tagPreview" class="hidden mb-4 flex items-start gap-2 px-4 py-2.5 rounded-xl text-xs font-medium" style="background:#F3E6F5;border:1px solid #C7A1C9;color:#5B005F;">
        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
        </svg>
        <span>Dipilih: <strong id="tagPreviewText"></strong></span>
      </div>

      <!-- Textarea -->
      <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-600 mb-2" for="feedback_text">
          Ceritakan lebih lanjut <span class="text-gray-400 font-normal">(opsional)</span>
        </label>
        <textarea
          id="feedback_text"
          name="feedback_text"
          rows="4"
          maxlength="2000"
          placeholder="Tuliskan pengalaman atau saran Anda di sini..."
          class="w-full px-4 py-3.5 rounded-2xl border-2 border-gray-200 transition-all text-gray-700 text-sm bg-gray-50 focus:bg-white resize-none leading-relaxed"
          oninput="updateCharCount(this)"
        ></textarea>
        <div class="flex justify-end mt-1">
          <span id="charCount" class="char-counter text-xs text-gray-400">0 / 2000</span>
        </div>
      </div>

      <!-- Buttons -->
      <div class="flex gap-3">
        <button type="submit" name="skip" value="1"
          class="flex-shrink-0 px-5 py-3.5 rounded-2xl border-2 border-gray-200 text-gray-500 font-semibold text-sm hover:bg-gray-50 hover:border-gray-300 active:scale-[0.98] transition-all">
          Lewati
        </button>
        <button type="submit" id="submitBtn"
          class="btn-primary flex-1 py-3.5 rounded-2xl font-semibold text-base text-white shadow-md flex items-center justify-center gap-2">
          <span id="submitText">Kirim Survey</span>
          <svg id="submitIcon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
          </svg>
          <svg id="submitSpinner" class="w-5 h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </button>
      </div>
    </form>

    <!-- Step dots -->
    <div class="flex justify-center items-center gap-2 mt-6">
      <span class="w-2 h-2 rounded-full bg-gray-200"></span>
      <span class="w-2 h-2 rounded-full bg-gray-200"></span>
      <span class="w-6 h-2 rounded-full" style="background:#5B005F;"></span>
    </div>
  </div>

  <script>
    function toggleChip(checkbox) {
      const label = checkbox.closest('label');
      if (checkbox.checked) {
        label.classList.add('selected');
        label.style.borderColor = '#5B005F';
        label.style.background  = '#F3E6F5';
        label.style.color       = '#5B005F';
      } else {
        label.classList.remove('selected');
        label.style.borderColor = '';
        label.style.background  = '';
        label.style.color       = '';
      }
      updateTagPreview();
    }

    function updateTagPreview() {
      const checked = [...document.querySelectorAll('input[name="tags[]"]:checked')];
      const preview = document.getElementById('tagPreview');
      if (checked.length > 0) {
        document.getElementById('tagPreviewText').textContent = checked.map(c => c.value).join(', ');
        preview.classList.remove('hidden');
      } else {
        preview.classList.add('hidden');
      }
    }

    function updateCharCount(el) {
      const len     = el.value.length;
      const counter = document.getElementById('charCount');
      counter.textContent = len + ' / 2000';
      counter.style.color = len > 1800 ? '#EF4444' : '#94a3b8';
    }

    let submitted = false;
    document.getElementById('feedbackForm').addEventListener('submit', function() {
      if (submitted) return false;
      submitted = true;
      document.getElementById('submitText').textContent = 'Mengirim...';
      document.getElementById('submitIcon').classList.add('hidden');
      document.getElementById('submitSpinner').classList.remove('hidden');
      document.getElementById('submitBtn').disabled = true;
    });

    // Floating bubbles
    const container = document.getElementById('bubblesContainer');
    for (let i = 0; i < 10; i++) {
      const size  = Math.random() * 55 + 18 | 0;
      const left  = Math.random() * 100 | 0;
      const delay = (Math.random() * 9).toFixed(1);
      const dur   = (Math.random() * 10 + 9).toFixed(1);
      const el = document.createElement('div');
      el.className = 'bubble';
      el.style.cssText = `width:${size}px;height:${size}px;left:${left}%;bottom:-${size}px;animation-duration:${dur}s;animation-delay:${delay}s;`;
      container.appendChild(el);
    }
  </script>
</body>
</html>
