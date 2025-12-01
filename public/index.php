<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Solo Leveling — Habit Quests</title>

  <!-- UI libs (Daisy + Tailwind) -->
  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.1.7/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Favicon (NO leading slash) -->
  <link rel="icon" type="image/png" href="favicon.png" />

  <!-- Custom CSS (NO leading slash) -->
  <link rel="stylesheet" href="styles.css" />
</head>

<body class="page">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="grid-overlay"></div>

  <main class="shell">
    <header id="hero-section" class="hero">
      <div class="hero__badge">Solo Leveling • Habit Quests</div>
      <h1><span class="gradient-text">Level Up</span> Your Life</h1>
      <p class="hero__lede">Complete quests, build better habits, and become the strongest version of yourself.</p>
      <div class="hero__features">
        <div class="feature-card">
          <span class="icon">⚡</span>
          <div>
            <div class="feature-title">Daily XP</div>
            <div class="feature-sub">Earn rewards</div>
          </div>
        </div>
        <div class="feature-card">
          <span class="icon">📈</span>
          <div>
            <div class="feature-title">Level Up</div>
            <div class="feature-sub">Track progress</div>
          </div>
        </div>
        <div class="feature-card">
          <span class="icon">🎯</span>
          <div>
            <div class="feature-title">Quests</div>
            <div class="feature-sub">Complete goals</div>
          </div>
        </div>
      </div>
      <div class="hero__cta">
        <button id="hero-start" class="btn primary hero-btn">Start your journey ⚡</button>
        <button id="hero-login" class="btn ghost hero-btn">Already have an account? Sign in</button>
      </div>
    </header>

    <!-- ===== STATS (hidden until logged in) ===== -->
    <section id="stats-wrap" class="hidden stats-panel panel">
      <div class="stats-panel__header">
        <div>
          <p class="eyebrow">Current tier</p>
          <div id="player-stats" class="stat-value"></div>
        </div>
        <div class="stats-panel__chips">
          <span id="streak-pill" class="pill">Streak: 0 days</span>
        </div>
      </div>
      <div class="xp-track">
        <div id="xp-fill" class="xp-fill"></div>
      </div>
      <div id="xp-text" class="xp-caption">0 / 100 XP</div>
    </section>

    <!-- ===== AUTH ===== -->
    <section id="auth" class="auth-grid auth-stack">
      <div class="panel auth-card" id="login-card">
        <div class="auth-card__head">
          <p class="eyebrow">Sign in</p>
          <h2>Welcome back</h2>
          <p class="muted">Access your quests, XP, streaks, and groups with a secure sign in.</p>
        </div>
        <input id="login-username" class="field" placeholder="Username">
        <input id="login-password" class="field" type="password" placeholder="Password">
        <button id="btn-login" class="btn primary">Sign in</button>
      </div>

      <div class="panel auth-card accent hidden" id="signup-card">
        <div class="auth-card__head">
          <p class="eyebrow">Create account</p>
          <h2>Join Solo Leveling</h2>
          <p class="muted">Register once, sync quests everywhere.</p>
        </div>
        <input id="reg-username" class="field" placeholder="Username">
        <input id="reg-name" class="field" placeholder="Full name">
        <input id="reg-email" class="field" placeholder="Email">
        <input id="reg-password" class="field" type="password" placeholder="Password">
        <button id="btn-register" class="btn secondary">Sign up</button>
      </div>
    </section>

    <!-- ===== DASHBOARD (logged-in) ===== -->
    <section id="dashboard" class="hidden dashboard-grid">
      <div class="panel">
        <div class="panel__head">
          <div>
            <p class="eyebrow">Quest board</p>
            <h3>Your quests</h3>
          </div>
          <button id="open-add" class="btn primary">Add quest</button>
        </div>

        <div class="quest-list">
          <ul id="quest-list"></ul>
        </div>
      </div>

    </section>
    <section id="achievements-page" class="hidden dashboard-grid">
      <div class="panel">
        <div class="panel__head">
          <div>
            <p class="eyebrow">Progress</p>
            <h3>Your achievements</h3>
          </div>
          <span class="pill">Milestones</span>
        </div>
        <ul id="achievements-list" class="achievements achievements--grid"></ul>
      </div>
    </section>

    <!-- ===== PROFILE PAGE (logged-in) ===== -->
    <section id="profile-page" class="hidden dashboard-grid">
      <div class="panel profile-card">
        <div class="profile-top">
          <div class="avatar" id="profile-avatar"></div>
          <div>
            <h3 id="profile-name">Your name</h3>
            <p id="profile-username" class="muted"></p>
            <p id="profile-email" class="muted"></p>
          </div>
        </div>
        <div class="profile-stats">
          <div>
            <p class="eyebrow">Level</p>
            <div id="profile-level" class="stat-value"></div>
          </div>
          <div>
            <p class="eyebrow">Total XP</p>
            <div id="profile-xp" class="stat-value"></div>
          </div>
          <div>
            <p class="eyebrow">Streak</p>
            <div id="profile-streak" class="stat-value"></div>
          </div>
        </div>
        <div class="profile-achievements">
          <p class="eyebrow">Recent achievements</p>
          <ul id="profile-ach-list" class="achievements achievements--grid"></ul>
        </div>
      </div>
    </section>

   <!-- ===== GROUPS PAGE (logged-in) ===== -->
