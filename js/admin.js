/**
 * Admin Panel JavaScript Module
 */

import { Toast, Dialog } from './modules/toast.js';

// ============ CSRF Token Helpers ============
function getCSRFToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content;
}

function getCSRFHeaders() {
  return {
    'X-CSRF-TOKEN': getCSRFToken()
  };
}

// ============ Global State ============
let currentSection = 'dashboard';
let currentUserTab = 'list';
let logPage = 1;
let activityPage = 1;

// ============ Section Navigation ============
window.showSection = function (section) {
  document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.admin-nav .btn').forEach(b => b.classList.remove('active'));

  document.getElementById(section + '-section').classList.add('active');

  // Find and activate the correct button
  const buttons = document.querySelectorAll('.admin-nav .btn');
  const sectionMap = { dashboard: 0, users: 1, maintenance: 2, settings: 3, security: 4 };
  if (sectionMap[section] !== undefined) {
    buttons[sectionMap[section]].classList.add('active');
  }

  currentSection = section;

  // Load section data
  switch (section) {
    case 'dashboard':
      loadDashboard();
      break;
    case 'users':
      loadUsers();
      break;
    case 'maintenance':
      // Health check loaded on demand
      break;
    case 'settings':
      loadSettings();
      loadAnnouncements();
      break;
    case 'security':
      loadLoginAttempts();
      loadIPBlocklist();
      loadActiveSessions();
      break;
  }
};

window.showUserTab = function (tab) {
  document.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.admin-sub-nav .btn').forEach(b => b.classList.remove('active'));

  document.getElementById('user-' + tab + '-tab').classList.add('active');

  const buttons = document.querySelectorAll('.admin-sub-nav .btn');
  const tabMap = { list: 0, create: 1, activity: 2 };
  if (tabMap[tab] !== undefined) {
    buttons[tabMap[tab]].classList.add('active');
  }

  currentUserTab = tab;
  if (tab === 'activity') loadUserActivity();
};

// ============ Dashboard ============
async function loadDashboard() {
  await Promise.all([loadStats(), loadLogs(), loadStorageChart()]);
}

async function loadStats() {
  try {
    const res = await fetch('api/admin/get_stats.php');
    const data = await res.json();

    if (data.success) {
      document.getElementById('statUsers').textContent = data.overview.total_users;
      document.getElementById('statReceipts').textContent = data.overview.total_receipts;
      document.getElementById('statToday').textContent = data.overview.today_receipts;
      document.getElementById('statStorage').textContent = data.storage.total_mb + ' MB';
    }
  } catch (err) {
    console.error('Failed to load stats:', err);
  }
}

window.loadLogs = async function () {
  const logType = document.getElementById('logType').value;
  const search = document.getElementById('logSearch').value;
  const viewer = document.getElementById('logViewer');

  viewer.innerHTML = '<div class="loading">載入中...</div>';

  try {
    const res = await fetch(`api/admin/get_logs.php?type=${logType}&page=${logPage}&search=${encodeURIComponent(search)}`);
    const data = await res.json();

    if (data.success) {
      if (data.logs.length === 0) {
        viewer.innerHTML = '<div class="loading">沒有日誌記錄</div>';
      } else {
        viewer.innerHTML = data.logs.map(log => `
          <div class="log-entry">
            <span class="timestamp">${log.timestamp || ''}</span>
            <span class="message">${escapeHtml(log.message)}</span>
          </div>
        `).join('');
      }

      // Pagination
      renderLogPagination(data.page, data.pages);
    }
  } catch (err) {
    viewer.innerHTML = '<div class="loading text-danger">載入失敗</div>';
  }
};

function renderLogPagination(current, total) {
  const container = document.getElementById('logPagination');
  if (total <= 1) {
    container.innerHTML = '';
    return;
  }

  let html = '';
  for (let i = 1; i <= total; i++) {
    html += `<button class="btn btn-sm ${i === current ? 'active' : ''}" onclick="goLogPage(${i})">${i}</button>`;
  }
  container.innerHTML = html;
}

window.goLogPage = function (page) {
  logPage = page;
  loadLogs();
};

