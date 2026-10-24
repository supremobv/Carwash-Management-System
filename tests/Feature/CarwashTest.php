<?php
namespace Tests\Feature;
use App\Models\{User,Customer,Vehicle,Service,Bay,WashOrder,Setting,InventoryItem};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class CarwashTest extends TestCase
{
    use RefreshDatabase;
    protected User $admin;
    protected function setUp(): void {
        parent::setUp(); $this->seed();
        $this->admin=User::factory()->create(['role'=>'admin','active'=>true]);
    }
    private function user(string $role): User { return User::factory()->create(['role'=>$role,'active'=>true]); }
    private function vehicle(): Vehicle {
        $c=Customer::create(['name'=>'Test customer','phone'=>'09000000000']);
        return Vehicle::create(['customer_id'=>$c->id,'plate'=>'TEST-'.Str::random(6),'make_model'=>'Toyota Vios','size'=>'medium']);
    }
    private function order(): WashOrder {
        $this->actingAs($this->admin)->post('/orders',['vehicle_id'=>$this->vehicle()->id,'services'=>[Service::first()->id],'discount'=>'0'])->assertSessionHasNoErrors()->assertRedirect();
        return WashOrder::latest('id')->firstOrFail();
    }
    public function test_guests_are_redirected_and_login_rotates_to_workspace(): void {
        $this->get('/')->assertRedirect('/login');
        $this->post('/login',['email'=>$this->admin->email,'password'=>'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->admin);
    }
    public function test_all_admin_screens_render(): void {
        $order=$this->order();
        foreach (['/','/orders','/orders/new','/orders/'.$order->id,'/orders/'.$order->id.'/receipt','/reports','/audit','/settings','/account'] as $url) $this->actingAs($this->admin)->get($url)->assertOk();
        foreach (array_keys(config('carwash.modules')) as $module) {
            $this->get('/manage/'.$module)->assertOk(); $this->get('/manage/'.$module.'/new')->assertOk();
        }
    }
    public function test_cashier_cannot_manage_users_settings_catalog_or_reports(): void {
        $this->actingAs($this->user('cashier'));
        foreach (['/settings','/manage/users','/manage/services','/manage/inventory','/reports','/audit','/api/v1/summary'] as $url) $this->get($url)->assertForbidden();
        $this->post('/manage/users',['name'=>'Attacker','role'=>'admin'])->assertForbidden();
    }
    public function test_washer_cannot_read_other_orders_or_collect_payments(): void {
        $order=$this->order(); $this->actingAs($this->user('washer'));
        $this->get('/orders/'.$order->id)->assertForbidden();
        $this->post('/orders/'.$order->id.'/status',['status'=>'cancelled'])->assertForbidden();
        $this->post('/orders/'.$order->id.'/payments',[])->assertForbidden();
        $this->get('/manage/customers')->assertForbidden();
    }
    public function test_totals_are_calculated_on_server_with_discount_and_tax(): void {
        Setting::first()->update(['tax_rate'=>12]); $service=Service::first();
        $this->actingAs($this->admin)->post('/orders',['vehicle_id'=>$this->vehicle()->id,'services'=>[$service->id],'discount'=>'10','total_cents'=>1])->assertSessionHasNoErrors();
        $order=WashOrder::first(); $net=$service->price_cents-1000;
        $this->assertSame($net+(int)round($net*.12),$order->total_cents);
        $service->update(['price_cents'=>99999]); $this->assertEquals(18000,$order->items->first()->price_cents);
    }
    public function test_discount_duplicate_services_and_inactive_services_rejected(): void {
        $v=$this->vehicle(); $s=Service::first(); $this->actingAs($this->admin);
        $this->post('/orders',['vehicle_id'=>$v->id,'services'=>[$s->id],'discount'=>99999])->assertSessionHasErrors('discount');
        $this->post('/orders',['vehicle_id'=>$v->id,'services'=>[$s->id,$s->id]])->assertSessionHasErrors('services.0');
        $s->update(['active'=>false]); $this->post('/orders',['vehicle_id'=>$v->id,'services'=>[$s->id]])->assertSessionHasErrors('services');
        $this->assertDatabaseCount('wash_orders',0);
    }
    public function test_full_wash_flow_bay_conflict_balance_check_and_payment_replay(): void {
        $one=$this->order(); $two=$this->order(); $washer=$this->user('washer'); $bay=Bay::first();
        $start=['status'=>'washing','bay_id'=>$bay->id,'assigned_to'=>$washer->id];
        $this->post('/orders/'.$one->id.'/status',$start)->assertSessionHasNoErrors();
        $this->post('/orders/'.$two->id.'/status',$start)->assertSessionHasErrors('bay_id');
        $this->actingAs($washer)->post('/orders/'.$one->id.'/status',['status'=>'ready'])->assertSessionHasNoErrors();
        $this->post('/orders/'.$one->id.'/status',['status'=>'completed'])->assertSessionHasErrors('status');
        $this->actingAs($this->admin); $payment=['amount'=>$one->total_cents/100,'method'=>'cash','idempotency_key'=>(string)Str::uuid()];
        $this->post('/orders/'.$one->id.'/payments',$payment)->assertSessionHasNoErrors();
        $this->post('/orders/'.$one->id.'/payments',$payment)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments',1);
        $this->post('/orders/'.$one->id.'/status',['status'=>'completed'])->assertSessionHasNoErrors();
        $this->assertEquals('completed',$one->fresh()->status);
    }
    public function test_overpayment_and_unpaid_cancellation_refund_rules(): void {
        $o=$this->order(); $url='/orders/'.$o->id;
        $this->post($url.'/payments',['amount'=>99999,'method'=>'cash','idempotency_key'=>(string)Str::uuid()])->assertSessionHasErrors('amount');
        $this->post($url.'/payments',['amount'=>10,'method'=>'cash','idempotency_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->post($url.'/status',['status'=>'cancelled'])->assertSessionHasErrors('status');
        $this->actingAs($this->user('cashier'))->post($url.'/payments',['amount'=>10,'method'=>'cash','refund'=>1,'idempotency_key'=>(string)Str::uuid()])->assertForbidden();
        $this->actingAs($this->admin)->post($url.'/payments',['amount'=>10,'method'=>'cash','refund'=>1,'reference'=>'Test refund','idempotency_key'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->post($url.'/status',['status'=>'cancelled'])->assertSessionHasNoErrors();
        $this->assertEquals(0,$o->fresh()->paid_cents);
    }
    public function test_stock_cannot_be_negative_and_movements_are_saved(): void {
        $item=InventoryItem::create(['name'=>'Shampoo','unit'=>'L','quantity'=>2,'reorder_level'=>5]); $this->actingAs($this->admin);
        $this->post('/inventory/'.$item->id.'/stock',['quantity'=>-3,'reason'=>'Used'])->assertSessionHasErrors('quantity');
        $this->assertEquals(2,$item->fresh()->quantity);
        $this->post('/inventory/'.$item->id.'/stock',['quantity'=>10,'reason'=>'Delivery'])->assertSessionHasNoErrors();
        $this->assertEquals(12,$item->fresh()->quantity); $this->assertDatabaseCount('stock_movements',1);
        $this->get('/inventory/'.$item->id.'/movements')->assertOk();
    }
    public function test_inactive_accounts_cannot_login_or_continue_session(): void {
        $user=$this->user('cashier'); $user->update(['active'=>false]);
        $this->post('/login',['email'=>$user->email,'password'=>'password'])->assertSessionHasErrors('email');
        $this->actingAs($user)->get('/')->assertRedirect('/login'); $this->assertGuest();
    }
    public function test_admin_cannot_disable_or_demote_self(): void {
        $this->actingAs($this->admin)->put('/manage/users/'.$this->admin->id,['name'=>$this->admin->name,'email'=>$this->admin->email,'role'=>'washer','active'=>1])->assertSessionHasErrors('role');
        $this->assertEquals('admin',$this->admin->fresh()->role);
    }
    public function test_unsafe_logo_rejected_and_raster_logo_served(): void {
        \Illuminate\Support\Facades\Storage::fake('local'); $this->actingAs($this->admin);
        $data=Setting::first()->only(['business_name','currency','timezone','tax_rate']);
        $this->put('/settings',[...$data,'logo'=>\Illuminate\Http\UploadedFile::fake()->create('bad.svg',1,'image/svg+xml')])->assertSessionHasErrors('logo');
        $this->put('/settings',[...$data,'logo'=>\Illuminate\Http\UploadedFile::fake()->image('logo.png',64,64)])->assertSessionHasNoErrors();
        $this->get('/branding/logo')->assertOk()->assertHeader('Content-Type','image/png');
    }
    public function test_output_escapes_customer_names_and_security_headers_present(): void {
        Customer::create(['name'=>'<script>alert(1)</script>','phone'=>'1']);
        $this->actingAs($this->admin)->get('/manage/customers')->assertOk()->assertDontSee('<script>alert(1)</script>',false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertHeader('X-Frame-Options','DENY')->assertHeader('X-Content-Type-Options','nosniff')->assertHeader('Content-Security-Policy');
    }
    public function test_reports_csv_and_session_api_require_report_role(): void {
        $o=$this->order(); $this->post('/orders/'.$o->id.'/payments',['amount'=>10,'method'=>'cash','idempotency_key'=>(string)Str::uuid()]);
        $this->get('/api/v1/summary')->assertOk()->assertJsonPath('net_collected_cents',1000);
        $this->get('/reports?export=csv')->assertOk()->assertDownload('carwash-report.csv');
    }
    public function test_invalid_status_transition_does_not_change_order(): void {
        $o=$this->order(); $this->post('/orders/'.$o->id.'/status',['status'=>'completed'])->assertSessionHasErrors('status');
        $this->assertEquals('waiting',$o->fresh()->status);
    }
    public function test_reused_payment_key_with_changed_amount_is_rejected(): void {
        $o=$this->order(); $key=(string)Str::uuid();
        $this->post('/orders/'.$o->id.'/payments',['amount'=>10,'method'=>'cash','idempotency_key'=>$key])->assertSessionHasNoErrors();
        $this->post('/orders/'.$o->id.'/payments',['amount'=>20,'method'=>'cash','idempotency_key'=>$key])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments',1);
    }
}
