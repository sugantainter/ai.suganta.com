@if(config('adsense.enabled') && filled(config('adsense.client_id')))
<script>
(function () {
    function initAdsense() {
        var units = document.querySelectorAll('ins.adsbygoogle:not([data-adsbygoogle-status])');
        if (!units.length) {
            return;
        }

        try {
            for (var i = 0; i < units.length; i++) {
                (window.adsbygoogle = window.adsbygoogle || []).push({});
            }
        } catch (e) {
            var attempts = window.adsenseInitAttempts || 0;
            if (attempts < 30) {
                window.adsenseInitAttempts = attempts + 1;
                setTimeout(initAdsense, 200);
            }
        }
    }

    function clampAdFrames() {
        document.querySelectorAll('.adsense-wrap .adsbygoogle iframe').forEach(function (frame) {
            frame.style.maxWidth = '100%';
            frame.style.width = '100%';
        });
    }

    window.adsenseInitAttempts = 0;

    function boot() {
        initAdsense();
        clampAdFrames();
        setTimeout(clampAdFrames, 600);
        setTimeout(clampAdFrames, 1500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.addEventListener('resize', clampAdFrames);
    window.addEventListener('orientationchange', function () {
        setTimeout(clampAdFrames, 300);
    });
})();
</script>
@endif