async function loadStorageChart() {
  try {
    const res = await fetch('api/admin/get_storage_info.php');
    const data = await res.json();

    if (data.success && data.by_user.length > 0) {
      const maxSize = Math.max(...data.by_user.map(u => u.size_bytes));

      document.getElementById('storageChart').innerHTML = data.by_user.slice(0, 10).map(user => `
        <div class="storage-bar">
          <span class="username">${escapeHtml(user.username)}</span>
          <div class="bar-container">
            <div class="bar-fill" style="width: ${(user.size_bytes / maxSize * 100).toFixed(1)}%"></div>
          </div>
          <span class="size">${user.size_mb} MB</span>
        </div>
      `).join('');
    } else {
      document.getElementById('storageChart').innerHTML = '<p class="text-muted">沒有儲存資料</p>';
    }
  } catch (err) {
    console.error('Failed to load storage:', err);
  }
}

// ============ User Management ============
async function loadUsers() {
  try {
    const res = await fetch('api/admin/list_users.php');
    const data = await res.json();

    if (data.success) {
      renderUsers(data.users);
      populateUserSelect(data.users);
    }
  } catch (err) {
    console.error('Failed to load users:', err);
  }
}

function renderUsers(users) {
  const tbody = document.getElementById('users-tbody');
  const currentUserId = window.currentUserId || 0;

  tbody.innerHTML = users.map(u => `
    <tr>
      <td>${u.id}</td>
      <td>${escapeHtml(u.username)}</td>
      <td>${u.is_admin ? '🔐 管理員' : '👤 一般'}</td>
      <td>
        <span class="badge ${u.status === 'suspended' ? 'badge-danger' : 'badge-success'}">
          ${u.status === 'suspended' ? '已停用' : '啟用中'}
        </span>
      </td>
      <td>
        <span class="badge badge-info" style="cursor:pointer" onclick="showQuotaModal(${u.id}, ${u.quota_limit || 0})">
          ${u.quota_limit > 0 ? u.quota_limit + '/月' : '無限制'}
        </span>
      </td>
      <td>${u.created_at}</td>
      <td>${u.last_login || '未登入'}</td>
      <td>${u.receipt_count}</td>
      <td>
        <button class="btn btn-sm" onclick="viewUserActivity(${u.id}, '${escapeHtml(u.username)}')">📋</button>
        <button class="btn btn-warning btn-sm" onclick="resetPassword(${u.id}, '${escapeHtml(u.username)}')">🔑</button>
        ${u.id != currentUserId ? `
          <button class="btn btn-sm ${u.status === 'suspended' ? 'btn-success' : 'btn-secondary'}" 
                  onclick="toggleUserStatus(${u.id}, '${u.status === 'suspended' ? 'active' : 'suspended'}')">
            ${u.status === 'suspended' ? '✓' : '⛔'}
          </button>
          <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id}, '${escapeHtml(u.username)}')">🗑️</button>
        ` : ''}
      </td>
    </tr>
  `).join('');
}

function populateUserSelect(users) {
  const select = document.getElementById('activityUser');
  select.innerHTML = '<option value="">所有用戶</option>' +
    users.map(u => `<option value="${u.id}">${escapeHtml(u.username)}</option>`).join('');
}

// Create User Form
document.getElementById('create-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);

  try {
    const res = await fetch('api/admin/create_user.php', {
      method: 'POST',
      headers: getCSRFHeaders(),
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      Toast.success('用戶建立成功！');
      e.target.reset();
      showUserTab('list');
      loadUsers();
    } else {
      Toast.error('建立失敗：' + data.error);
    }
  } catch (err) {
    Toast.error('建立失敗');
  }
});

window.deleteUser = async function (id, username) {
  const confirmed = await Dialog.confirm(
    `確定要刪除用戶「${username}」嗎？<br><br><strong>此操作將同時刪除該用戶的所有單據及圖片！</strong>`,
    { title: '刪除用戶', confirmText: '刪除', danger: true }
  );

  if (!confirmed) return;

  try {
    const res = await fetch('api/admin/delete_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...getCSRFHeaders() },
      body: `user_id=${id}`
    });
    const data = await res.json();

    if (data.success) {
      Toast.success('用戶已刪除');
      loadUsers();
    } else {
      Toast.error('刪除失敗：' + data.error);
    }
  } catch (err) {
    Toast.error('刪除失敗');
  }
};

