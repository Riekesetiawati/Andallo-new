@props(['name' => 'grid', 'class' => 'icon'])
<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
@switch($name)
@case('lotus')
    <path d="M12 20c4-3 6-6.5 6-9.5C18 7 15.5 5 12 5S6 7 6 10.5C6 13.5 8 17 12 20Z"/>
    <path d="M12 20c0-4 1.2-7 4-9"/>
    <path d="M12 20c0-4-1.2-7-4-9"/>
    @break
@case('broom')
    <path d="M14 4l-2 8"/>
    <path d="M8 21c1.5-4 3-7 4-9 1.2 2 2.6 5 4 9"/>
    <path d="M7 21h10"/>
    @break
@case('tools')
    <path d="M14.5 6.5a3 3 0 0 0-4 4L4 17l3 3 6.5-6.5a3 3 0 0 0 4-4L15 12l-3-3 2.5-2.5Z"/>
    <path d="M16 8l2 2"/>
    @break
@case('heart')
    <path d="M12 19s-7-4.4-7-8.5A3.5 3.5 0 0 1 12 8a3.5 3.5 0 0 1 7 2.5C19 14.6 12 19 12 19Z"/>
    @break
@case('graduation')
    <path d="M3 10 12 6l9 4-9 4-9-4Z"/>
    <path d="M7 12.5V16c1.6 1.3 3.2 2 5 2s3.4-.7 5-2v-3.5"/>
    @break
@case('car')
    <path d="M4 15h16l-1.2-4.2A2 2 0 0 0 16.9 9H7.1a2 2 0 0 0-1.9 1.8L4 15Z"/>
    <path d="M5 15v2M19 15v2"/>
    <circle cx="7.5" cy="16.5" r=".8" fill="currentColor"/>
    <circle cx="16.5" cy="16.5" r=".8" fill="currentColor"/>
    @break
@case('camera')
    <path d="M4 8h3l1.5-2h7L17 8h3v10H4V8Z"/>
    <circle cx="12" cy="13" r="3"/>
    @break
@case('laptop')
    <rect x="5" y="5" width="14" height="10" rx="1.5"/>
    <path d="M3 18h18"/>
    @break
@case('grid')
    <rect x="4" y="4" width="6" height="6" rx="1"/>
    <rect x="14" y="4" width="6" height="6" rx="1"/>
    <rect x="4" y="14" width="6" height="6" rx="1"/>
    <rect x="14" y="14" width="6" height="6" rx="1"/>
    @break
@case('pin')
    <path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10Z"/>
    <circle cx="12" cy="11" r="2"/>
    @break
@case('search')
    <circle cx="11" cy="11" r="6"/>
    <path d="M20 20l-3.5-3.5"/>
    @break
@case('star')
    <path d="M12 3.8 14.2 9l5.6.5-4.3 3.6 1.3 5.4L12 15.8 7.2 18.5 8.5 13 4.2 9.5 9.8 9 12 3.8Z" fill="currentColor" stroke="none"/>
    @break
@case('check')
    <path d="M5 12.5 9.2 17 19 7"/>
    @break
@case('calendar')
    <rect x="4" y="5" width="16" height="15" rx="2"/>
    <path d="M8 3v4M16 3v4M4 10h16"/>
    @break
@default
    <circle cx="12" cy="12" r="8"/>
@endswitch
</svg>
