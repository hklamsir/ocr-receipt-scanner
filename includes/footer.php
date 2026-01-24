<?php
/**
 * footer.php - 共用頁尾組件
 */
?>

<footer>
    &copy; 2026 Powered by OCRSpace and DeepSeek
</footer>

<!-- 公告載入腳本 -->
<script>
    (function () {
        // 檢查是否已關閉過公告（使用 sessionStorage，每次 session 重新顯示）
        const closedAnnouncements = JSON.parse(sessionStorage.getItem('closedAnnouncements') || '[]');

        // 載入公告
        fetch('api/get_announcements.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.announcements && data.announcements.length > 0) {
                    // 過濾已關閉的公告
                    const activeAnnouncements = data.announcements.filter(a => !closedAnnouncements.includes(a.id));

                    if (activeAnnouncements.length > 0) {
                        const announcement = activeAnnouncements[0]; // 顯示最新的一條
                        const banner = document.getElementById('announcementBanner');
                        const textEl = document.getElementById('announcementText');

                        if (banner && textEl) {
                            textEl.innerHTML = '<strong>' + announcement.title + '</strong>' +
                                (announcement.content ? '：' + announcement.content : '');
                            banner.style.display = 'block';
                            banner.dataset.id = announcement.id;
                            document.body.classList.add('has-announcement');
                        }
                    }
                }
            })
            .catch(err => console.log('Announcements load skipped'));

        // 關閉公告
        window.closeAnnouncement = function () {
            const banner = document.getElementById('announcementBanner');
            if (banner) {
                const id = parseInt(banner.dataset.id);
                if (id) {
                    closedAnnouncements.push(id);
                    sessionStorage.setItem('closedAnnouncements', JSON.stringify(closedAnnouncements));
                }
                banner.style.display = 'none';
                document.body.classList.remove('has-announcement');
            }
        };
    })();
</script>

</body>

</html>