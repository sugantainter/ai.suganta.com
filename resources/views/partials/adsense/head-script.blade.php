@php
    $clientId = (string) config('adsense.client_id');
    $adsenseEnabled = config('adsense.enabled') && filled($clientId);
@endphp

@if($adsenseEnabled)
    <link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>
    <link rel="preconnect" href="https://googleads.g.doubleclick.net" crossorigin>
    <script
        async
        src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $clientId }}"
        crossorigin="anonymous"
    ></script>
@endif
