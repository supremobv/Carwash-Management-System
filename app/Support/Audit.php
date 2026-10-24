<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
class Audit {
    public static function record(string $action, string $subject): void {
        DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>$action,'subject'=>$subject,'ip_address'=>request()->ip(),'created_at'=>now(),'updated_at'=>now()]);
    }
}
