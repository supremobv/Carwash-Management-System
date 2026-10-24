<?php
namespace Tests\Feature;
use App\Models\{Setting,Customer,User,Vehicle,Service,WashOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ConfigurationTest extends TestCase
{
    use RefreshDatabase;
    public function test_currency_locks_after_first_order_and_branding_can_still_change(): void {
        $this->seed(); $admin=User::factory()->create(['role'=>'admin','active'=>true]); $this->actingAs($admin);
        $customer=Customer::create(['name'=>'Test','phone'=>'1']); $v=Vehicle::create(['customer_id'=>$customer->id,'plate'=>'TEST','make_model'=>'Test','size'=>'small']);
        $this->post('/orders',['vehicle_id'=>$v->id,'services'=>[Service::first()->id]])->assertSessionHasNoErrors();
        $data=Setting::first()->only(['business_name','currency','timezone','tax_rate']);
        $this->put('/settings',[...$data,'currency'=>'USD'])->assertSessionHasErrors('currency');
        $this->put('/settings',[...$data,'business_name'=>'My business'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('My business');
    }
    public function test_future_booking_checks_in_and_past_booking_is_rejected(): void {
        $this->seed(); $admin=User::factory()->create(['role'=>'admin','active'=>true]);$this->actingAs($admin);
        $customer=Customer::create(['name'=>'Test','phone'=>'1']); $v=Vehicle::create(['customer_id'=>$customer->id,'plate'=>'TEST','make_model'=>'Test','size'=>'small']);
        $data=['vehicle_id'=>$v->id,'services'=>[Service::first()->id]];
        $this->post('/orders',[...$data,'scheduled_at'=>now()->subDay()->format('Y-m-d H:i:s')])->assertSessionHasErrors('scheduled_at');
        $this->post('/orders',[...$data,'scheduled_at'=>now()->addDay()->format('Y-m-d H:i:s')])->assertSessionHasNoErrors();
        $order=WashOrder::first(); $this->assertEquals('scheduled',$order->status);
        $this->post('/orders/'.$order->id.'/status',['status'=>'waiting'])->assertSessionHasNoErrors();
        $this->assertEquals('waiting',$order->fresh()->status);
    }
    public function test_bad_logins_are_throttled(): void {
        $this->seed();
        for($i=0;$i<5;$i++)$this->post('/login',['email'=>'nonexistent@example.test','password'=>'incorrect'])->assertSessionHasErrors('email');
        $response=$this->post('/login',['email'=>'nonexistent@example.test','password'=>'incorrect']);
        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('Too many attempts',session('errors')->first('email'));
    }
    public function test_password_change_requires_current_password_and_hashes_new_password(): void {
        $this->seed(); $user=User::factory()->create(['role'=>'cashier','active'=>true]); $this->actingAs($user);
        $data=['current_password'=>'incorrect','password'=>'a-unique-long-password','password_confirmation'=>'a-unique-long-password'];
        $this->put('/account/password',$data)->assertSessionHasErrors('current_password');
        $this->put('/account/password',[...$data,'current_password'=>'password'])->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('a-unique-long-password',$user->fresh()->password));
        $this->assertNotEquals('a-unique-long-password',$user->fresh()->password);
    }
}
