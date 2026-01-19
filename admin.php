<?php
require_once __DIR__ . '/includes/admin_check.php';

// 頁面設定
$pageTitle = '管理後台';
$headerTitle = '管理後台';
include __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="css/admin.css">

<div class="container">
  <h2>系統管理</h2>

  <div class="admin-nav">
    <button class="btn active" onclick="showSection('dashboard')">📊 統計儀表板</button>
    <button class="btn" onclick="showSection('users')">👥 用戶管理</button>
    <button class="btn" onclick="showSection('maintenance')">🔧 系統維護</button>
    <button class="btn" onclick="showSection('settings')">⚙️ 系統設定</button>
    <button class="btn" onclick="showSection('security')">🔐 安全中心</button>
  </div>

  <!-- ============ 統計儀表板 ============ -->
  <div id="dashboard-section" class="admin-section active">
    <h3>📊 統計儀表板</h3>

    <!-- 概覽卡片 -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
          <div class="stat-value" id="statUsers">-</div>
          <div class="stat-label">總用戶數</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🧾</div>
        <div class="stat-content">
          <div class="stat-value" id="statReceipts">-</div>
          <div class="stat-label">總單據數</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
          <div class="stat-value" id="statToday">-</div>
          <div class="stat-label">今日新增</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">💾</div>
        <div class="stat-content">
          <div class="stat-value" id="statStorage">-</div>
          <div class="stat-label">儲存空間</div>
        </div>
      </div>
    </div>

    <!-- 日誌檢視 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>📋 系統日誌</h4>
        <div class="panel-actions">
          <select id="logType" onchange="loadLogs()">
            <option value="error">Error Log</option>
            <option value="access">Access Log</option>
          </select>
          <input type="text" id="logSearch" placeholder="搜尋..." onkeyup="debounce(loadLogs, 500)()">
          <button class="btn btn-sm" onclick="loadLogs()">🔄 重新整理</button>
        </div>
      </div>
      <div class="log-viewer" id="logViewer">
        <div class="loading">載入中...</div>
      </div>
      <div class="log-pagination" id="logPagination"></div>
    </div>

    <!-- 用戶儲存統計 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>💾 用戶儲存空間</h4>
      </div>
      <div class="storage-chart" id="storageChart"></div>
    </div>
  </div>

  <!-- ============ 用戶管理 ============ -->
  <div id="users-section" class="admin-section">
    <h3>👥 用戶管理</h3>

    <div class="admin-sub-nav">
      <button class="btn btn-sm active" onclick="showUserTab('list')">用戶列表</button>
      <button class="btn btn-sm" onclick="showUserTab('create')">新增用戶</button>
      <button class="btn btn-sm" onclick="showUserTab('activity')">活動日誌</button>
    </div>

    <!-- 用戶列表 -->
    <div id="user-list-tab" class="user-tab active">
      <table id="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>用戶名</th>
            <th>角色</th>
            <th>狀態</th>
            <th>配額</th>
            <th>建立時間</th>
            <th>最後登入</th>
            <th>單據數</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody id="users-tbody"></tbody>
      </table>
    </div>

    <!-- 新增用戶 -->
    <div id="user-create-tab" class="user-tab">
      <form id="create-form" style="max-width:500px;">
        <div class="form-group">
          <label>用戶名</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
          <label>密碼</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>角色</label>
          <select name="is_admin" class="form-control">
            <option value="0">一般用戶</option>
            <option value="1">管理員</option>
          </select>
        </div>
        <div class="form-group">
          <label>月配額 (0 = 無限制)</label>
          <input type="number" name="quota_limit" class="form-control" value="0" min="0">
        </div>
        <button type="submit" class="btn btn-primary">建立用戶</button>
      </form>
    </div>

    <!-- 活動日誌 -->
    <div id="user-activity-tab" class="user-tab">
      <div class="filter-bar">
        <select id="activityUser">
          <option value="">所有用戶</option>
        </select>
        <select id="activityAction">
          <option value="">所有操作</option>
        </select>
        <button class="btn btn-sm" onclick="loadUserActivity()">篩選</button>
      </div>
      <div class="activity-list" id="activityList"></div>
    </div>
  </div>

  <!-- ============ 系統維護 ============ -->
  <div id="maintenance-section" class="admin-section">
    <h3>🔧 系統維護</h3>

    <!-- 健康檢查 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>🏥 系統健康檢查</h4>
        <button class="btn btn-sm" onclick="runHealthCheck()">執行檢查</button>
      </div>
      <div class="health-status" id="healthStatus">
        <div class="loading">點擊「執行檢查」開始...</div>
      </div>
    </div>

    <!-- 孤立圖片清理 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>🗑️ 孤立圖片清理</h4>
        <button class="btn btn-sm" onclick="findOrphanImages()">掃描孤立圖片</button>
      </div>
      <div id="orphanResults">
        <p class="text-muted">掃描孤立圖片（檔案存在但資料庫無記錄）</p>
      </div>
    </div>
  </div>

  <!-- ============ 系統設定 ============ -->
  <div id="settings-section" class="admin-section">
    <h3>⚙️ 系統設定</h3>

    <!-- 一般設定 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>🔧 一般設定</h4>
      </div>
      <form id="settingsForm">
        <div class="settings-list" id="settingsList"></div>
        <button type="submit" class="btn btn-primary">儲存設定</button>
      </form>
    </div>

    <!-- 公告管理 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>📢 系統公告</h4>
        <button class="btn btn-sm btn-primary" onclick="showAnnouncementModal()">新增公告</button>
      </div>
      <div id="announcementsList"></div>
    </div>
  </div>

  <!-- ============ 安全中心 ============ -->
  <div id="security-section" class="admin-section">
    <h3>🔐 安全中心</h3>

    <!-- 登入記錄 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>🔑 登入嘗試記錄</h4>
        <div class="panel-actions">
          <label><input type="checkbox" id="showFailedOnly" onchange="loadLoginAttempts()"> 只顯示失敗</label>
          <button class="btn btn-sm" onclick="loadLoginAttempts()">🔄 重新整理</button>
        </div>
      </div>
      <div class="login-stats" id="loginStats"></div>
      <div class="login-attempts-list" id="loginAttemptsList"></div>
    </div>

    <!-- IP 封鎖 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>🚫 IP 封鎖管理</h4>
        <button class="btn btn-sm btn-warning" onclick="showBlockIPModal()">封鎖 IP</button>
      </div>
      <div id="ipBlocklist"></div>
    </div>

    <!-- Session 管理 -->
    <div class="admin-panel">
      <div class="panel-header">
        <h4>🔗 活動 Session</h4>
        <button class="btn btn-sm" onclick="loadActiveSessions()">🔄 重新整理</button>
      </div>
      <div id="sessionsList"></div>
    </div>
  </div>
