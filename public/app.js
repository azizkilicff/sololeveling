// --- app.js ---

// Tiny helper
const $ = (id) => document.getElementById(id);

// ===== DOM refs (must match your HTML) =====
const auth        = $("auth");
const dash        = $("dashboard");
const navbar      = $("navbar");

const statsWrap   = $("stats-wrap");
const stats       = $("player-stats");
const xpFill      = $("xp-fill");
const xpText      = $("xp-text");
const streakPill  = $("streak-pill");
const achPill     = $("achievements-pill");
const achList     = $("achievement-list");

const questList   = $("quest-list");
const qTitle      = $("q-title");
const qDesc       = $("q-desc");
const qDue        = $("q-due");
const btnAdd      = $("btn-add");

const btnLogin    = $("btn-login");
const btnRegister = $("btn-register");
const btnLogout   = $("btn-logout");
const loginUser   = $("login-username");
const loginPass   = $("login-password");
const regUser     = $("reg-username");
const regEmail    = $("reg-email");
const regPass     = $("reg-password");

// Groups page
const groupsPage      = $("groups-page");
const btnBack         = $("btn-back");
const navDashBtn      = $("nav-dashboard");
const navGroupsBtn    = $("nav-groups");
const myGroups        = $("my-groups");
const allGroups       = $("all-groups");
const lbWrap          = $("lb-wrap");
const createInput     = $("create-group-name");
const joinInput       = $("join-group-name");
const btnCreateGroup  = $("btn-create-group");
const btnJoinByName   = $("btn-join-by-name");
const groupsErrorEl   = $("groups-error");

// Elements that require auth (Dashboard/Groups buttons)
const authOnlyEls = document.querySelectorAll(".requires-auth");

// ===== API helper =====
async function api(path, method = "GET", data) {
  const opts = { method, headers: { "Content-Type": "application/json" } };
  if (data) opts.body = JSON.stringify(data);

  const res = await fetch("/" + path, opts);
  let text = "", payload = null;
  try { text = await res.text(); payload = text ? JSON.parse(text) : null; } catch {}
  if (!res.ok) throw new Error((payload && payload.error) || text || `HTTP ${res.status}`);
  return payload ?? {};
}

// ===== UI helpers =====
function computeLevel(xp) {
  return Math.max(1, Math.floor(Math.sqrt(Math.max(0, xp) / 50)) + 1);
}

function setAuthUI(isAuthed) {
  // hide/show the Dashboard + Groups buttons only
  document.querySelectorAll(".requires-auth").forEach(el => {
    el.classList.toggle("hidden", !isAuthed);
  });
  // keep navbar visible so Logout shows; if you want Logout hidden too, add: navbar.classList.toggle("hidden", !isAuthed);
  navbar?.classList.remove("hidden");
}

function showAuth() {
  auth.classList.remove("hidden");
  statsWrap.classList.add("hidden");
  dash.classList.add("hidden");
  groupsPage.classList.add("hidden");
  setAuthUI(false); // hides Dashboard/Groups buttons
}

function showDashboard() {
  auth.classList.add("hidden");
  statsWrap.classList.remove("hidden");
  dash.classList.remove("hidden");
  groupsPage.classList.add("hidden");
  setAuthUI(true);  // show Dashboard/Groups buttons
}

function showGroups() {
  auth.classList.add("hidden");
  statsWrap.classList.remove("hidden");
  dash.classList.add("hidden");
  groupsPage.classList.remove("hidden");
  setAuthUI(true);  // show Dashboard/Groups buttons
}

// ===== Me + XP bar =====
let CURRENT_USER_ID = null;

