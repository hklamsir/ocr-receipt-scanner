<?php
/**
 * footer.php - 共用頁尾組件
 */
?>

<footer>
    &copy; 2026 Lamsir 架構系統，Antigravity 編寫程式
</footer>

<!-- 公告載入腳本 -->
<script>
    (function () {
        // 檢查是否已關閉過公告（使用 sessionStorage，每次 session 重新顯示）
        let closedAnnouncements = JSON.parse(sessionStorage.getItem('closedAnnouncements') || '[]');
        let allAnnouncements = [];

        // 顯示公告
        function showAnnouncement() {
            const activeAnnouncements = allAnnouncements.filter(a => !closedAnnouncements.includes(a.id));
            const banner = document.getElementById('announcementBanner');
            const textEl = document.getElementById('announcementText');

            if (activeAnnouncements.length > 0 && banner && textEl) {
                const announcement = activeAnnouncements[0];
                textEl.innerHTML = '<strong>' + announcement.title + '</strong>' +
                    (announcement.content ? '：' + announcement.content : '');
                banner.style.display = 'block';
                banner.dataset.id = announcement.id;
                document.body.classList.add('has-announcement');
            } else if (banner) {
                banner.style.display = 'none';
                document.body.classList.remove('has-announcement');
            }
        }

        // 載入公告
        fetch('api/get_announcements.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.announcements) {
                    allAnnouncements = data.announcements;
                    showAnnouncement();
                }
            })
            .catch(err => console.log('Announcements load skipped'));

        // 關閉公告（立即顯示下一條）
        window.closeAnnouncement = function () {
            const banner = document.getElementById('announcementBanner');
            if (banner) {
                const id = parseInt(banner.dataset.id);
                if (id) {
                    closedAnnouncements.push(id);
                    sessionStorage.setItem('closedAnnouncements', JSON.stringify(closedAnnouncements));
                }
                showAnnouncement(); // 顯示下一條公告
            }
        };
    })();
</script>

</body>

</html>