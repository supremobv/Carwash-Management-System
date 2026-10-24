@extends('layouts.app')
@section('title',($record->exists?'Edit ':'Add ').$config['singular'])
@section('content')
<div class="page-head"><div><div class="eyebrow">{{ $config['title'] }}</div><h1>{{ $record->exists?'Edit':'Add' }} {{ $config['singular'] }}</h1><p>{{ $config['description'] }}</p></div><a href="/manage/{{ $module }}" class="button secondary">← Back</a></div>
<section class="panel narrow"><div class="panel-body"><form method="post" action="/manage/{{ $module }}{{ $record->exists?'/'.$record->id:'' }}">@csrf @if($record->exists)@method('PUT')@endif<div class="form-grid">
@foreach($config['fields'] as $key=>$field)
@php($value=old($key, match($key){'price'=>$record->exists?number_format($record->price_cents/100,2,'.',''):null,'amount'=>$record->exists?number_format($record->amount_cents/100,2,'.',''):null,'password'=>null,'active'=>$record->exists?(int)$record->active:1,'spent_on'=>$record->spent_on??today()->toDateString(),default=>$record->$key}))
<div class="field {{ ($field['type']??'')==='textarea'?'full':'' }}"><label for="{{ $key }}">{{ $field['label'] }}@if(str_contains($field['rules'],'required')) * @endif</label>
@if(isset($field['options']))<select id="{{ $key }}" name="{{ $key }}" required>@foreach($field['options'] as $option=>$label)<option value="{{ $option }}" @selected((string)$value===(string)$option)>{{ $label }}</option>@endforeach</select>
@elseif(($field['type']??'')==='customers')<select name="{{ $key }}" id="{{ $key }}" required><option value="">Select a customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected($value==$customer->id)>{{ $customer->name }} · {{ $customer->phone }}</option>@endforeach</select><small><a href="/manage/customers/new">Add a new customer →</a></small>
@elseif(($field['type']??'')==='textarea')<textarea id="{{ $key }}" name="{{ $key }}" maxlength="2000">{{ $value }}</textarea>
@else<input type="{{ $field['type']??'text' }}" id="{{ $key }}" name="{{ $key }}" @if($key!=='password')value="{{ $value }}"@endif @if(($field['type']??'')==='number')step="{{ $key==='duration_minutes'?'1':'0.01' }}" min="{{ $key==='duration_minutes'?'1':'0' }}"@endif @if(str_contains($field['rules'],'required') || ($key==='password' && !$record->exists))required @endif @if($key==='password')minlength="12" autocomplete="new-password"@endif maxlength="{{ $key==='password'?128:255 }}">@endif
@error($key)<small class="danger-text">{{ $message }}</small>@enderror</div>
@if($key==='password')<div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password"></div>@endif
@endforeach</div><div class="form-actions"><button type="submit" class="button">Save {{ $config['singular'] }}</button><a class="button secondary" href="/manage/{{ $module }}">Cancel</a></div></form></div></section>
@endsection
