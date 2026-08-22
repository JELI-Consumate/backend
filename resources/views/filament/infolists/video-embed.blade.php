@php
    $videoId = $getRecord()->youtube_video_id;
@endphp

@if ($videoId)
    <div class="aspect-video w-full max-w-xl overflow-hidden rounded-lg">
        <iframe
            src="https://www.youtube.com/embed/{{ $videoId }}"
            class="h-full w-full"
            allowfullscreen
        ></iframe>
    </div>
@else
    <p class="text-sm text-gray-500">URL YouTube tidak valid, tidak bisa di-embed.</p>
@endif
