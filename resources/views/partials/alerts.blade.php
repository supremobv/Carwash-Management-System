@if(session('success'))<div class="alert" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert error" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert"><strong>Let’s check a few details.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
