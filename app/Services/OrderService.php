<?php
namespace App\Services;
use App\Models\{WashOrder,Service,User,Bay,Setting,Payment};
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class OrderService
{
    public static function cents(string|int|float $value): int { return (int) round((float)$value*100); }
    public function create(array $data): WashOrder
    {
        return DB::transaction(function () use ($data) {
            $services=Service::whereIn('id',$data['services'])->where('active',true)->lockForUpdate()->get();
            if ($services->count()!==count($data['services'])) throw ValidationException::withMessages(['services'=>'A selected service is unavailable. Please choose again.']);
            $subtotal=(int)$services->sum('price_cents'); $discount=self::cents($data['discount']??0);
            if ($discount>$subtotal) throw ValidationException::withMessages(['discount'=>'Discount cannot exceed the service total.']);
            $tax=(int)round(($subtotal-$discount)*(float)Setting::firstOrFail()->tax_rate/100);
            $order=WashOrder::create(['vehicle_id'=>$data['vehicle_id'],'created_by'=>auth()->id(),'status'=>empty($data['scheduled_at'])?'waiting':'scheduled','scheduled_at'=>$data['scheduled_at']??null,'subtotal_cents'=>$subtotal,'discount_cents'=>$discount,'tax_cents'=>$tax,'total_cents'=>$subtotal-$discount+$tax,'notes'=>$data['notes']??null]);
            foreach ($services as $service) $order->items()->create(['service_id'=>$service->id,'name'=>$service->name,'price_cents'=>$service->price_cents,'duration_minutes'=>$service->duration_minutes]);
            Audit::record('Created order',$order->number); return $order;
        });
    }
    public function transition(int $id, array $data): void
    {
        DB::transaction(function () use ($id,$data) {
            $order=WashOrder::lockForUpdate()->findOrFail($id);
            $user=auth()->user();
            if ($user->role==='washer' && $order->assigned_to!==$user->id) abort(403,'You may update only washes assigned to you.');
            $allowed=['scheduled'=>['waiting','cancelled'],'waiting'=>['washing','cancelled'],'washing'=>['ready'],'ready'=>['completed'],'completed'=>[],'cancelled'=>[]];
            $next=$data['status'];
            if (!in_array($next,$allowed[$order->status]??[],true)) throw ValidationException::withMessages(['status'=>'This status change is no longer available. Refresh and try again.']);
            if ($next==='cancelled' && $order->payments()->sum('amount_cents')>0) throw ValidationException::withMessages(['status'=>'Refund the payments before cancelling this order.']);
            if ($next==='washing') {
                $bay=Bay::where('active',true)->lockForUpdate()->find($data['bay_id']??0);
                $staff=User::where('active',true)->whereIn('role',['washer','manager','admin'])->lockForUpdate()->find($data['assigned_to']??0);
                if (!$bay || !$staff) throw ValidationException::withMessages(['bay_id'=>'Choose an active bay and an available wash team member.']);
                if (WashOrder::where('status','washing')->where(fn($q)=>$q->where('bay_id',$bay->id)->orWhere('assigned_to',$staff->id))->exists()) throw ValidationException::withMessages(['bay_id'=>'This bay or team member is already washing another vehicle.']);
                $order->bay_id=$bay->id; $order->assigned_to=$staff->id; $order->started_at=now();
            }
            if ($next==='completed') {
                if ($order->payments()->sum('amount_cents')<$order->total_cents) throw ValidationException::withMessages(['status'=>'Collect the remaining balance before completing this order.']);
                $order->completed_at=now();
            }
            $order->status=$next; $order->save(); Audit::record('Status → '.$next,$order->number);
        });
    }
    public function pay(int $id, array $data, bool $refund=false): void
    {
        DB::transaction(function () use ($id,$data,$refund) {
            $order=WashOrder::lockForUpdate()->findOrFail($id);
            $existing=Payment::where('idempotency_key',$data['idempotency_key'])->first();
            if ($existing) {
                $expected=self::cents($data['amount'])*($refund?-1:1);
                if ($existing->wash_order_id!==$order->id || $existing->amount_cents!==$expected || $existing->method!==$data['method']) throw ValidationException::withMessages(['amount'=>'This payment form was already submitted with different details. Reload the order and check its payment history.']);
                return;
            }
            if ($order->status==='cancelled') throw ValidationException::withMessages(['amount'=>'Cancelled orders cannot receive payments.']);
            $amount=self::cents($data['amount']); $paid=(int)$order->payments()->sum('amount_cents');
            if ($amount<1 || ($refund ? $amount>$paid : $amount>$order->total_cents-$paid)) throw ValidationException::withMessages(['amount'=>$refund?'Refund exceeds the amount collected.':'Payment exceeds the outstanding balance.']);
            $order->payments()->create(['created_by'=>auth()->id(),'amount_cents'=>$refund?-$amount:$amount,'method'=>$data['method'],'reference'=>$data['reference']??null,'idempotency_key'=>$data['idempotency_key']]);
            Audit::record($refund?'Recorded refund':'Recorded payment',$order->number);
        });
    }
}
