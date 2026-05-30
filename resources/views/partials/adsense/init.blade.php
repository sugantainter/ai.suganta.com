@if(config('adsense.enabled') && filled(config('adsense.client_id')))
<script>
(function () {
    function isFilled(ins) {
        var status = ins.getAttribute('data-adsbygoogle-status');

        if (status === 'unfilled') {
            return false;
        }

        if (status !== 'done') {
            return null;
        }

        var iframe = ins.querySelector('iframe');

        if (!iframe) {
            return false;
        }

        return iframe.offsetHeight > 0 && iframe.offsetWidth > 0;
    }

    function revealWrap(wrap) {
        wrap.classList.remove('adsense-wrap--pending');
        wrap.classList.add('adsense-wrap--filled');
        wrap.hidden = false;
    }

    function removeWrap(wrap) {
        wrap.remove();
    }

    function settleAdSlot(ins) {
        var wrap = ins.closest('.adsense-wrap');

        if (!wrap || wrap.classList.contains('adsense-wrap--debug')) {
            return;
        }

        var filled = isFilled(ins);

        if (filled === true) {
            revealWrap(wrap);
            return;
        }

        if (filled === false) {
            removeWrap(wrap);
        }
    }

    function settleAllAds() {
        document.querySelectorAll('ins.adsbygoogle').forEach(settleAdSlot);
    }

    function watchAdSlot(ins) {
        if (ins.dataset.adsenseWatchBound === '1') {
            return;
        }

        ins.dataset.adsenseWatchBound = '1';

        var observer = new MutationObserver(function () {
            settleAdSlot(ins);
        });

        observer.observe(ins, {
            attributes: true,
            attributeFilter: ['data-adsbygoogle-status', 'style'],
            childList: true,
            subtree: true,
        });
    }

    function initAdsense() {
        var units = document.querySelectorAll('ins.adsbygoogle:not([data-adsbygoogle-status])');

        if (!units.length) {
            settleAllAds();
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
        document.querySelectorAll('.adsense-wrap--filled .adsbygoogle iframe').forEach(function (frame) {
            frame.style.maxWidth = '100%';
            frame.style.width = '100%';
        });
    }

    window.adsenseInitAttempts = 0;

    function boot() {
        document.querySelectorAll('ins.adsbygoogle').forEach(watchAdSlot);
        initAdsense();
        settleAllAds();

        [800, 1500, 3000, 5000, 8000].forEach(function (delay) {
            setTimeout(function () {
                settleAllAds();
                clampAdFrames();
            }, delay);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.addEventListener('resize', clampAdFrames);
    window.addEventListener('orientationchange', function () {
        setTimeout(function () {
            settleAllAds();
            clampAdFrames();
        }, 300);
    });
})();
</script>
@endif
