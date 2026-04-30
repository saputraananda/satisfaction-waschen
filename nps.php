<?php
session_start();

if (empty($_SESSION['no_nota'])) {
  header('Location: index.php');
  exit;
}
if (empty($_SESSION['csat_score'])) {
  header('Location: csat.php');
  exit;
}

// Allow going back from Feedback to edit NPS
if (isset($_GET['back'])) {
  unset($_SESSION['nps_score'], $_SESSION['nps_category']);
}

if (!empty($_SESSION['nps_score']) && !isset($_GET['back'])) {
  header('Location: feedback.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $score = (int) ($_POST['nps_score'] ?? -1);
  if ($score >= 0 && $score <= 10) {
    $category = $score <= 6 ? 'Detractor' : ($score <= 8 ? 'Passive' : 'Promoter');
    $_SESSION['nps_score'] = $score;
    $_SESSION['nps_category'] = $category;
    header('Location: feedback.php');
    exit;
  }
}

$no_nota = htmlspecialchars($_SESSION['no_nota'], ENT_QUOTES, 'UTF-8');
$csat_score = (int) $_SESSION['csat_score'];
$csat_label = htmlspecialchars($_SESSION['csat_label'], ENT_QUOTES, 'UTF-8');
$csat_emoji = ['', '😭', '😞', '😐', '😊', '🤩'][$csat_score] ?? '';
$preselected = isset($_SESSION['nps_score']) ? (int) $_SESSION['nps_score'] : -1;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rekomendasi — Waschen Laundry</title>
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
            'card-in': 'cardIn .6s ease-out both',
            'slide-in': 'slideIn .4s ease-out both',
          },
          keyframes: {
            fadeUp: { '0%': { opacity: '0', transform: 'translateY(18px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
            cardIn: { '0%': { opacity: '0', transform: 'translateY(28px) scale(.97)' }, '100%': { opacity: '1', transform: 'translateY(0) scale(1)' } },
            slideIn: { '0%': { opacity: '0', transform: 'translateX(-10px)' }, '100%': { opacity: '1', transform: 'translateX(0)' } },
          },
        },
      },
    }
  </script>
  <style>
    * {
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #5B005F 0%, #8A4A8D 100%);
    }

    .bubble {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.12);
      animation: floatUp linear infinite;
    }

    @keyframes floatUp {
      0% {
        transform: translateY(100vh);
        opacity: .12;
      }

      100% {
        transform: translateY(-20vh);
        opacity: 0;
      }
    }

    .nps-btn {
      transition: transform .15s, box-shadow .15s;
      user-select: none;
      cursor: pointer;
    }

    .nps-btn:hover {
      transform: translateY(-4px) scale(1.1);
    }

    .nps-btn.active {
      transform: translateY(-6px) scale(1.18);
      box-shadow: 0 8px 20px rgba(0, 0, 0, .2);
    }

    .progress-bar {
      transition: width .5s cubic-bezier(.4, 0, .2, 1);
    }

    .btn-primary {
      background: #5B005F;
      transition: background .2s, transform .15s;
    }

    .btn-primary:hover:not(:disabled) {
      background: #430046;
    }

    .btn-primary:active:not(:disabled) {
      transform: scale(0.98);
    }

    .category-badge {
      transition: all .3s ease;
    }
  </style>
</head>