async function refreshMe() {
  try {
    const { user } = await api("api/me.php");
    CURRENT_USER_ID = user.id;

    const xp = user.xp || 0;
    const level = user.level ?? computeLevel(xp);
    const xpInLevel = xp % 100;
    const streak = user.streak || 0;
    const achievements = user.achievements || [];

    stats.textContent = `Level ${level} • Total XP ${xp}`;
    if (xpFill) xpFill.style.width = `${xpInLevel}%`;
    if (xpText) xpText.textContent = `${xpInLevel} / 100 XP for this level`;
    if (streakPill) streakPill.textContent = `Streak: ${streak} day${streak === 1 ? "" : "s"}`;
    if (achPill) achPill.textContent = `Achievements: ${achievements.length}`;
    if (achList) {
      achList.innerHTML = achievements.length
        ? achievements.map(a => `<li class="pill pill--mini">${prettyAchievement(a.code)} <span class="ach-date">${(a.earned_at || "").slice(0,10)}</span></li>`).join("")
        : '<li class="muted">No achievements yet. Finish quests to start earning.</li>';
    }

    showDashboard();
    await loadQuests();
  } catch {
    showAuth();
  }
}

// ===== Quests =====
async function loadQuests() {
  questList.innerHTML = "";
  const { quests } = await api("api/quests.php");
  if (!quests || !quests.length) {
    questList.innerHTML = '<li class="card">No quests yet. Add one above.</li>';
    return;
  }

  quests.forEach(q => {
    const li = document.createElement("li");
    const diffClass = q.difficulty ? `diff-${q.difficulty}` : "diff-medium";
    const dueDate = new Date(`${q.due_date}T23:59:59`);
    const isOverdue = q.status === "pending" && (dueDate < new Date());

    li.className = `quest quest-item ${diffClass} ${isOverdue ? "overdue" : ""}`;

    const left = document.createElement("div");
    left.innerHTML = `
      <div class="quest-title">${q.title}</div>
      <div class="quest-meta">
        <span class="pill pill--mini ${isOverdue ? "pill--danger" : "pill--subtle"}">
          Due ${q.due_date}${isOverdue ? " • Overdue" : ""}
        </span>
        <span class="pill pill--mini pill--reward">+${q.reward_xp} XP</span>
        <span class="pill pill--mini pill--penalty">-${q.penalty_xp} XP</span>
        <span class="pill pill--mini pill--diff">${q.difficulty || "medium"}</span>
      </div>`;

    const right = document.createElement("div");
    right.className = "right";

    const chip = document.createElement("span");
    chip.className =
      (q.status === "completed")
        ? "badge badge-completed"
        : (q.status === "failed")
          ? "badge badge-failed"
          : "badge badge-pending";
    chip.textContent = q.status;
    right.appendChild(chip);

    if (q.status === "pending") {
      const b1 = document.createElement("button");
      b1.className = "btn";
      b1.textContent = "Complete";
      b1.onclick = () => finish(q, "complete");

      const b2 = document.createElement("button");
      b2.className = "btn btn-ghost";
      b2.textContent = "Fail";
      b2.style.marginLeft = ".5rem";
      b2.onclick = () => finish(q, "fail");

      right.appendChild(b1);
      right.appendChild(b2);
    }

    li.appendChild(left);
    li.appendChild(right);
    questList.appendChild(li);
  });
}

async function finish(q, action) {
  try {
    const dueDate = new Date(`${q.due_date}T23:59:59`);
    const isOverdue = q.status === "pending" && (dueDate < new Date());
    const prompt =
      (action === "complete")
        ? `Mark "${q.title}" complete? Reward: +${q.reward_xp} XP${isOverdue ? " (overdue)" : ""}`
        : `Fail "${q.title}"? Penalty: -${q.penalty_xp} XP${isOverdue ? " (overdue)" : ""}`;
    if (!confirm(prompt)) return;

    await api("api/complete.php", "POST", { id: q.id, action });
    await refreshMe();
  } catch (e) {
    alert(e.message);
  }
}

function prettyAchievement(code) {
  const map = {
    first_quest: "First Quest",
    streak_3: "3-Day Streak",
    streak_7: "7-Day Streak",
    level_5: "Level 5 Reached",
    level_10: "Level 10 Reached"
  };
  return map[code] || code;
}