window.resetPassword = async function (id, username) {
  const newPassword = await Dialog.prompt(
    `為用戶「${username}」設定新密碼：`,
    { title: '重設密碼', inputType: 'password', placeholder: '輸入新密碼' }
  );

  if (!newPassword) return;

  try {
    const res = await fetch('api/admin/reset_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...getCSRFHeaders() },
      body: `user_id=${id}&new_password=${encodeURIComponent(newPassword)}`
    });
    const data = await res.json();

    if (data.success) {
      Toast.success('密碼已重設！');
    } else {
      Toast.error('重設失敗：' + data.error);
    }
  } catch (err) {
    Toast.error('重設失敗');
  }
};

window.toggleUserStatus = async function (id, newStatus) {
  const action = newStatus === 'suspended' ? '停用' : '啟用';
  const confirmed = await Dialog.confirm(`確定要${action}此帳號嗎？`, { title: `${action}帳號` });

  if (!confirmed) return;

  try {
    const res = await fetch('api/admin/update_user_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...getCSRFHeaders() },
      body: `user_id=${id}&status=${newStatus}`
    });
    const data = await res.json();

    if (data.success) {
      Toast.success(data.message);
      loadUsers();
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('操作失敗');
  }
};

// User Activity
window.loadUserActivity = async function () {
  const userId = document.getElementById('activityUser').value;
  const action = document.getElementById('activityAction').value;
  const container = document.getElementById('activityList');

  container.innerHTML = '<div class="loading">載入中...</div>';

  try {
    const res = await fetch(`api/admin/get_user_activity.php?user_id=${userId}&action=${action}&page=${activityPage}`);
    const data = await res.json();

    if (data.success) {
      // Populate action filter
      if (data.available_actions?.length) {
        const actionSelect = document.getElementById('activityAction');
        if (actionSelect.options.length <= 1) {
          data.available_actions.forEach(a => {
            actionSelect.add(new Option(a, a));
          });
        }
      }

      if (data.logs.length === 0) {
        container.innerHTML = '<div class="loading">沒有活動記錄</div>';
      } else {
        container.innerHTML = data.logs.map(log => `
          <div class="activity-item">
            <div class="activity-icon">${getActionIcon(log.action)}</div>
            <div class="activity-content">
              <div class="action"><strong>${log.username}</strong> - ${log.action}</div>
              <div class="details">${log.details || ''}</div>
              <div class="meta">${log.created_at} | IP: ${log.ip_address || 'N/A'}</div>
            </div>
          </div>
        `).join('');
      }
    }
  } catch (err) {
    container.innerHTML = '<div class="loading text-danger">載入失敗</div>';
  }
};

window.viewUserActivity = async function (userId, username) {
  document.getElementById('userActivityTitle').textContent = `${username} 的活動記錄`;
  document.getElementById('userActivityModal').style.display = 'flex';
  const container = document.getElementById('userActivityContent');
  container.innerHTML = '<div class="loading">載入中...</div>';

  try {
    const res = await fetch(`api/admin/get_user_activity.php?user_id=${userId}&limit=50`);
    const data = await res.json();

    if (data.success && data.logs.length > 0) {
      container.innerHTML = data.logs.map(log => `
        <div class="activity-item">
          <div class="activity-icon">${getActionIcon(log.action)}</div>
          <div class="activity-content">
            <div class="action">${log.action}</div>
            <div class="details">${log.details || ''}</div>
            <div class="meta">${log.created_at} | IP: ${log.ip_address || 'N/A'}</div>
          </div>
        </div>
      `).join('');
    } else {
      container.innerHTML = '<p class="text-muted">沒有活動記錄</p>';
    }
  } catch (err) {
    container.innerHTML = '<p class="text-danger">載入失敗</p>';
  }
};

window.closeUserActivityModal = function () {
  document.getElementById('userActivityModal').style.display = 'none';
};

// Quota Modal
window.showQuotaModal = function (userId, currentQuota) {
  document.getElementById('quotaUserId').value = userId;
  document.getElementById('quotaLimit').value = currentQuota;
  document.getElementById('quotaModal').style.display = 'flex';
};