</div>

<!-- ===== Modals ===== -->

<!-- 公告編輯 Modal -->
<div id="announcementModal" class="edit-modal">
  <div class="edit-modal-content" style="max-width:500px;">
    <div class="edit-modal-header">
      <span id="announcementModalTitle">新增公告</span>
      <button class="close-btn" onclick="closeAnnouncementModal()">✕</button>
    </div>
    <form id="announcementForm">
      <input type="hidden" id="announcementId">
      <div class="form-group">
        <label>標題</label>
        <input type="text" id="announcementTitle" class="form-control" required maxlength="100">
      </div>
      <div class="form-group">
        <label>內容</label>
        <textarea id="announcementContent" class="form-control" rows="4"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>開始時間</label>
          <input type="datetime-local" id="announcementStart" class="form-control">
        </div>
        <div class="form-group">
          <label>結束時間</label>
          <input type="datetime-local" id="announcementEnd" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label><input type="checkbox" id="announcementActive" checked> 啟用</label>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeAnnouncementModal()">取消</button>
        <button type="submit" class="btn btn-primary">儲存</button>
      </div>
    </form>
  </div>
</div>

<!-- IP 封鎖 Modal -->
<div id="blockIPModal" class="edit-modal">
  <div class="edit-modal-content" style="max-width:400px;">
    <div class="edit-modal-header">
      <span>封鎖 IP</span>
      <button class="close-btn" onclick="closeBlockIPModal()">✕</button>
    </div>
    <form id="blockIPForm">
      <div class="form-group">
        <label>IP 位址</label>
        <input type="text" id="blockIP" class="form-control" required placeholder="例: 192.168.1.1">
      </div>
      <div class="form-group">
        <label>原因</label>
        <input type="text" id="blockReason" class="form-control" placeholder="可選">
      </div>
      <div class="form-group">
        <label>封鎖時長</label>
        <select id="blockDuration" class="form-control">
          <option value="0">永久</option>
          <option value="1">1 小時</option>
          <option value="24">24 小時</option>
          <option value="168">7 天</option>
          <option value="720">30 天</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeBlockIPModal()">取消</button>
        <button type="submit" class="btn btn-warning">封鎖</button>
      </div>
    </form>
  </div>
</div>

<!-- 用戶活動 Modal -->
<div id="userActivityModal" class="edit-modal">
  <div class="edit-modal-content" style="max-width:700px;">
    <div class="edit-modal-header">
      <span id="userActivityTitle">用戶活動</span>
      <button class="close-btn" onclick="closeUserActivityModal()">✕</button>
    </div>
    <div id="userActivityContent" style="padding:20px;max-height:500px;overflow-y:auto;"></div>
  </div>
</div>

<!-- 配額設定 Modal -->
<div id="quotaModal" class="edit-modal">
  <div class="edit-modal-content" style="max-width:350px;">
    <div class="edit-modal-header">
      <span>設定配額</span>
      <button class="close-btn" onclick="closeQuotaModal()">✕</button>
    </div>
    <form id="quotaForm">
      <input type="hidden" id="quotaUserId">
      <div class="form-group">
        <label>月配額限制 (0 = 無限制)</label>
        <input type="number" id="quotaLimit" class="form-control" min="0">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeQuotaModal()">取消</button>
        <button type="submit" class="btn btn-primary">儲存</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script type="module" src="js/admin.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>