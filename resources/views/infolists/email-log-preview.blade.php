@php
    $previewHtml = $html;

    if (blank($previewHtml) && filled($text)) {
        $previewHtml = '<!doctype html><html><head><meta charset="utf-8"></head>'
            .'<body style="margin:0;padding:16px;font-family:ui-sans-serif,system-ui,sans-serif;'
            .'white-space:pre-wrap;word-break:break-word;color:#111827;">'
            .e($text).
            '</body></html>';
    }
@endphp

<div class="overflow-hidden rounded-xl bg-white ring-1 ring-gray-950/5 dark:ring-white/10">
    @if (filled($previewHtml))
        <iframe
            class="h-[40rem] w-full bg-white"
            sandbox="allow-same-origin"
            loading="lazy"
            srcdoc="{{ $previewHtml }}"
        ></iframe>
    @else
        <div class="p-6 text-sm text-gray-500 dark:text-gray-400">
            No stored preview is available for this email.
        </div>
    @endif
</div>