window.closeQuotaModal = function () {
  document.getElementById('quotaModal').style.display = 'none';
};

document.getElementById('quotaForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const userId = document.getElementById('quotaUserId').value;
  const quotaLimit = document.getElementById('quotaLimit').value;

  try {
    const res = await fetch('api/admin/update_user_quota.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...getCSRFHeaders() },
      body: `user_id=${userId}&quota_limit=${quotaLimit}`
    });
    const data = await res.json();

    if (data.success) {
      Toast.success(data.message);
      closeQuotaModal();
      loadUsers();
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('更新失敗');
  }
});

// ============ System Maintenance ============
window.runHealthCheck = async function () {
  const container = document.getElementById('healthStatus');
  container.innerHTML = '<div class="loading">檢查中...</div>';

  try {
    const res = await fetch('api/admin/health_check.php');
    const data = await res.json();

    if (data.success) {
      const overallClass = data.overall.status;
      let html = `
        <div class="health-item ${overallClass}">
          <div class="status-icon">${getStatusIcon(overallClass)}</div>
          <div class="item-name">整體狀態</div>
          <div class="item-message">${data.overall.message}</div>
        </div>
        <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--border-color);">
      `;

      html += data.checks.map(check => `
        <div class="health-item ${check.status}">
          <div class="status-icon">${getStatusIcon(check.status)}</div>
          <div class="item-name">${check.name}</div>
          <div class="item-message">${check.message}</div>
        </div>
      `).join('');

      container.innerHTML = html;
    }
  } catch (err) {
    container.innerHTML = '<div class="loading text-danger">檢查失敗</div>';
  }
};

window.findOrphanImages = async function () {
  const container = document.getElementById('orphanResults');
  container.innerHTML = '<div class="loading">掃描中...</div>';

  try {
    const res = await fetch('api/admin/find_orphan_images.php');
    const data = await res.json();

    if (data.success) {
      let html = `<p>找到 <strong>${data.orphan_count}</strong> 個孤立圖片（${data.orphan_size_mb} MB）</p>`;

      if (data.orphan_count > 0) {
        html += '<form id="orphanCleanupForm">';
        html += data.orphan_images.map(img => `
          <div class="orphan-item">
            <input type="checkbox" name="paths" value="${img.path}">
            <span class="filename">${img.path}</span>
            <span class="size">${img.size_kb} KB</span>
          </div>
        `).join('');
        html += `
          <div style="margin-top: 16px;">
            <button type="button" class="btn btn-danger" onclick="cleanupOrphans()">刪除選中的圖片</button>
            <button type="button" class="btn" onclick="selectAllOrphans()">全選</button>
          </div>
        </form>`;
      }

      if (data.dangling_count > 0) {
        html += `<p class="text-warning" style="margin-top: 16px;">
          另有 ${data.dangling_count} 筆資料庫記錄指向不存在的檔案
        </p>`;
      }

      container.innerHTML = html;
    }
  } catch (err) {
    container.innerHTML = '<div class="loading text-danger">掃描失敗</div>';
  }
};

window.selectAllOrphans = function () {
  document.querySelectorAll('#orphanCleanupForm input[type="checkbox"]').forEach(cb => cb.checked = true);
};

window.cleanupOrphans = async function () {
  const form = document.getElementById('orphanCleanupForm');
  const checked = Array.from(form.querySelectorAll('input[name="paths"]:checked')).map(cb => cb.value);

  if (checked.length === 0) {
    Toast.warning('請選擇要刪除的圖片');
    return;
  }

  const confirmed = await Dialog.confirm(`確定要刪除 ${checked.length} 個孤立圖片嗎？`, { danger: true });
  if (!confirmed) return;

  try {
    const res = await fetch('api/admin/cleanup_orphan_images.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...getCSRFHeaders() },
      body: JSON.stringify({ paths: checked })
    });
    const data = await res.json();

    if (data.success) {
      Toast.success(`已刪除 ${data.deleted_count} 個圖片`);
      findOrphanImages();
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('清理失敗');
  }
};

