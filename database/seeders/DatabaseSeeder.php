<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Setting,Service,Bay};
class DatabaseSeeder extends Seeder {
    public function run(): void {
        Setting::firstOrCreate(['id'=>1],['business_name'=>'AquaFlow','tagline'=>'A little care. A better shine.','address'=>'123 Main Street, Your City','currency'=>'PHP','timezone'=>'Asia/Manila','receipt_footer'=>'Thank you for choosing us. See you for your next shine!']);
        foreach ([['Express wash',180,20,'A fresh exterior wash, rinse and hand dry.'],['Signature clean',350,40,'Exterior wash, tire shine, vacuum and interior wipe.'],['Premium detail',1200,120,'A complete interior and exterior detail with protective wax.'],['Interior refresh',250,30,'Thorough vacuum, dashboard care and glass cleaning.']] as [$name,$price,$duration,$description]) Service::firstOrCreate(['name'=>$name],['price_cents'=>$price*100,'duration_minutes'=>$duration,'description'=>$description]);
        foreach (['Bay 01','Bay 02','Detail studio'] as $name) Bay::firstOrCreate(['name'=>$name]);
    }
}
