<?php
namespace App\Http\Controllers;
use App\Models\{Customer,InventoryItem,User};
use App\Services\OrderService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule,ValidationException};
class RecordController extends Controller
{
    private function module(string $module): array { $config=config('carwash.modules.'.$module); abort_unless($config,404); abort_unless(auth()->user()->canDo($config['permission']),403); return $config; }
    public function index(Request $r,string $module)
    {
        $config=$this->module($module); $q=$config['model']::query()->latest();
        if ($module==='vehicles') $q->with('customer');
        if ($r->filled('q')) { $term=mb_substr($r->string('q'),0,100); $field=match($module){'vehicles'=>'plate','expenses'=>'description',default=>'name'}; $q->where($field,'like',"%$term%"); }
        return view('records.index',['module'=>$module,'config'=>$config,'records'=>$q->paginate(15)->withQueryString()]);
    }
    public function form(string $module,?int $id=null)
    {
        $config=$this->module($module); $record=$id?$config['model']::findOrFail($id):new $config['model'];
        return view('records.form',['module'=>$module,'config'=>$config,'record'=>$record,'customers'=>$module==='vehicles'?Customer::orderBy('name')->get():collect()]);
    }
    public function save(Request $r,string $module,?int $id=null)
    {
        $config=$this->module($module); $record=$id?$config['model']::findOrFail($id):new $config['model'];
        $rules=collect($config['fields'])->map(fn($f)=>$f['rules'])->all();
        if ($module==='vehicles') $rules['plate']=['required','string','max:30',Rule::unique('vehicles','plate')->ignore($id)];
        if ($module==='bays') $rules['name']=['required','string','max:255',Rule::unique('bays','name')->ignore($id)];
        if ($module==='users') { $rules['email']=['required','email','max:255',Rule::unique('users','email')->ignore($id)]; if (!$id) $rules['password']='required|string|min:12|max:128|confirmed'; }
        if ($module==='vehicles') $r->merge(['plate'=>mb_strtoupper(trim($r->input('plate','')))]);
        $data=$r->validate($rules);
        if ($module==='users') {
            if (!$r->filled('password')) unset($data['password']);
            if ($id===auth()->id() && ($data['role']!=='admin' || !$data['active'])) throw ValidationException::withMessages(['role'=>'You cannot disable or demote your own administrator account.']);
        }
        if ($module==='services') { $data['price_cents']=OrderService::cents($data['price']); unset($data['price']); }
        if ($module==='expenses') { $data['amount_cents']=OrderService::cents($data['amount']); unset($data['amount']); if (!$id) $data['created_by']=auth()->id(); }
        DB::transaction(function () use ($record,$data,$module,$id) { $record->fill($data)->save(); Audit::record($id?'Updated '.$module:'Created '.$module,(string)$record->id); });
        return redirect('/manage/'.$module)->with('success',ucfirst($config['singular']).' saved.');
    }
    public function stock(Request $r,int $id)
    {
        $this->module('inventory'); $data=$r->validate(['quantity'=>'required|numeric|not_in:0|between:-1000000,1000000|decimal:0,2','reason'=>'required|string|max:255']);
        DB::transaction(function () use ($data,$id) {
            $item=InventoryItem::lockForUpdate()->findOrFail($id);
            if ((float)$item->quantity+(float)$data['quantity']<0) throw ValidationException::withMessages(['quantity'=>'You cannot use more stock than is available.']);
            $item->quantity=round((float)$item->quantity+(float)$data['quantity'],2); $item->save();
            DB::table('stock_movements')->insert([...$data,'inventory_item_id'=>$id,'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]); Audit::record('Adjusted stock',(string)$id);
        });
        return back()->with('success','Stock balance updated.');
    }
    public function movements(int $id)
    {
        $this->module('inventory'); $item=InventoryItem::findOrFail($id);
        $movements=DB::table('stock_movements')->join('users','users.id','=','stock_movements.created_by')->where('inventory_item_id',$id)->select('stock_movements.*','users.name as staff')->orderByDesc('stock_movements.id')->paginate(30);
        return view('records.movements',compact('item','movements'));
    }
}