// Database Backup
window.downloadBackup = async function () {
  const container = document.getElementById('backupInfo');
  const originalContent = container.innerHTML;
  container.innerHTML = '<div class="loading">正在產生備份...</div>';

  try {
    const res = await fetch('api/admin/backup_database.php');

    if (!res.ok) {
      const data = await res.json();
      throw new Error(data.error || '備份失敗');
    }

    // 取得檔案名稱
    const contentDisposition = res.headers.get('Content-Disposition');
    let filename = 'backup.sql';
    if (contentDisposition) {
      const match = contentDisposition.match(/filename="(.+)"/);
      if (match) filename = match[1];
    }

    // 下載檔案
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);

    container.innerHTML = originalContent;
    Toast.success('備份已下載');
  } catch (err) {
    container.innerHTML = originalContent;
    Toast.error(err.message || '備份失敗');
  }
};

// ============ Settings ============
// 定義 API 金鑰設定
const API_KEY_SETTINGS = ['deepseek_api_key', 'ocrspace_api_key', 'ocr_engine', 'gemini_api_key'];

// 設定項顯示名稱對照表
const SETTING_LABELS = {
  'deepseek_api_key': 'DeepSeek API 金鑰',
  'ocrspace_api_key': 'OCR.space API 金鑰',
  'gemini_api_key': 'Gemini API 金鑰',
  'ocr_engine': 'OCR.space 引擎',
  'llm_provider': '文字 LLM 提供者',
  'gemini_vision_enabled': '啟用 Gemini 視覺端到端',
  'max_files_per_upload': '每次上傳最大檔案數',
  'image_quality': '圖片壓縮品質 (1-100)',
  'max_image_size_kb': '圖片最大大小 (KB)',
  'login_max_attempts': '登入失敗最大嘗試次數',
  'login_lockout_minutes': '登入鎖定時間 (分鐘)'
};

async function loadSettings() {
  try {
    const res = await fetch('api/admin/settings.php');
    const data = await res.json();

    if (data.success) {
      const settingsContainer = document.getElementById('settingsList');
      const apiKeysContainer = document.getElementById('apiKeysList');

      // 視覺模式開啟時，文字 LLM 提供者（DeepSeek）自動停用
      const visionEnabled = (data.settings['gemini_vision_enabled']?.value ?? '0') === '1';

      let settingsHtml = '';
      let apiKeysHtml = '';

      Object.entries(data.settings).forEach(([key, val]) => {
        const label = SETTING_LABELS[key] || val.description || key;
        const isApiKey = API_KEY_SETTINGS.includes(key);
        const isPassword = key.includes('api_key');

        // 依設定類型渲染控制項
        let controlHtml;
        let showToggle = isPassword;
        if (key === 'ocr_engine') {
            controlHtml = `
              <select class="form-control setting-input" name="${key}">
                <option value="1" ${val.value === '1' ? 'selected' : ''}>Engine 1 (較穩定)</option>
                <option value="2" ${val.value === '2' ? 'selected' : ''}>Engine 2 (較準確)</option>
              </select>`;
        } else if (key === 'llm_provider') {
            const disabled = visionEnabled ? 'disabled' : '';
            controlHtml = `
              <select class="form-control setting-input" name="${key}" ${disabled}>
                <option value="deepseek" ${val.value === 'deepseek' ? 'selected' : ''}>DeepSeek</option>
                <option value="gemini" ${val.value === 'gemini' ? 'selected' : ''}>Gemini</option>
              </select>
              ${visionEnabled ? '<div class="setting-hint">視覺模式已開啟，DeepSeek 自動停用</div>' : ''}`;
        } else if (key === 'gemini_vision_enabled') {
            const checked = val.value === '1' ? 'checked' : '';
            controlHtml = `
              <label class="switch">
                <input type="checkbox" class="setting-input" name="${key}" value="1" ${checked}>
                <span class="slider"></span>
              </label>
              <div class="setting-hint">啟用後直接使用 Gemini 視覺端到端處理，DeepSeek 自動停用</div>`;
            showToggle = false;
        } else {
            controlHtml = `
              <input type="${isPassword ? 'password' : 'text'}" 
                     class="form-control setting-input ${isPassword ? 'api-key-input' : ''}" 
                     name="${key}" 
                     value="${escapeHtml(val.value)}"
                     ${isPassword ? 'autocomplete="off"' : ''}>`;
        }

        const itemHtml = `
          <div class="setting-item">
            <div>
              <div class="setting-label">${label}</div>
              <div class="setting-description">${val.description || ''}</div>
            </div>
            ${controlHtml}
            ${showToggle ? `<button type="button" class="btn btn-sm toggle-visibility" onclick="toggleApiKeyVisibility(this)">👁️</button>` : ''}
          </div>
        `;

        if (isApiKey) {
          apiKeysHtml += itemHtml;
        } else {
          settingsHtml += itemHtml;
        }
      });

      settingsContainer.innerHTML = settingsHtml || '<p class="text-muted">沒有一般設定</p>';
      if (apiKeysContainer) {
        apiKeysContainer.innerHTML = apiKeysHtml || '<p class="text-muted">沒有 API 金鑰設定</p>';
      }
    }
  } catch (err) {
    console.error('Failed to load settings:', err);
  }
}

