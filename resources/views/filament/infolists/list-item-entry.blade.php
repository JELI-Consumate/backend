@php
    $record = $getRecord();
@endphp

<div style="display:flex; align-items:flex-start; gap:0.75rem;">
    <span style="
        flex-shrink:0;
        display:flex;
        align-items:center;
        justify-content:center;
        width:1.75rem;
        height:1.75rem;
        border-radius:9999px;
        background-color:var(--primary-50);
        color:var(--primary-600);
        font-weight:600;
        font-size:0.8125rem;
        line-height:1;
    ">
        {{ $record->list_item_number }}
    </span>
    <p style="margin:0; padding-top:0.25rem;">{{ $record->text_article }}</p>
</div>
