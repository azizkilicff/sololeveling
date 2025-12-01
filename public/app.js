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
const addModal    = $("add-quest-modal");
const addCancel   = $("add-cancel");
const openAddBtn  = $("open-add");
const addModalTitle = document.querySelector("#add-quest-modal h3");

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
const regName     = $("reg-name");
const regEmail    = $("reg-email");
const regPass     = $("reg-password");
const loginCard   = $("login-card");
const signupCard  = $("signup-card");
const showSignup  = $("show-signup");
const showLogin   = $("show-login");
const heroStart   = $("hero-start");
const heroLogin   = $("hero-login");
const heroSection = $("hero-section");
const achievementsPage = $("achievements-page");
const achievementsList = $("achievements-list");
const navAchievementsBtn = $("nav-achievements");
const profilePage = $("profile-page");
const profileName = $("profile-name");
const profileUsername = $("profile-username");
const profileEmail = $("profile-email");
const profileLevel = $("profile-level");
const profileXp = $("profile-xp");
const profileStreak = $("profile-streak");
const profileAchList = $("profile-ach-list");
const profileAvatar = $("profile-avatar");
const navProfileBtn = $("nav-profile");
const memberModal = $("member-modal");
const memberUsername = $("member-username");
const memberKickBtn = $("mm-kick");
const memberViewBtn = $("mm-view");
const memberCloseBtn = $("mm-close");
const memberDetails = $("member-details");
const memberLevel = $("member-level");
const memberXp = $("member-xp");
const memberStreak = $("member-streak");
const memberEmail = $("member-email");
const questDetailModal = $("quest-detail-modal");
const qdTitle = $("qd-title");
const qdDesc = $("qd-desc");
const qdDue = $("qd-due");
const qdDiff = $("qd-diff");
const qdReward = $("qd-reward");
const qdPenalty = $("qd-penalty");
const qdRepeat = $("qd-repeat");
const qdTags = $("qd-tags");
const qdClose = $("qd-close");

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

  const res = await fetch(path, opts);
  let text = "", payload = null;
  try { text = await res.text(); payload = text ? JSON.parse(text) : null; } catch {}
  if (!res.ok) throw new Error((payload && payload.error) || text || `HTTP ${res.status}`);
  return payload ?? {};
}

// ===== UI helpers =====
function computeLevelDetails(totalXp) {
  const base = 30;
  const growth = 1.18;
  let level = 1;
  let cap = base;
  let xpRemaining = Math.max(0, totalXp);
  while (xpRemaining >= cap) {
    xpRemaining -= cap;
    level += 1;
    cap = Math.round(cap * growth);
  }
  return { level, xpInLevel: xpRemaining, cap };
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
  heroSection?.classList.remove("hidden");
  statsWrap.classList.add("hidden");
  dash.classList.add("hidden");
  groupsPage.classList.add("hidden");
  document.body.classList.add("auth-mode");
  navbar?.classList.add("hidden");
  setAuthUI(false); // hides Dashboard/Groups buttons
}

function showDashboard() {
  auth.classList.add("hidden");
  heroSection?.classList.add("hidden");
  statsWrap.classList.remove("hidden");
  dash.classList.remove("hidden");
  achievementsPage?.classList.add("hidden");
  groupsPage.classList.add("hidden");
  document.body.classList.remove("auth-mode");
  navbar?.classList.remove("hidden");
  setAuthUI(true);  // show Dashboard/Groups buttons
}

function showGroups() {
  auth.classList.add("hidden");
  heroSection?.classList.add("hidden");
  statsWrap.classList.remove("hidden");
  dash.classList.add("hidden");
  achievementsPage?.classList.add("hidden");
  profilePage?.classList.add("hidden");
  groupsPage.classList.remove("hidden");
  document.body.classList.remove("auth-mode");
  navbar?.classList.remove("hidden");
  setAuthUI(true);  // show Dashboard/Groups buttons
}

function showAchievements() {
  auth.classList.add("hidden");
  heroSection?.classList.add("hidden");
  statsWrap.classList.remove("hidden");
  dash.classList.add("hidden");
  groupsPage.classList.add("hidden");
  achievementsPage?.classList.remove("hidden");
  profilePage?.classList.add("hidden");
  document.body.classList.remove("auth-mode");
  navbar?.classList.remove("hidden");
  setAuthUI(true);
  renderAchievements();
}