// ===== Groups =====
async function loadGroups() {
  const data = await api("api/groups.php?action=list");
  const mine = data.mine || [];
  const mineIds = new Set(mine.map(g => g.id));
  const all  = (data.all || []).filter(g => !mineIds.has(g.id));

  // My Groups
  myGroups.innerHTML = (!mine.length)
    ? '<li class="opacity-70">No groups yet.</li>'
    : mine.map(g => `
      <li class="flex justify-between items-center p-3 rounded bg-[#0b131a] border border-[#1d2a38]">
        <span class="flex items-center gap-2">
          <button class="pill pill--mini pill--link" data-lb="${g.id}">${g.name}</button>
          <span class="opacity-60">(members: ${g.members})</span>
        </span>
        <span class="flex gap-2">
          ${
            (g.owner_user_id === CURRENT_USER_ID)
              ? `<button class="btn btn-xs bg-red-600 border-0 hover:bg-red-700" data-delete="${g.id}">Delete</button>`
              : `<button class="btn btn-xs bg-red-600 border-0 hover:bg-red-700" data-leave="${g.id}">Leave</button>`
          }
        </span>
      </li>`).join("");

  // All Groups (browse/join)
  allGroups.innerHTML = (!all.length)
    ? '<li class="opacity-70">No public groups found.</li>'
    : all.map(g => `
      <li class="flex justify-between items-center p-3 rounded bg-[#0b131a] border border-[#1d2a38]">
        <span class="flex items-center gap-2">
          <button class="pill pill--mini pill--link" data-lb="${g.id}">${g.name}</button>
          <span class="opacity-60">(members: ${g.members})</span>
        </span>
        <span class="flex gap-2">
          <button class="btn btn-xs aura-btn" data-join="${g.name}">Join</button>
        </span>
      </li>`).join("");

  // Attach handlers
  myGroups.querySelectorAll("[data-lb]").forEach(b =>
    b.addEventListener("click", () => loadLeaderboard(parseInt(b.dataset.lb,10))));
  myGroups.querySelectorAll("[data-leave]").forEach(b =>
    b.addEventListener("click", () => leaveGroup(parseInt(b.dataset.leave,10))));
  myGroups.querySelectorAll("[data-delete]").forEach(b =>
    b.addEventListener("click", () => openDeleteModal(parseInt(b.dataset.delete,10))));

  allGroups.querySelectorAll("[data-lb]").forEach(b =>
    b.addEventListener("click", () => loadLeaderboard(parseInt(b.dataset.lb,10))));
  allGroups.querySelectorAll("[data-join]").forEach(b =>
    b.addEventListener("click", () => joinGroupByName(b.dataset.join)));
}

async function loadLeaderboard(groupId) {
  lbWrap.innerHTML = "Loading…";
  const { leaders } = await api(`api/groups.php?action=leaderboard&group_id=${groupId}`);
  if (!leaders || !leaders.length) {
    lbWrap.innerHTML = "No members yet.";
    return;
  }
  lbWrap.innerHTML = leaders.map((u, i) =>
    `<div class="flex justify-between p-2">
       <span>#${i+1} ${u.username}</span>
       <span>${u.xp} XP</span>
     </div>`).join("");
}

async function createGroup() {
  try {
    groupsErrorEl.textContent = "";
    const name = (createInput?.value || "").trim();
    if (!name) throw new Error("Enter a group name");
    await api("api/groups.php", "POST", { action: "create", name });
    if (createInput) createInput.value = "";
    await loadGroups();
  } catch (e) {
    groupsErrorEl.textContent = e.message;
  }
}

async function joinGroupByName(nameFromBtn) {
  try {
    groupsErrorEl.textContent = "";
    const name = (nameFromBtn || joinInput?.value || "").trim();
    if (!name) throw new Error("Enter a group name to join");
    await api("api/groups.php", "POST", { action: "join", name });
    if (joinInput) joinInput.value = "";
    await loadGroups();
  } catch (e) {
    groupsErrorEl.textContent = e.message;
  }
}

