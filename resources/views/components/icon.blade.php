@props(['name'=>'grid'])
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
@case('car')<path d="m4 10 2-6h12l2 6M3 10h18v9H3zm3 9v2m12-2v2M6 14h2m8 0h2"/>@break
@case('users')<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m2-17a3 3 0 0 1 0 6m1 5a5 5 0 0 1 3 5"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>@break
@case('spark')<path d="m12 2 3 7 7 3-7 3-3 7-3-7-7-3 7-3Zm7 0v4m-2-2h4"/>@break
@case('box')<path d="m3 7 9-4 9 4v11l-9 4-9-4Zm0 0 9 4 9-4M12 11v11M7 5l10 4"/>@break
@case('chart')<path d="M3 3v18h19M7 16v-5m5 5V6m5 10v-8"/>@break
@case('wallet')<path d="M20 8V5H5a3 3 0 0 0 0 6h16v9H5a3 3 0 0 1-3-3V8m14 6h5"/><circle cx="17" cy="15" r=".5"/>@break
@case('settings')<path d="m9 3-1 3-3 1-2 4 2 2v4l4 2 3-1 3 1 4-2v-4l2-2-2-4-3-1-1-3Z"/><circle cx="12" cy="11" r="3"/>@break
@case('shield')<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m8 12 3 3 5-6"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('arrow')<path d="M4 12h16m-5-5 5 5-5 5"/>@break
@case('logout')<path d="M9 4H4v16h5m3-8h9m-4-4 4 4-4 4"/>@break
@case('menu')<path d="M3 6h18M3 12h18M3 18h18"/>@break
@case('print')<path d="M6 9V3h12v6M6 18H3V9h18v9h-3M6 14h12v8H6Z"/>@break
@default <circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3v.1"/>
@endswitch
</svg>
