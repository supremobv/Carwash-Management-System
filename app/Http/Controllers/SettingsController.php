<?php
namespace App\Http\Controllers;
use App\Models\Setting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Validation\Rule;
class SettingsController extends Controller
{
    public function edit() { return view('settings'); }
    public function save(Request $r)
    {
        $data=$r->validate(['business_name'=>'required|string|max:100','tagline'=>'nullable|string|max:160','email'=>'nullable|email|max:255','phone'=>'nullable|string|max:40','address'=>'nullable|string|max:255','currency'=>['required',Rule::in(['PHP','USD','SGD','MYR','EUR','GBP','AUD','CAD','INR'])],'timezone'=>['required',Rule::in(timezone_identifiers_list())],'tax_rate'=>'required|numeric|between:0,100|decimal:0,2','receipt_footer'=>'nullable|string|max:1000','logo'=>'nullable|file|mimes:png,jpg,jpeg,webp|max:2048|dimensions:max_width=2000,max_height=2000','remove_logo'=>'nullable|boolean']);
        $setting=Setting::firstOrFail(); $old=$setting->logo_path;
        if (($data['currency']!==$setting->currency || $data['timezone']!==$setting->timezone) && (\App\Models\WashOrder::exists() || \App\Models\Expense::exists())) {
            throw \Illuminate\Validation\ValidationException::withMessages(['currency'=>'Currency and timezone are locked after the first order or expense to preserve financial history.']);
        }
        unset($data['logo'],$data['remove_logo']);
        if ($r->boolean('remove_logo')) $data['logo_path']=null;
        if ($r->hasFile('logo')) {
            $image=$r->file('logo'); $source=match($image->getMimeType()){'image/png'=>imagecreatefrompng($image->path()),'image/jpeg'=>imagecreatefromjpeg($image->path()),'image/webp'=>imagecreatefromwebp($image->path()),default=>false};
            abort_unless($source,422); $path='logos/'.\Illuminate\Support\Str::uuid().'.png';
            ob_start(); imagepng($source); $bytes=ob_get_clean(); imagedestroy($source);
            if (!Storage::disk('local')->put($path,$bytes)) throw \Illuminate\Validation\ValidationException::withMessages(['logo'=>'The logo could not be saved. Ask your administrator to check storage permissions and free space.']);
            $data['logo_path']=$path;
        }
        DB::transaction(function () use ($setting,$data) { $setting->update($data); Audit::record('Updated business settings','Business'); });
        if (array_key_exists('logo_path',$data) && $old && $old!==$data['logo_path']) Storage::disk('local')->delete($old);
        return back()->with('success','Business settings saved.');
    }
    public function logo() { $path=Setting::firstOrFail()->logo_path; abort_unless($path && Storage::disk('local')->exists($path),404); return response()->file(Storage::disk('local')->path($path),['Content-Type'=>'image/png','X-Content-Type-Options'=>'nosniff']); }
    public function password(Request $r)
    {
        $data=$r->validate(['current_password'=>'required|current_password','password'=>'required|string|min:12|max:128|confirmed|different:current_password']);
        $r->user()->update(['password'=>$data['password'],'remember_token'=>null]); $r->session()->regenerate(); Audit::record('Changed password','Account');
        return back()->with('success','Your password has been updated.');
    }
}