<section id="groups-page" class="hidden groups-grid">
  <div class="panel panel--stack">

    <!-- HEADER -->
    <div class="panel__head">
      <div>
        <p class="eyebrow">Party up</p>
        <h3>Groups</h3>
      </div>
      <button id="btn-back" class="btn ghost">← Back</button>
    </div>

    <!-- CREATE / JOIN -->
    <div class="panel panel--inline">
      <div class="panel__head">
        <h4>Create or join</h4>
        <p class="muted">Recruit allies or dive into existing leaderboards.</p>
      </div>

      <div class="group-forms">

        <!-- Create Group -->
        <input id="create-group-name" class="field" placeholder="New group name" />

        <!-- NEW: Join mode dropdown -->
        <select id="create-group-join-mode" class="field">
          <option value="open">Open (anyone can join)</option>
          <option value="request">Request Only (owner approves)</option>
        </select>

        <button id="btn-create-group" class="btn secondary wide">Create group</button>

        <!-- Join by name -->
        <div class="group-join">
          <input id="join-group-name" class="field" placeholder="Join by name..." />
          <button id="btn-join-by-name" class="btn primary wide">Join</button>
        </div>

      </div>

      <p id="groups-error" class="error-text"></p>
      <p class="muted text-sm">Groups are public; keep shared details non-sensitive.</p>
    </div>


    <!-- MY GROUPS / ALL GROUPS -->
    <div class="groups-columns">

      <!-- YOUR GROUPS -->
      <div class="panel panel--inline">
        <div class="panel__head">
          <h4>My groups</h4>
        </div>
        <ul id="my-groups" class="groups-list"></ul>
      </div>

      <!-- PUBLIC GROUPS -->
      <div class="panel panel--inline">
        <div class="panel__head">
          <h4>Browse public</h4>
        </div>
        <ul id="all-groups" class="groups-list"></ul>
      </div>

    </div>

    <!-- LEADERBOARD -->
    <div class="panel panel--inline">
      <div class="panel__head">
        <h4>Leaderboard</h4>
      </div>
      <div id="lb-wrap" class="leaderboard">
        Pick a group to view its leaderboard.
      </div>
    </div>

  </div>
</section>

<!-- Delete Group Modal -->
<dialog id="delete-modal" class="modal">
  <div class="modal-box bg-[#0f1824]">
    <h3 class="font-bold text-lg text-[#f55] mb-3">Delete Group?</h3>
    <p class="muted mb-4">This action cannot be undone.</p>

    <div class="modal-action">
      <button id="dm-cancel" class="btn ghost">Cancel</button>
      <button id="dm-ok" class="btn danger">Delete</button>
    </div>
  </div>
