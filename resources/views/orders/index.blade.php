@extends('layouts.app')
@section('title','Wash orders')
@section('content')
<div class="page-head"><div><div class="eyebrow">OPERATIONS</div><h1>Keep the shine moving.</h1><p>Bookings, walk-ins and every wash in between.</p></div>@can('operations')<a href="/orders/new" class="button"><x-icon name="plus"/>New wash order</a>@endcan</div>
<div class="toolbar"><div class="tabs">@foreach([''=>'All orders','scheduled'=>'Booked','waiting'=>'Waiting','washing'=>'Washing','ready'=>'Ready','completed'=>'Completed','cancelled'=>'Cancelled'] as $status=>$label)<a class="tab {{ request('status','')===$status?'active':'' }}" href="{{ url('/orders').'?'.http_build_query(['status'=>$status,'q'=>request('q')]) }}">{{ $label }}</a>@endforeach</div><form class="search-form" method="get"><input type="hidden" name="status" value="{{ request('status') }}"><input name="q" aria-label="Search orders" placeholder="Customer, plate or order ID…" value="{{ request('q') }}"><button type="submit" class="button secondary">Search</button></form></div><section class="panel">@include('orders.table'){{ $orders->links() }}</section>
@endsection
