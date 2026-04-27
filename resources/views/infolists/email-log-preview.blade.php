@php
    $previewHtml = $html;

    if (blank($previewHtml) && filled($text)) {
        $previewHtml = '<!doctype html><html><body style="margin:0;padding:16px;font-family:ui-sans-serif,system-ui,sans-serif;white-space:pre-wrap;">'
            .e($text).
            '</body></html>';
    }
@endphp

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    @if (filled($previewHtml))
        <iframe
            class="h-[36rem] w-full bg-white"
            sandbox="allow-same-origin"
            srcdoc="{{ $previewHtml }}"
        ></iframe>
    @else
        <div class="p-4 text-sm text-gray-500">
            No stored preview is available for this email.
        </div>
    @endif
</div>