// 切換 API 金鑰可見性
window.toggleApiKeyVisibility = function (btn) {
  const input = btn.previousElementSibling;
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
  } else {
    input.type = 'password';
    btn.textContent = '👁️';
  }
};

document.getElementById('settingsForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const inputs = e.target.querySelectorAll('.setting-input');
  const settings = {};
  inputs.forEach(input => {
    // checkbox（如 Gemini 視覺開關）轉為 1/0；其餘取 value
    settings[input.name] = input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value;
  });

  try {
    const res = await fetch('api/admin/settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...getCSRFHeaders() },
      body: JSON.stringify(settings)
    });
    const data = await res.json();

    if (data.success) {
      Toast.success(data.message);
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('儲存失敗');
  }
});

// Announcements
async function loadAnnouncements() {
  try {
    const res = await fetch('api/admin/announcements.php?all=1');
    const data = await res.json();

    if (data.success) {
      const container = document.getElementById('announcementsList');
      if (data.announcements.length === 0) {
        container.innerHTML = '<p class="text-muted" style="padding: 20px;">沒有公告</p>';
      } else {
        container.innerHTML = data.announcements.map(a => `
          <div class="announcement-item">
            <div>
              <div class="title">
                ${a.is_active ? '' : '<span class="badge badge-secondary">已停用</span> '}
                ${escapeHtml(a.title)}
              </div>
              <div class="content">${escapeHtml(a.content || '')}</div>
              <div class="dates">
                ${a.start_date ? '開始: ' + a.start_date : ''}
                ${a.end_date ? ' | 結束: ' + a.end_date : ''}
              </div>
            </div>
            <div>
              <button class="btn btn-sm" onclick='editAnnouncement(${JSON.stringify(a)})'>✏️</button>
              <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(${a.id})">🗑️</button>
            </div>
          </div>
        `).join('');
      }
    }
  } catch (err) {
    console.error('Failed to load announcements:', err);
  }
}

window.showAnnouncementModal = function () {
  document.getElementById('announcementModalTitle').textContent = '新增公告';
  document.getElementById('announcementId').value = '';
  document.getElementById('announcementTitle').value = '';
  document.getElementById('announcementContent').value = '';
  document.getElementById('announcementStart').value = '';
  document.getElementById('announcementEnd').value = '';
  document.getElementById('announcementActive').checked = true;
  document.getElementById('announcementModal').style.display = 'flex';
};

window.editAnnouncement = function (a) {
  document.getElementById('announcementModalTitle').textContent = '編輯公告';
  document.getElementById('announcementId').value = a.id;
  document.getElementById('announcementTitle').value = a.title;
  document.getElementById('announcementContent').value = a.content || '';
  document.getElementById('announcementStart').value = a.start_date ? a.start_date.replace(' ', 'T').slice(0, 16) : '';
  document.getElementById('announcementEnd').value = a.end_date ? a.end_date.replace(' ', 'T').slice(0, 16) : '';
  document.getElementById('announcementActive').checked = a.is_active == 1;
  document.getElementById('announcementModal').style.display = 'flex';
};

window.closeAnnouncementModal = function () {
  document.getElementById('announcementModal').style.display = 'none';
};