</dialog>

  <!-- ===== NAVBAR (hidden until logged in) ===== -->
  <nav id="navbar" class="navbar hidden">
    <div class="navbar__inner">
      <div class="navbar__brand">
        <div class="logo-dot"></div>
        <span class="logo-text">Solo Leveling</span>
      </div>
      <div class="navbar__actions">
        <button id="nav-dashboard" class="btn nav-btn requires-auth">🏠 Dashboard</button>
        <button id="nav-groups" class="btn nav-btn requires-auth">🏆 Groups</button>
        <button id="nav-achievements" class="btn nav-btn requires-auth">🎖 Achievements</button>
        <button id="nav-profile" class="btn nav-btn requires-auth">👤 Profile</button>
        <button id="btn-logout" class="btn danger nav-btn">🚪 Logout</button>
      </div>
    </div>
  </nav>

  <dialog id="delete-modal" class="modal">
    <div class="modal-box bg-[#0f1824]">
      <h3 class="font-bold text-lg text-[#4cc9ff]">Delete this group?</h3>
      <p class="py-2 opacity-80">This cannot be undone.</p>
      <div class="modal-action">
        <button id="dm-cancel" class="btn">Cancel</button>
        <button id="dm-ok" class="btn bg-red-600 border-0 hover:bg-red-700">Delete</button>
      </div>
    </div>
  </dialog>
  <dialog id="add-quest-modal" class="modal">
    <div class="modal-box bg-[rgba(24,34,56,0.96)] border border-[rgba(255,255,255,0.18)] shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
      <h3 class="font-bold text-lg text-[#4cc9ff]">Add quest</h3>
  
      <div class="space-y-2 mt-3">
  
        <!-- Title input + autocomplete wrapper -->
        <div class="relative">
          <input id="q-title" class="field" placeholder="Quest title" autocomplete="off">
          <ul id="template-suggestions"
              class="menu bg-base-200 rounded-box mt-1 hidden max-h-48 overflow-y-auto
                     shadow-lg absolute z-50 w-full"></ul>
        </div>
  
        <textarea id="q-desc" class="field field--area" placeholder="Description"></textarea>
  
        <label class="field__label">Due date</label>
        <input id="q-due" type="date" class="field">
  
        <label class="field__label">Difficulty</label>
        <select id="q-diff" class="field">
          <option value="easy">Easy</option>
          <option value="medium" selected>Medium</option>
          <option value="hard">Hard</option>
          <option value="epic">Epic</option>
        </select>
  
        <!-- ⭐ NEW: Repeat Mode -->
        <label class="field__label">Repeat</label>
        <select id="q-repeat" class="field">
          <option value="none" selected>One-time</option>
          <option value="daily">Daily (repeats every day)</option>
          <option value="weekly">Weekly (repeats every week)</option>
        </select>
  
      </div>
  
      <div class="modal-action">
        <button id="add-cancel" class="btn ghost">Cancel</button>
        <button id="btn-add" class="btn primary">Add quest</button>
      </div>
    </div>
  </dialog>
  <dialog id="member-modal" class="modal">
    <div class="modal-box bg-[rgba(24,34,56,0.96)] border border-[rgba(255,255,255,0.18)] shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
      <h3 class="font-bold text-lg text-[#4cc9ff]">Member</h3>
      <p class="py-2 opacity-80" id="member-username">Username</p>
      <div id="member-details" class="hidden text-sm space-y-1">
        <div>Level: <span id="member-level"></span></div>
        <div>XP: <span id="member-xp"></span></div>
        <div>Streak: <span id="member-streak"></span></div>
        <div>Email: <span id="member-email"></span></div>
      </div>
      <div class="modal-action">
        <button id="mm-view" class="btn">View profile</button>
        <button id="mm-kick" class="btn danger hidden">Remove from group</button>
        <button id="mm-close" class="btn ghost">Close</button>
      </div>
    </div>
  </dialog>
  <div id="member-pop" class="member-pop hidden">
    <div class="member-pop-inner">
      <div class="member-pop-name" id="mp-name">Member</div>
      <div class="member-pop-sub" id="mp-username">@user</div>
      <div class="member-pop-sub" id="mp-email"></div>
      <div class="member-pop-stats">
        <span id="mp-level">Lv 1</span>
        <span id="mp-xp">0 XP</span>
        <span id="mp-streak">0 day streak</span>
      </div>
      <div class="member-pop-actions">
        <button id="mp-close" class="chip-btn mini">Close</button>
        <button id="mp-kick" class="chip-btn mini danger hidden">Remove</button>
      </div>
    </div>
  </div>
  <dialog id="quest-detail-modal" class="modal">
    <div class="modal-box bg-[rgba(24,34,56,0.96)] border border-[rgba(255,255,255,0.18)] shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
      <h3 class="font-bold text-lg text-[#4cc9ff]" id="qd-title">Quest</h3>
      <p class="opacity-80" id="qd-desc"></p>
      <div class="mt-3 text-sm space-y-1">
        <div>Due: <span id="qd-due"></span></div>
        <div>Difficulty: <span id="qd-diff"></span></div>
        <div>Reward: <span id="qd-reward"></span></div>
        <div>Penalty: <span id="qd-penalty"></span></div>
        <div>Repeat: <span id="qd-repeat"></span></div>
      </div>
      <div class="modal-action">
        <button id="qd-close" class="btn ghost">Close</button>
      </div>
    </div>
  </dialog>
  <script src="app.js"></script>
</body>
</html>
