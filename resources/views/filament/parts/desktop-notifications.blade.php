<div id="desktop-notification-banner" style="display: none; background-color: #2563eb; color: white; padding: 12px 24px; text-align: center; font-size: 14px; font-family: sans-serif; position: fixed; bottom: 20px; right: 20px; z-index: 99999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; align-items: center; gap: 12px; border: 1px solid rgba(255,255,255,0.2);">
    <span>🔔 Aktifkan Notifikasi Desktop untuk info peminjaman & keluhan baru.</span>
    <button id="enable-desktop-notifications-btn" style="background-color: white; color: #2563eb; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px; transition: background-color 0.2s;">Aktifkan</button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!("Notification" in window)) {
            console.log("This browser does not support desktop notification");
            return;
        }

        const banner = document.getElementById('desktop-notification-banner');
        const btn = document.getElementById('enable-desktop-notifications-btn');

        function updateBannerVisibility() {
            if (Notification.permission === "default") {
                banner.style.display = 'flex';
            } else {
                banner.style.display = 'none';
            }
        }

        updateBannerVisibility();

        btn.addEventListener('click', function () {
            Notification.requestPermission().then(permission => {
                updateBannerVisibility();
                if (permission === "granted") {
                    new Notification("Notifikasi Aktif!", {
                        body: "Anda akan menerima pemberitahuan peminjaman & keluhan baru di sini.",
                        icon: '/favicon.ico'
                    });
                }
            });
        });

        function playNotificationSound() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
                gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);

                oscillator.start();
                oscillator.stop(audioCtx.currentTime + 0.15);

                setTimeout(() => {
                    const osc2 = audioCtx.createOscillator();
                    const gain2 = audioCtx.createGain();
                    osc2.connect(gain2);
                    gain2.connect(audioCtx.destination);
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(880, audioCtx.currentTime);
                    gain2.gain.setValueAtTime(0.1, audioCtx.currentTime);
                    osc2.start();
                    osc2.stop(audioCtx.currentTime + 0.15);
                }, 200);
            } catch (e) {
                console.error("Audio context error:", e);
            }
        }

        function checkNewNotifications() {
            fetch('{{ route('admin.unread-notifications') }}')
                .then(response => response.json())
                .then(notifications => {
                    if (!Array.isArray(notifications)) return;

                    let shownNotifications = JSON.parse(localStorage.getItem('shown_desktop_notifications') || '[]');
                    let newShown = [...shownNotifications];
                    let hasNew = false;

                    notifications.forEach(notification => {
                        if (!shownNotifications.includes(notification.id)) {
                            hasNew = true;
                            newShown.push(notification.id);

                            if (Notification.permission === "granted") {
                                const options = {
                                    body: notification.body,
                                    icon: '/favicon.ico',
                                    tag: notification.id,
                                    requireInteraction: true
                                };
                                const n = new Notification(notification.title, options);
                                n.onclick = function(event) {
                                    event.preventDefault();
                                    window.focus();
                                    n.close();
                                };
                            }
                        }
                    });

                    if (hasNew) {
                        if (newShown.length > 100) {
                            newShown = newShown.slice(newShown.length - 100);
                        }
                        localStorage.setItem('shown_desktop_notifications', JSON.stringify(newShown));
                        
                        // Play sound
                        playNotificationSound();

                        // Auto refresh if not on create/edit page
                        const path = window.location.pathname;
                        const isEditingOrCreate = path.includes('/create') || path.includes('/edit');
                        if (!isEditingOrCreate) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }

        // Check every 5 seconds
        setInterval(checkNewNotifications, 5000);
        // Also check immediately on load
        setTimeout(checkNewNotifications, 2000);
    });
</script>
