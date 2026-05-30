@props([
    'placement' => null,
    'slot' => null,
    'variant' => 'default',
    'format' => 'auto',
])

@php
    $clientId = (string) config('adsense.client_id');
    $debug = (bool) config('adsense.debug');
    $enabled = (bool) config('adsense.enabled');
    $slots = (array) config('adsense.slots', []);

    $slotId = $slot ?? ($placement ? ($slots[$placement] ?? '') : '');
    $slotId = (string) $slotId;
    $variantClass = $variant ?: ($placement ?? 'default');

    $canServeAd = $enabled && filled($clientId) && filled($slotId);
    $showDebugPlaceholder = $debug && $enabled && filled($slotId) && ! $canServeAd;
@endphp

@if($canServeAd)
    <div {{ $attributes->class(['adsense-wrap', "adsense-wrap--{$variantClass}"]) }} role="complementary" aria-label="Advertisement">
        <span class="adsense-label">Advertisement</span>
        <ins
            class="adsbygoogle"
            style="display:block"
            data-ad-client="{{ $clientId }}"
            data-ad-slot="{{ $slotId }}"
            data-ad-format="{{ $format }}"
            data-full-width-responsive="true"
        ></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    </div>
@elseif($showDebugPlaceholder)
    <div {{ $attributes->class(['adsense-wrap', 'adsense-wrap--debug', "adsense-wrap--{$variantClass}"]) }} role="complementary" aria-label="Advertisement placeholder">
        <span class="adsense-label">Advertisement (debug)</span>
        <div class="adsense-debug-box">
            <strong>Ad slot:</strong> {{ $slotId }}
            @if($placement)
                <br><strong>Placement:</strong> {{ $placement }}
            @endif
            @if(! filled($clientId))
                <br><span class="adsense-debug-hint">Set GOOGLE_ADSENSE_CLIENT_ID in .env (ca-pub-… from AdSense dashboard)</span>
            @endif
        </div>
    </div>
@endif