document.getElementById('announcementForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = document.getElementById('announcementId').value;
  const payload = {
    title: document.getElementById('announcementTitle').value,
    content: document.getElementById('announcementContent').value,
    start_date: document.getElementById('announcementStart').value || null,
    end_date: document.getElementById('announcementEnd').value || null,
    is_active: document.getElementById('announcementActive').checked ? 1 : 0
  };

  if (id) payload.id = parseInt(id);

  try {
    const res = await fetch('api/admin/announcements.php', {
      method: id ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json', ...getCSRFHeaders() },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      Toast.success(data.message);
      closeAnnouncementModal();
      loadAnnouncements();
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('儲存失敗');
  }
});

window.deleteAnnouncement = async function (id) {
  const confirmed = await Dialog.confirm('確定要刪除此公告嗎？');
  if (!confirmed) return;

  try {
    const res = await fetch(`api/admin/announcements.php?id=${id}`, { method: 'DELETE', headers: getCSRFHeaders() });
    const data = await res.json();

    if (data.success) {
      Toast.success(data.message);
      loadAnnouncements();
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('刪除失敗');
  }
};

// ============ Security ============
window.loadLoginAttempts = async function () {
  const failedOnly = document.getElementById('showFailedOnly').checked;
  const statsContainer = document.getElementById('loginStats');
  const listContainer = document.getElementById('loginAttemptsList');

  try {
    const res = await fetch(`api/admin/get_login_attempts.php?failed_only=${failedOnly ? '1' : '0'}`);
    const data = await res.json();

    if (data.success) {
      // Stats
      const s = data.stats_24h;
      statsContainer.innerHTML = `
        <div class="login-stat">
          <div class="value">${s.total_attempts}</div>
          <div class="label">24h 嘗試</div>
        </div>
        <div class="login-stat">
          <div class="value text-danger">${s.failed_attempts}</div>
          <div class="label">失敗次數</div>
        </div>
        <div class="login-stat">
          <div class="value">${s.unique_ips}</div>
          <div class="label">不同 IP</div>
        </div>
        <div class="login-stat">
          <div class="value text-warning">${s.suspicious_ips}</div>
          <div class="label">可疑 IP</div>
        </div>
      `;

      // List
      listContainer.innerHTML = data.attempts.map(a => `
        <div class="attempt-item ${a.success ? 'success' : 'failed'}">
          <span class="status-icon">${a.success ? '✅' : '❌'}</span>
          <span style="flex:1">${escapeHtml(a.username || '未知')}</span>
          <span style="font-family:monospace">${escapeHtml(a.ip_address)}</span>
          <span style="color:var(--text-muted);font-size:0.8rem">${escapeHtml(a.created_at)}</span>
          ${!a.success ? `<button class="btn btn-sm btn-warning" onclick="blockIP('${escapeHtml(a.ip_address)}')">封鎖</button>` : ''}
        </div>
      `).join('');
    }
  } catch (err) {
    statsContainer.innerHTML = '';
    listContainer.innerHTML = '<div class="loading text-danger">載入失敗</div>';
  }
};

window.loadIPBlocklist = async function () {
  try {
    const res = await fetch('api/admin/manage_ip_block.php');
    const data = await res.json();

    if (data.success) {
      const container = document.getElementById('ipBlocklist');
      if (data.blocklist.length === 0) {
        container.innerHTML = '<p class="text-muted" style="padding: 20px;">沒有封鎖的 IP</p>';
      } else {
        container.innerHTML = data.blocklist.map(b => `
          <div class="ip-block-item">
            <div>
              <div class="ip">${escapeHtml(b.ip_address)}</div>
              <div class="reason">${escapeHtml(b.reason || '無原因')} ${b.blocked_until ? '| 到期: ' + escapeHtml(b.blocked_until) : '| 永久'}</div>
            </div>
            <button class="btn btn-sm btn-success" onclick="unblockIP(${b.id})">解除</button>
          </div>
        `).join('');
      }
    }
  } catch (err) {
    console.error('Failed to load IP blocklist:', err);
  }
};

window.showBlockIPModal = function () {
  document.getElementById('blockIP').value = '';
  document.getElementById('blockReason').value = '';
  document.getElementById('blockDuration').value = '0';
  document.getElementById('blockIPModal').style.display = 'flex';
};

window.closeBlockIPModal = function () {
  document.getElementById('blockIPModal').style.display = 'none';
};

window.blockIP = function (ip) {
  document.getElementById('blockIP').value = ip;
  document.getElementById('blockReason').value = '登入失敗過多';
  document.getElementById('blockDuration').value = '24';
  document.getElementById('blockIPModal').style.display = 'flex';
};

document.getElementById('blockIPForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  try {
    const res = await fetch('api/admin/manage_ip_block.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...getCSRFHeaders() },
      body: JSON.stringify({
        ip_address: document.getElementById('blockIP').value,
        reason: document.getElementById('blockReason').value,
        duration_hours: parseInt(document.getElementById('blockDuration').value)
      })
    });
    const data = await res.json();

    if (data.success) {
      Toast.success(data.message);
      closeBlockIPModal();
      loadIPBlocklist();
      loadLoginAttempts();
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('封鎖失敗');
  }
});