<body class="min-h-screen flex items-start sm:items-center justify-center p-4 py-8 relative">

  <div id="bubblesContainer" class="fixed inset-0 pointer-events-none overflow-hidden"></div>

  <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg px-8 py-10 animate-card-in relative z-10">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-5">
      <a href="csat.php?back=1" class="text-gray-400 hover:text-gray-600 transition-colors p-1" title="Kembali ke CSAT">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </a>
      <div class="flex-1">
        <p class="text-xs font-semibold uppercase tracking-widest" style="color:#5B005F;">Waschen Laundry</p>
        <p class="text-xs text-gray-400">Nota: <strong class="text-gray-600"><?= $no_nota ?></strong></p>
      </div>
      <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-3 py-1 rounded-full">2 / 3</span>
    </div>

    <!-- Progress -->
    <div class="w-full bg-gray-100 rounded-full h-1.5 mb-7 overflow-hidden">
      <div class="progress-bar h-1.5 rounded-full" style="width:66%; background:#5B005F;"></div>
    </div>

    <!-- Title -->
    <div class="text-center mb-6 animate-fade-up" style="animation-delay:.08s">
      <div class="inline-flex items-center gap-2 text-xs font-semibold px-4 py-1.5 rounded-full mb-3"
        style="background:#F3E6F5;color:#5B005F;">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path
            d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" />
        </svg>
        Rekomendasi
      </div>
      <h2 class="text-xl font-bold text-gray-800 leading-snug">
        Apakah Anda Akan <span style="color:#5B005F;">Merekomendasikan Kami</span> ke
        Teman dan Keluarga?
      </h2>
      <p class="text-gray-400 text-xs mt-2">0 = Tidak Mungkin &nbsp;·&nbsp; 10 = Pasti Rekomendasikan</p>
    </div>

    <!-- Category badge (shown after selection) -->
    <div id="categoryBadge" class="<?= $preselected >= 0 ? '' : 'hidden' ?> category-badge text-center mb-5">
      <span id="categoryInner"
        class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-semibold shadow-sm">
        <span id="categoryIcon"></span>
        <span id="categoryText"></span>
      </span>
      <p id="categoryDesc" class="text-xs text-gray-400 mt-1.5"></p>
    </div>

    <form method="POST" id="npsForm">
      <input type="hidden" name="nps_score" id="nps_score_input" value="<?= $preselected >= 0 ? $preselected : '' ?>">

      <!-- NPS grid 0-10 -->
      <div class="grid grid-cols-11 gap-1.5 mb-5" id="npsGrid">
        <?php for ($n = 0; $n <= 10; $n++):
          if ($n <= 6) {
            $bg = '#FFF5F5';
            $tc = '#EF4444';
            $bc = '#FECACA';
            $abg = '#EF4444';
            $atc = '#fff';
            $abc = '#EF4444';
          } elseif ($n <= 8) {
            $bg = '#FFFBEB';
            $tc = '#D97706';
            $bc = '#FDE68A';
            $abg = '#F59E0B';
            $atc = '#fff';
            $abc = '#F59E0B';
          } else {
            $bg = '#F0FDF4';
            $tc = '#16A34A';
            $bc = '#BBF7D0';
            $abg = '#10B981';
            $atc = '#fff';
            $abc = '#10B981';
          }
          $isActive = ($preselected === $n);
          ?>
          <button type="button" onclick="selectNps(<?= $n ?>)" id="npsBtn<?= $n ?>" data-score="<?= $n ?>"
            data-bg="<?= $bg ?>" data-tc="<?= $tc ?>" data-bc="<?= $bc ?>" data-abg="<?= $abg ?>" data-atc="<?= $atc ?>"
            data-abc="<?= $abc ?>"
            class="nps-btn aspect-square flex items-center justify-center rounded-xl border-2 text-xs font-bold <?= $isActive ? 'active' : '' ?>"
            style="background:<?= $isActive ? $abg : $bg ?>;color:<?= $isActive ? $atc : $tc ?>;border-color:<?= $isActive ? $abc : $bc ?>;"><?= $n ?></button>
        <?php endfor; ?>
      </div>

      <button type="submit" id="npsSubmit" <?= $preselected >= 0 ? '' : 'disabled' ?>
        class="btn-primary w-full py-3.5 rounded-2xl font-semibold text-base text-white flex items-center justify-center gap-2 shadow-md <?= $preselected >= 0 ? '' : 'opacity-40 cursor-not-allowed' ?>">
        Lanjutkan
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
        </svg>
      </button>
    </form>

    <!-- Step dots -->
    <div class="flex justify-center items-center gap-2 mt-6">
      <span class="w-2 h-2 rounded-full bg-gray-200"></span>
      <span class="w-6 h-2 rounded-full" style="background:#5B005F;"></span>
      <span class="w-2 h-2 rounded-full bg-gray-200"></span>
    </div>
  </div>

  <script>
    let selectedNps = <?= $preselected >= 0 ? $preselected : 'null' ?>;

    const categoryData = {
      detractor: { text: 'Detractor', icon: '😔', desc: 'Kami akan berusaha lebih keras untuk Anda!', bg: '#FFF5F5', tc: '#EF4444' },
      passive: { text: 'Passive', icon: '😌', desc: 'Terima kasih! Kami akan terus meningkatkan.', bg: '#FFFBEB', tc: '#D97706' },
      promoter: { text: 'Promoter', icon: '🤩', desc: 'Wah, Anda luar biasa! Terima kasih banyak!', bg: '#F0FDF4', tc: '#16A34A' },
    };

    function renderCategory(score) {
      const catKey = score <= 6 ? 'detractor' : (score <= 8 ? 'passive' : 'promoter');
      const cat = categoryData[catKey];
      const inner = document.getElementById('categoryInner');
      inner.style.background = cat.bg;
      inner.style.color = cat.tc;
      document.getElementById('categoryIcon').textContent = cat.icon;
      document.getElementById('categoryText').textContent = cat.text + ' — Skor ' + score;
      document.getElementById('categoryDesc').textContent = cat.desc;
      document.getElementById('categoryBadge').classList.remove('hidden');
    }

    // Restore preselected state
    if (selectedNps !== null) renderCategory(selectedNps);

    function selectNps(score) {
      for (let i = 0; i <= 10; i++) {
        const btn = document.getElementById('npsBtn' + i);
        btn.classList.remove('active');
        btn.style.background = btn.dataset.bg;
        btn.style.color = btn.dataset.tc;
        btn.style.borderColor = btn.dataset.bc;
      }
      const active = document.getElementById('npsBtn' + score);
      active.classList.add('active');
      active.style.background = active.dataset.abg;
      active.style.color = active.dataset.atc;
      active.style.borderColor = active.dataset.abc;

      document.getElementById('nps_score_input').value = score;
      selectedNps = score;
      renderCategory(score);

      const btn = document.getElementById('npsSubmit');
      btn.disabled = false;
      btn.classList.remove('opacity-40', 'cursor-not-allowed');
    }

    // Floating bubbles
    const container = document.getElementById('bubblesContainer');
    for (let i = 0; i < 10; i++) {
      const size = Math.random() * 55 + 18 | 0;
      const left = Math.random() * 100 | 0;
      const delay = (Math.random() * 9).toFixed(1);
      const dur = (Math.random() * 10 + 9).toFixed(1);
      const el = document.createElement('div');
      el.className = 'bubble';
      el.style.cssText = `width:${size}px;height:${size}px;left:${left}%;bottom:-${size}px;animation-duration:${dur}s;animation-delay:${delay}s;`;
      container.appendChild(el);
    }
  </script>
</body>

</html>