async function leaveGroup(groupId) {
  try {
    groupsErrorEl.textContent = "";
    await api("api/groups.php", "POST", { action: "leave", group_id: groupId });
    await loadGroups();
    lbWrap.innerHTML = "Pick a group to view its leaderboard.";
  } catch (e) {
    groupsErrorEl.textContent = e.message;
  }
}

let pendingDeleteGroupId = null;
function openDeleteModal(groupId) {
  pendingDeleteGroupId = groupId;
  $("delete-modal")?.showModal();
}
$("dm-cancel")?.addEventListener("click", () => {
  $("delete-modal")?.close();
  pendingDeleteGroupId = null;
});
$("dm-ok")?.addEventListener("click", async () => {
  const gid = pendingDeleteGroupId;
  $("delete-modal")?.close();
  pendingDeleteGroupId = null;
  await api("api/groups.php", "POST", { action: "delete", group_id: gid });
  await loadGroups();
  lbWrap.innerHTML = "Pick a group to view its leaderboard.";
});

// ===== Navbar show/hide on scroll (keeps nav, hides buttons on auth) =====
let lastY = window.scrollY;
let navForceUntil = 0;
function showNav() { navbar?.classList.remove("nav-hidden"); }
function hideNav() { navbar?.classList.add("nav-hidden"); }
function nudgeNavVisible(ms = 800) { navForceUntil = performance.now() + ms; showNav(); }

function onScroll() {
  const y = window.scrollY;
  const onAuth = !auth.classList.contains("hidden");
  if (onAuth) { hideNav(); lastY = y; return; }

  const nearTop = y < 16;
  if (nearTop || performance.now() < navForceUntil) {
    showNav();
    lastY = y;
    return;
  }

  if (y > lastY + 4) {
    hideNav(); // scrolling down
  } else if (y < lastY - 4) {
    showNav(); // scrolling up
  }
  lastY = y;
}
window.addEventListener("scroll", onScroll, { passive: true });

// ===== Events =====
// Auth
btnLogin?.addEventListener("click", async () => {
  try {
    await api("api/login.php", "POST", {
      username: (loginUser.value || "").trim(),
      password: loginPass.value
    });
    await refreshMe();
  } catch (e) {
    alert(e.message);
  }
});
btnRegister?.addEventListener("click", async () => {
  try {
    await api("api/register.php", "POST", {
      username: (regUser.value || "").trim(),
      email:    (regEmail.value || "").trim(),
      password: regPass.value
    });
    await refreshMe();
  } catch (e) {
    alert(e.message);
  }
});
btnLogout?.addEventListener("click", async () => {
  await api("api/logout.php");
  location.reload();
});

// Quests (difficulty only; no custom XP)
const qDiff = document.getElementById('q-diff');
btnAdd?.addEventListener("click", async () => {
  try {
    await api("api/quests.php", "POST", {
      title:       (qTitle.value || "").trim(),
      description: (qDesc.value || "").trim(),
      due_date:    qDue.value || new Date().toISOString().slice(0,10),
      difficulty:  (qDiff?.value || "medium")
    });
    qTitle.value = ""; qDesc.value = "";
    if (qDiff) qDiff.value = "medium";
    await loadQuests();
  } catch (e) {
    alert(e.message);
  }
});

// Navbar + groups
navDashBtn?.addEventListener("click", showDashboard);
btnBack?.addEventListener("click", showDashboard);
navGroupsBtn?.addEventListener("click", async () => { showGroups(); await loadGroups(); });

// Groups actions
btnCreateGroup?.addEventListener("click", createGroup);
btnJoinByName?.addEventListener("click", () => joinGroupByName());

// ===== Boot: start on Auth; refresh session if exists =====
document.addEventListener("DOMContentLoaded", () => {
  showAuth();   // Hide dashboard/groups on first paint
  refreshMe();  // If session is valid, this will switch to dashboard and reveal buttons
});
