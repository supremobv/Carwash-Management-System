<?php
namespace Database\Seeders;
use App\Models\{Customer,Vehicle,User,WashOrder,Service,Bay,InventoryItem,Expense};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Customer::exists()) { $this->command?->warn('Demo data skipped: customers already exist.'); return; }
        $timezone=\App\Models\Setting::firstOrFail()->timezone;
        config(['app.timezone'=>$timezone]); date_default_timezone_set($timezone);
        $admin=User::where('role','admin')->firstOrFail();
        $washer=User::firstOrCreate(['email'=>'wash.team@example.test'],['name'=>'Alex Rivera','password'=>Str::random(32),'role'=>'washer','active'=>true]);
        $services=Service::where('active',true)->get();
        foreach ([['Jamie Santos','NAB 2048','Toyota Vios','Silver','waiting'],['Morgan Reyes','DCA 3182','Honda Civic','White','washing'],['Taylor Cruz','ABC 1094','Mitsubishi Xpander','Black','ready'],['Casey Lim','NCE 7891','Toyota Fortuner','Pearl','waiting'],['Jordan Tan','BAG 4501','Suzuki Swift','Red','scheduled'],['Sam Garcia','NDF 6023','Honda City','Gray','completed']] as $i=>[$name,$plate,$model,$color,$status]) {
            $customer=Customer::create(['name'=>$name,'phone'=>'0900000000'.$i,'email'=>'customer'.($i+1).'@example.test','notes'=>'Fictional demonstration record.']);
            $vehicle=Vehicle::create(['customer_id'=>$customer->id,'plate'=>$plate,'make_model'=>$model,'color'=>$color,'size'=>$i===3?'large':'medium']);
            $service=$services[$i%$services->count()];
            $order=WashOrder::create(['vehicle_id'=>$vehicle->id,'created_by'=>$admin->id,'status'=>$status,'assigned_to'=>in_array($status,['washing','ready','completed'])?$washer->id:null,'bay_id'=>$status==='washing'?Bay::first()->id:null,'scheduled_at'=>$status==='scheduled'?now()->addDay()->setTime(10,0):null,'started_at'=>in_array($status,['washing','ready','completed'])?now()->subMinutes(25):null,'completed_at'=>$status==='completed'?now():null,'subtotal_cents'=>$service->price_cents,'total_cents'=>$service->price_cents]);
            $order->items()->create(['service_id'=>$service->id,'name'=>$service->name,'price_cents'=>$service->price_cents,'duration_minutes'=>$service->duration_minutes]);
            if (in_array($status,['ready','completed'])) $order->payments()->create(['created_by'=>$admin->id,'amount_cents'=>$service->price_cents,'method'=>'cash','reference'=>'Demo payment','idempotency_key'=>(string)Str::uuid()]);
        }
        foreach (range(1,6) as $days) {
            $original=WashOrder::first(); $order=$original->replicate();
            $order->status='completed'; $order->created_at=now()->subDays($days); $order->updated_at=now()->subDays($days); $order->completed_at=now()->subDays($days); $order->save();
            foreach ($original->items as $item) { $copy=$item->replicate(); $copy->wash_order_id=$order->id; $copy->save(); }
            $order->payments()->create(['created_by'=>$admin->id,'amount_cents'=>$order->total_cents,'method'=>$days%2?'cash':'ewallet','reference'=>'Demo collection','idempotency_key'=>(string)Str::uuid(),'created_at'=>now()->subDays($days),'updated_at'=>now()->subDays($days)]);
        }
        foreach ([['Car shampoo','L',18,5],['Microfiber towels','pcs',4,10],['Tire dressing','L',8,3],['Interior cleaner','L',12,4]] as [$name,$unit,$qty,$threshold]) {
            $item=InventoryItem::create(['name'=>$name,'unit'=>$unit,'quantity'=>$qty,'reorder_level'=>$threshold]);
            \Illuminate\Support\Facades\DB::table('stock_movements')->insert(['inventory_item_id'=>$item->id,'created_by'=>$admin->id,'quantity'=>$qty,'reason'=>'Demo opening stock','created_at'=>now(),'updated_at'=>now()]);
        }
        Expense::create(['description'=>'Demo cleaning supplies','category'=>'supplies','amount_cents'=>85000,'spent_on'=>today(),'created_by'=>$admin->id]);
    }
}