window.unblockIP = async function (id) {
  const confirmed = await Dialog.confirm('確定要解除此 IP 的封鎖嗎？');
  if (!confirmed) return;

  try {
    const res = await fetch(`api/admin/manage_ip_block.php?id=${id}`, { method: 'DELETE', headers: getCSRFHeaders() });
    const data = await res.json();

    if (data.success) {
      Toast.success(data.message);
      loadIPBlocklist();
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('操作失敗');
  }
};

window.loadActiveSessions = async function () {
  try {
    const res = await fetch('api/admin/get_active_sessions.php');
    const data = await res.json();

    if (data.success) {
      const container = document.getElementById('sessionsList');
      if (data.sessions.length === 0) {
        container.innerHTML = '<p class="text-muted" style="padding: 20px;">沒有活動 Session</p>';
      } else {
        container.innerHTML = data.sessions.map(s => `
          <div class="session-item ${s.is_current ? 'current' : ''}">
            <div class="user-info">
              <strong>${escapeHtml(s.username)}</strong>
              <span class="session-meta">
                ${escapeHtml(s.session_id_masked)} | ${escapeHtml(s.ip_address || 'N/A')} | 最後活動: ${escapeHtml(s.last_activity)}
              </span>
            </div>
            ${s.is_current ?
            '<span class="badge badge-info">目前 Session</span>' :
            `<button class="btn btn-sm btn-danger" onclick="forceLogout('${escapeHtml(s.session_id)}')">強制登出</button>`
          }
          </div>
        `).join('');
      }
    }
  } catch (err) {
    console.error('Failed to load sessions:', err);
  }
};

window.forceLogout = async function (sessionId) {
  const confirmed = await Dialog.confirm('確定要強制登出此 Session 嗎？');
  if (!confirmed) return;

  try {
    const res = await fetch('api/admin/force_logout.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', ...getCSRFHeaders() },
      body: JSON.stringify({ session_id: sessionId })
    });
    const data = await res.json();

    if (data.success) {
      Toast.success(data.message);
      loadActiveSessions();
    } else {
      Toast.error(data.error);
    }
  } catch (err) {
    Toast.error('操作失敗');
  }
};

// ============ Utilities ============
function escapeHtml(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function getActionIcon(action) {
  const icons = {
    login: '🔑',
    logout: '🚪',
    ocr_request: '📷',
    save_receipt: '💾',
    delete_receipt: '🗑️',
    user_created: '👤',
    user_suspended: '⛔',
    user_activated: '✅',
    settings_updated: '⚙️',
    ip_blocked: '🚫',
    ip_unblocked: '✓',
    force_logout: '🔒',
    quota_updated: '📊'
  };
  return icons[action] || '📋';
}

function getStatusIcon(status) {
  const icons = { ok: '✓', warning: '⚠', error: '✕', unknown: '?' };
  return icons[status] || '?';
}

let debounceTimer;
window.debounce = function (fn, delay) {
  return function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fn, delay);
  };
};

// ============ Initialize ============
document.addEventListener('DOMContentLoaded', () => {
  loadDashboard();
});

// Export current user ID for template
const userIdScript = document.querySelector('script[data-user-id]');
if (userIdScript) {
  window.currentUserId = parseInt(userIdScript.dataset.userId);
}
