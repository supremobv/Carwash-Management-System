<?php
namespace App\Http\Controllers;
use App\Models\{WashOrder,Vehicle,Service,Bay,User};
use App\Services\OrderService;
use Illuminate\Http\Request;
class OrderController extends Controller
{
    public function index(Request $r)
    {
        $q=WashOrder::with(['vehicle.customer','items','payments','assignee','bay'])->latest();
        if (auth()->user()->role==='washer') $q->where('assigned_to',auth()->id());
        if ($r->filled('status')) $q->where('status',$r->string('status')->toString());
        if ($r->filled('q')) { $term=mb_substr($r->string('q'),0,100); $q->where(fn($q)=>$q->whereHas('vehicle',fn($v)=>$v->where('plate','like',"%$term%")->orWhereHas('customer',fn($c)=>$c->where('name','like',"%$term%")))->orWhere('id',is_numeric($term)?(int)$term:0)); }
        return view('orders.index',['orders'=>$q->paginate(15)->withQueryString()]);
    }
    public function create() { return view('orders.create',['vehicles'=>Vehicle::with('customer')->orderBy('plate')->get(),'services'=>Service::where('active',true)->get()]); }
    public function store(Request $r,OrderService $service)
    {
        $data=$r->validate(['vehicle_id'=>'required|exists:vehicles,id','services'=>'required|array|min:1|max:20','services.*'=>'required|integer|distinct|exists:services,id','discount'=>'nullable|numeric|min:0|max:1000000|decimal:0,2','scheduled_at'=>'nullable|date|after:now','notes'=>'nullable|string|max:2000']);
        $order=$service->create($data); return redirect('/orders/'.$order->id)->with('success','Order created. You’re ready to go.');
    }
    public function show(WashOrder $order)
    {
        if (auth()->user()->role==='washer' && $order->assigned_to!==auth()->id()) abort(403);
        $order->load(['vehicle.customer','items','payments','assignee','bay']);
        return view('orders.show',['order'=>$order,'bays'=>Bay::where('active',true)->get(),'staff'=>User::where('active',true)->whereIn('role',['washer','manager','admin'])->get()]);
    }
    public function transition(Request $r,WashOrder $order,OrderService $service)
    {
        $data=$r->validate(['status'=>'required|in:waiting,washing,ready,completed,cancelled','bay_id'=>'nullable|integer','assigned_to'=>'nullable|integer']);
        if (auth()->user()->role==='washer' && !in_array($data['status'],['ready','completed'])) abort(403);
        $service->transition($order->id,$data); return back()->with('success','Wash status updated.');
    }
    public function payment(Request $r,WashOrder $order,OrderService $service)
    {
        $data=$r->validate(['amount'=>'required|numeric|min:0.01|max:10000000|decimal:0,2','method'=>'required|in:cash,card,bank,ewallet','reference'=>'nullable|string|max:255','idempotency_key'=>'required|uuid','refund'=>'nullable|boolean']);
        $refund=(bool)($data['refund']??false);
        if ($refund && !in_array(auth()->user()->role,['admin','manager'])) abort(403);
        if ($refund) $r->validate(['reference'=>'required|string|max:255']);
        $service->pay($order->id,$data,$refund); return back()->with('success',$refund?'Refund recorded.':'Payment recorded. Receipt updated.');
    }
    public function receipt(WashOrder $order) { $order->load(['vehicle.customer','items','payments']); return view('orders.receipt',compact('order')); }
}