function showProfile() {
  auth.classList.add("hidden");
  heroSection?.classList.add("hidden");
  statsWrap.classList.remove("hidden");
  dash.classList.add("hidden");
  groupsPage.classList.add("hidden");
  achievementsPage?.classList.add("hidden");
  profilePage?.classList.remove("hidden");
  document.body.classList.remove("auth-mode");
  navbar?.classList.remove("hidden");
  setAuthUI(true);
}

// ===== Me + XP bar =====
let CURRENT_USER_ID = null;
let LAST_ACHIEVEMENTS = [];
let LAST_USER = null;
let ACHIEVEMENT_CATALOG = {};
let LAST_ACHIEVEMENTS_UNLOCKED = [];
let editingQuestId = null;

async function refreshMe() {
  try {
    const { user } = await api("api/me.php");
    CURRENT_USER_ID = user.id;

    const xp = user.xp || 0;
    const details = (user.xp_cap !== undefined)
      ? { level: user.level, xpInLevel: user.xp_in_level, cap: user.xp_cap }
      : computeLevelDetails(xp);
    const level = details.level;
    const xpInLevel = details.xpInLevel;
    const cap = details.cap;
    const streak = user.streak || 0;
    const achievements = user.achievements || [];
    ACHIEVEMENT_CATALOG = user.achievement_catalog || ACHIEVEMENT_CATALOG;
    LAST_ACHIEVEMENTS_UNLOCKED = achievements;
    LAST_ACHIEVEMENTS = achievements;
    LAST_USER = user;

    stats.textContent = `Level ${level} • Total XP ${xp}`;
    const pct = cap ? Math.min(100, Math.round((xpInLevel / cap) * 100)) : 0;
    if (xpFill) xpFill.style.width = `${pct}%`;
    if (xpText) xpText.textContent = `${xpInLevel} / ${cap} XP for this level`;
    if (streakPill) streakPill.textContent = `Streak: ${streak} day${streak === 1 ? "" : "s"}`;
    renderAchievements();

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
    const diff = resolveDifficulty(q);
    const diffClass = `diff-${diff}`;
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
        <span class="pill pill--mini pill--diff diff-${diff}">${diff.charAt(0).toUpperCase() + diff.slice(1)}</span>
        ${
          q.tags
            ? q.tags.split(",").map(t => t.trim()).filter(Boolean).map(tag =>
                `<span class="pill pill--mini pill--ghost">${tag}</span>`).join("")
            : ""
        }
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

    li.addEventListener("click", () => openQuestDetail(q));

    if (q.status === "pending") {
      const b1 = document.createElement("button");
      b1.className = "btn";
      b1.textContent = "Complete";
      b1.onclick = (e) => { e.stopPropagation(); finish(q, "complete"); };

      const b2 = document.createElement("button");
      b2.className = "btn btn-ghost";
      b2.textContent = "Fail";
      b2.style.marginLeft = ".5rem";
      b2.onclick = (e) => { e.stopPropagation(); finish(q, "fail"); };

      const b3 = document.createElement("button");
      b3.className = "btn btn-ghost";
      b3.textContent = "Edit";
      b3.style.marginLeft = ".5rem";
      b3.onclick = (e) => { e.stopPropagation(); openEditQuest(q); };

      right.appendChild(b1);
      right.appendChild(b2);
      right.appendChild(b3);
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

function openEditQuest(q) {
  editingQuestId = q.id;
  if (addModalTitle) addModalTitle.textContent = "Edit quest";
  if (btnAdd) btnAdd.textContent = "Save changes";
  if (qTitle) qTitle.value = q.title || "";
  if (qDesc) qDesc.value = q.description || "";
  if (qDue) qDue.value = q.due_date || "";
  if (qDiff) qDiff.value = resolveDifficulty(q);
  addModal?.showModal();
}

function renderAchievementsList(achievements) {
  if (!ACHIEVEMENT_CATALOG || !Object.keys(ACHIEVEMENT_CATALOG).length) {
    return '<li class="muted">No achievements yet. Finish quests to start earning.</li>';
  }
  const earnedCodes = new Set((achievements || []).map(a => a.code));
  const catalogEntries = Object.entries(ACHIEVEMENT_CATALOG);
  return catalogEntries.map(([code, meta]) => {
    const isEarned = earnedCodes.has(code);
    const earnedAt = (achievements || []).find(a => a.code === code)?.earned_at;
    return `<li class="achievement-card ${isEarned ? "" : "locked"}">
      <div class="ach-title">${meta.title}</div>
      <div class="ach-desc">${meta.desc}</div>
      <div class="ach-status">${isEarned ? `Unlocked ${earnedAt ? earnedAt.slice(0,10) : ""}` : "Locked"}</div>
    </li>`;
  }).join("");
}

function renderAchievements() {
  if (achievementsList) {
    achievementsList.innerHTML = renderAchievementsList(LAST_ACHIEVEMENTS);
  }
}

function resolveDifficulty(q) {
  const raw = (q.difficulty || "").toLowerCase();
  if (["easy", "medium", "hard", "epic"].includes(raw)) return raw;
  const rxp = q.reward_xp || 0;
  if (rxp <= 15) return "easy";
  if (rxp <= 25) return "medium";
  if (rxp <= 45) return "hard";
  return "epic";
}

function openQuestDetail(q) {
  if (!questDetailModal) return;
  const diff = resolveDifficulty(q);
  if (qdTitle) qdTitle.textContent = q.title || "Quest";
  if (qdDesc) qdDesc.textContent = q.description || "No description provided.";
  if (qdDue) qdDue.textContent = q.due_date || "N/A";
  if (qdDiff) qdDiff.textContent = diff.charAt(0).toUpperCase() + diff.slice(1);
  if (qdReward) qdReward.textContent = `${q.reward_xp || 0} XP`;
  if (qdPenalty) qdPenalty.textContent = `${q.penalty_xp || 0} XP`;
  if (qdRepeat) qdRepeat.textContent = q.repeat_mode || "one-time";
  questDetailModal.showModal();
}

function renderShortAchievements(achievements) {
  if (!achievements || !achievements.length) return '<li class="muted">No achievements yet.</li>';
  const unlocked = achievements.slice(0, 3);
  return unlocked.map(a => `<li class="pill pill--mini">${prettyAchievement(a.code)}</li>`).join("");
}

function renderProfile() {
  if (!LAST_USER || !profilePage) return;
  profileName.textContent = LAST_USER.name || LAST_USER.username;
  profileUsername.textContent = `@${LAST_USER.username}`;
  profileEmail.textContent = LAST_USER.email || "";
  const d = (LAST_USER.xp_cap !== undefined)
    ? { level: LAST_USER.level, xpInLevel: LAST_USER.xp_in_level, cap: LAST_USER.xp_cap }
    : computeLevelDetails(LAST_USER.xp || 0);
  profileLevel.textContent = d.level;
  profileXp.textContent = LAST_USER.xp || 0;
  profileStreak.textContent = `${LAST_USER.streak || 0} day${(LAST_USER.streak||0) === 1 ? "" : "s"}`;
  if (profileAchList) profileAchList.innerHTML = renderShortAchievements(LAST_ACHIEVEMENTS_UNLOCKED);

  if (profileAvatar && LAST_USER.username) {
    const initials = (LAST_USER.name || LAST_USER.username).split(" ").map(s => s[0] || "").join("").slice(0,2).toUpperCase();
    profileAvatar.textContent = initials;
  }
}

async function openMemberModal(groupId, userId, ownerId, username) {
  const isOwner = ownerId === CURRENT_USER_ID;
  if (!memberModal) return;
  try {
    const { user } = await api(`api/user.php?id=${userId}`);
    memberUsername.textContent = user.name || user.username;
    memberUsername.dataset.profile = user.username;
    const d = (user.xp_cap !== undefined)
      ? { level: user.level, xpInLevel: user.xp_in_level, cap: user.xp_cap }
      : computeLevelDetails(user.xp || 0);
    if (memberLevel) memberLevel.textContent = d.level;
    if (memberXp) memberXp.textContent = user.xp || 0;
    if (memberStreak) memberStreak.textContent = `${user.streak || 0} day streak`;
    if (memberEmail) memberEmail.textContent = user.email || "";
  } catch {
    memberUsername.textContent = username || "Member";
    memberUsername.dataset.profile = "";
    if (memberLevel) memberLevel.textContent = "";
    if (memberXp) memberXp.textContent = "";
    if (memberStreak) memberStreak.textContent = "";
    if (memberEmail) memberEmail.textContent = "";
  }
  if (memberKickBtn) {
    memberKickBtn.dataset.gid = groupId;
    memberKickBtn.dataset.uid = userId;
    memberKickBtn.classList.toggle("hidden", !isOwner || userId === CURRENT_USER_ID);
  }
  if (memberDetails) memberDetails.classList.add("hidden");
  memberModal.showModal();
}

// ===== Groups =====
async function loadGroups() {

  const data = await api("api/groups.php?action=list");
  const mine = data.mine || [];
  const mineIds = new Set(mine.map(g => g.id));
  const all  = (data.all || []).filter(g => !mineIds.has(g.id));

  // -----------------------------
  // RENDER — MY GROUPS
  // -----------------------------
  myGroups.innerHTML = (!mine.length)
    ? '<li class="opacity-70">No groups yet.</li>'
    : mine.map(g => `
      <li class="flex justify-between items-center p-3 rounded bg-[#0b131a] border border-[#1d2a38]">

        <span class="flex items-center gap-2">
          <span class="pill pill--mini pill--ghost">${g.name}</span>
          <span class="opacity-60">(members: ${g.members})</span>
        </span>

        <span class="flex flex-col gap-2 action-chip-col">

          <span class="flex gap-2 action-chip-row">
            <button class="chip-btn" data-lb="${g.id}">List</button>
            ${
              (g.owner_user_id === CURRENT_USER_ID)
                ? `<button class="chip-btn danger" data-delete="${g.id}">Delete</button>`
                : `<button class="chip-btn danger" data-leave="${g.id}">Leave</button>`
            }
          </span>

          ${
            (g.owner_user_id === CURRENT_USER_ID && g.join_mode === "request" && g.requests?.length)
              ? `
                <div class="request-box">
                  <div class="request-title">Join Requests</div>
                  ${g.requests.map(r => `
                    <div class="request-row">
                      <span class="request-user">${r.username}</span>
                      <span class="request-actions">
                        <button class="chip-btn mini" data-approve="${g.id}:${r.user_id}">Approve</button>
                        <button class="chip-btn mini danger" data-reject="${g.id}:${r.user_id}">Reject</button>
                      </span>
                    </div>
                  `).join("")}
                </div>
              `
              : ""
          }

        </span>
      </li>`).join("");


// -----------------------------
// RENDER — ALL GROUPS
// -----------------------------
allGroups.innerHTML = (!all.length)
  ? '<li class="opacity-70">No public groups found.</li>'
  : all.map(g => `
    <li class="flex justify-between items-center p-3 rounded bg-[#0b131a] border border-[#1d2a38]">

      <span class="flex items-center gap-2">
        <span class="pill pill--mini pill--ghost">${g.name}</span>
        <span class="opacity-60">(members: ${g.members})</span>
      </span>

      <span class="flex gap-2 action-chip-row">

        <button class="chip-btn" data-lb="${g.id}">List</button>

        ${
          // --- OPEN GROUP ---
          g.join_mode === "open"
            ? `<button class="chip-btn" data-join="${g.name}">Join</button>`

          // --- REQUEST GROUP ---
          : g.requested_by_me
              ? `<button class="chip-btn mini opacity-50" disabled>Requested ✓</button>`
              : `<button class="chip-btn" data-request="${g.id}">Request to Join</button>`
        }

      </span>

    </li>`).join("");


  // ==========================================================
  // ===============   EVENT HANDLERS (INSIDE)   ===============
  // ==========================================================

  // My Groups — Leaderboard
  myGroups.querySelectorAll("[data-lb]").forEach(b =>
    b.addEventListener("click", () =>
      loadLeaderboard(parseInt(b.dataset.lb, 10)))
  );

  // My Groups — Leave
  myGroups.querySelectorAll("[data-leave]").forEach(b =>
    b.addEventListener("click", () =>
      leaveGroup(parseInt(b.dataset.leave, 10)))
  );

  // My Groups — Delete (owner)
  myGroups.querySelectorAll("[data-delete]").forEach(b =>
    b.addEventListener("click", () =>
      openDeleteModal(parseInt(b.dataset.delete, 10)))
  );

  // ALL Groups — Leaderboard
  allGroups.querySelectorAll("[data-lb]").forEach(b =>
    b.addEventListener("click", () =>
      alert("This leaderboard is restricted. Join the group to view it.")))

  // ALL Groups — Join (open mode)
  allGroups.querySelectorAll("[data-join]").forEach(b =>
    b.addEventListener("click", () =>
      joinGroupByName(b.dataset.join))
  );

  // ALL Groups — Request to Join
  allGroups.querySelectorAll("[data-request]").forEach(b =>
    b.addEventListener("click", () =>
      requestJoin(parseInt(b.dataset.request, 10)))
  );

// OWNER — Approve request
myGroups.querySelectorAll("[data-approve]").forEach(b => {
  const [gid, uid] = b.dataset.approve.split(":").map(Number);
  b.addEventListener("click", () =>
    approveJoinRequest(gid, uid));
});

  // OWNER — Reject request
  myGroups.querySelectorAll("[data-reject]").forEach(b => {
    const [gid, uid] = b.dataset.reject.split(":").map(Number);
    b.addEventListener("click", () =>
      rejectJoinRequest(gid, uid));
  });

} // <===== CLOSE loadGroups() PROPERLY ⬆⬆⬆


async function loadLeaderboard(groupId) {
  lbWrap.innerHTML = "Loading…";
  const data = await api(`api/groups.php?action=leaderboard&group_id=${groupId}`);
  const leaders = data.leaders || [];
  const ownerId = data.owner_user_id;
  if (!leaders || !leaders.length) {
    lbWrap.innerHTML = "No members yet.";
    return;
  }
  lbWrap.innerHTML = leaders.map((u, i) =>
    `<div class="flex justify-between items-center p-2">
       <button class="member-link" data-member="${groupId}:${u.user_id}:${ownerId}">#${i+1} ${u.username}</button>
       <span>${u.xp} XP</span>
     </div>`).join("");

  lbWrap.querySelectorAll(".member-link").forEach(b => {
    const [gid, uid, oid] = b.dataset.member.split(":").map(Number);
    const username = b.textContent?.replace(/#\d+\s*/, "") || "";
    b.addEventListener("click", () => openMemberModal(gid, uid, oid, username));
  });
}

async function createGroup() {
  try {
    groupsErrorEl.textContent = "";

    const name = (createInput?.value || "").trim();
    if (!name) throw new Error("Enter a group name");

    // NEW: read join mode dropdown
    const join_mode = document.getElementById("create-group-join-mode")?.value || "open";

    await api("api/groups.php", "POST", {
      action: "create",
      name,
      join_mode
    });

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

async function requestJoin(groupId) {
  try {
    groupsErrorEl.textContent = "";
    await api("api/groups.php", "POST", { action: "join", group_id: groupId });
    await loadGroups();
  } catch (e) {
    groupsErrorEl.textContent = e.message;
  }
}

async function approveJoinRequest(groupId, userId) {
  try {
    await api("api/groups.php", "POST", {
      action: "approve_request",
      group_id: groupId,
      user_id: userId
    });
    await loadGroups();
  } catch (e) {
    groupsErrorEl.textContent = e.message;
  }
}

async function rejectJoinRequest(groupId, userId) {
  try {
    await api("api/groups.php", "POST", {
      action: "reject_request",
      group_id: groupId,
      user_id: userId
    });
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

async function kickMember(groupId, userId) {
  try {
    await api("api/groups.php", "POST", {
      action: "kick",
      group_id: groupId,
      user_id: userId
    });
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

// ===== NEW CREATE QUEST (supports repeat) =====
async function createQuest() {
  const title  = qTitle.value.trim();
  const desc   = qDesc.value.trim();
  const due    = qDue.value || new Date().toISOString().slice(0,10);
  const diff   = qDiff.value;
  const repeat = $("q-repeat")?.value || "none";

  if (!title) {
    alert("Title required");
    return;
  }

  await api("api/quests.php", "POST", {
    title,
    description: desc,
    due_date: due,
    difficulty: diff,
    repeat_mode: repeat
  });

  qTitle.value = "";
  qDesc.value = "";
  qDiff.value = "medium";
  $("q-repeat").value = "none";

  addModal.close();
  await loadQuests();
}
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
      name:     (regName.value || "").trim(),
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
async function createQuest() {
  await api("api/quests.php", "POST", {
    title:       (qTitle.value || "").trim(),
    description: (qDesc.value || "").trim(),
    due_date:    qDue.value || new Date().toISOString().slice(0,10),
    difficulty:  (qDiff?.value || "medium")
  });
}

btnAdd?.addEventListener("click", async () => {
  try {
    if (editingQuestId) {
      await api("api/quests.php", "PUT", {
        id: editingQuestId,
        title:       (qTitle.value || "").trim(),
        description: (qDesc.value || "").trim(),
        due_date:    qDue.value || new Date().toISOString().slice(0,10),
        difficulty:  (qDiff?.value || "medium")
      });
      editingQuestId = null;
      if (addModalTitle) addModalTitle.textContent = "Add quest";
      if (btnAdd) btnAdd.textContent = "Add quest";
    } else {
      await createQuest();
    }
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
navAchievementsBtn?.addEventListener("click", showAchievements);
navProfileBtn?.addEventListener("click", () => {
  showProfile();
  renderProfile();
});
memberCloseBtn?.addEventListener("click", () => memberModal?.close());
memberViewBtn?.addEventListener("click", () => {
  memberDetails?.classList.toggle("hidden");
});
memberKickBtn?.addEventListener("click", async () => {
  const gid = parseInt(memberKickBtn.dataset.gid || "0", 10);
  const uid = parseInt(memberKickBtn.dataset.uid || "0", 10);
  if (!gid || !uid) return;
  await kickMember(gid, uid);
  memberModal?.close();
});
qdClose?.addEventListener("click", () => questDetailModal?.close());

// Groups actions
btnCreateGroup?.addEventListener("click", createGroup);
btnJoinByName?.addEventListener("click", () => joinGroupByName());

// Auth toggle
showSignup?.addEventListener("click", (e) => {
  e.preventDefault();
  loginCard?.classList.add("hidden");
  signupCard?.classList.remove("hidden");
});
showLogin?.addEventListener("click", (e) => {
  e.preventDefault();
  signupCard?.classList.add("hidden");
  loginCard?.classList.remove("hidden");
});
heroStart?.addEventListener("click", (e) => {
  e.preventDefault();
  loginCard?.classList.add("hidden");
  signupCard?.classList.remove("hidden");
});
heroLogin?.addEventListener("click", (e) => {
  e.preventDefault();
  signupCard?.classList.add("hidden");
  loginCard?.classList.remove("hidden");
});

// Add quest modal
openAddBtn?.addEventListener("click", () => {
  editingQuestId = null;
  if (addModalTitle) addModalTitle.textContent = "Add quest";
  if (btnAdd) btnAdd.textContent = "Add quest";
  if (qTitle) qTitle.value = "";
  if (qDesc) qDesc.value = "";
  if (qDue) qDue.value = "";
  if (qDiff) qDiff.value = "medium";
  addModal?.showModal();
});
addCancel?.addEventListener("click", () => {
  editingQuestId = null;
  if (addModalTitle) addModalTitle.textContent = "Add quest";
  if (btnAdd) btnAdd.textContent = "Add quest";
  addModal?.close();
});
btnAdd?.addEventListener("click", () => addModal?.close());

 // ===== AUTOCOMPLETE FOR QUEST TEMPLATES =====
const sugList = $("template-suggestions");

async function searchTemplates(text) {
  if (!text || text.length < 1) {
    sugList.classList.add("hidden");
    return;
  }

  try {
    const res = await fetch(`/api/search_templates.php?q=${encodeURIComponent(text)}`);
    const items = await res.json();

    if (!items.length) {
      sugList.innerHTML = "";
      sugList.classList.add("hidden");
      return;
    }

    sugList.innerHTML = items.map(t =>
      `<li class="p-2 hover:bg-[#1b2634] cursor-pointer"
           data-id="${t.id}"
           data-title="${t.title}"
           data-desc="${t.description || ''}">
           ${t.title}
       </li>`
    ).join("");

    sugList.classList.remove("hidden");

    sugList.querySelectorAll("li").forEach(li => {
      li.onclick = () => {
        qTitle.value = li.dataset.title;
        qDesc.value  = li.dataset.desc;
        sugList.classList.add("hidden");
      };
    });

  } catch (err) {
    console.error("Template search error:", err);
  }
}

qTitle?.addEventListener("input", () => {
  searchTemplates(qTitle.value.trim());
});

document.addEventListener("click", (e) => {
  if (!sugList.contains(e.target) && e.target !== qTitle) {
    sugList.classList.add("hidden");
  }
});

// ===== Boot: start on Auth; refresh session if exists =====
document.addEventListener("DOMContentLoaded", () => {
  showAuth();   // Hide dashboard/groups on first paint
  refreshMe();  // If session is valid, this will switch to dashboard and reveal buttons
});
