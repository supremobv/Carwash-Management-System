<?php
namespace App\Http\Controllers;
use App\Models\{WashOrder,Customer,Bay,InventoryItem,Payment,Expense};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->role==='washer') return redirect('/orders');
        $stats=['revenue'=>(int)Payment::whereDate('created_at',today())->sum('amount_cents'),'orders'=>WashOrder::whereDate('created_at',today())->count(),'active'=>WashOrder::whereIn('status',['waiting','washing','ready'])->count(),'customers'=>Customer::count()];
        $orders=WashOrder::with(['vehicle.customer','items','payments','assignee','bay'])->whereIn('status',['waiting','washing','ready'])->oldest()->limit(6)->get();
        $bays=Bay::where('active',true)->get();
        $occupied=WashOrder::with('vehicle')->where('status','washing')->get()->keyBy('bay_id');
        $lowStock=auth()->user()->canDo('inventory')?InventoryItem::whereColumn('quantity','<=','reorder_level')->limit(5)->get():collect();
        $trend=collect(range(6,0))->map(function($days){$date=today()->subDays($days);return ['day'=>$date->format('D'),'amount'=>(int)Payment::whereDate('created_at',$date)->sum('amount_cents')];});
        return view('dashboard',compact('stats','orders','bays','occupied','lowStock','trend'));
    }
    public function reports(Request $r)
    {
        $data=$r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);
        $from=$data['from']??today()->startOfMonth()->toDateString(); $to=$data['to']??today()->toDateString();
        $payments=Payment::whereDate('created_at','>=',$from)->whereDate('created_at','<=',$to);
        $expenses=Expense::whereBetween('spent_on',[$from,$to]);
        $collected=(int)(clone $payments)->where('amount_cents','>',0)->sum('amount_cents');
        $refunds=-(int)(clone $payments)->where('amount_cents','<',0)->sum('amount_cents');
        $spent=(int)(clone $expenses)->sum('amount_cents');
        $rows=(clone $payments)->selectRaw('DATE(created_at) as day, SUM(amount_cents) as net_cents, COUNT(*) as transactions')->groupByRaw('DATE(created_at)')->orderByDesc('day')->get();
        $methods=(clone $payments)->selectRaw('method, SUM(amount_cents) as total')->groupBy('method')->get();
        if ($r->input('export')==='csv') return response()->streamDownload(function () use ($rows) { $f=fopen('php://output','w'); fputcsv($f,['Date','Transactions','Net collected']); foreach ($rows as $row) fputcsv($f,[$row->day,$row->transactions,number_format($row->net_cents/100,2,'.','')]); fclose($f); },'carwash-report.csv',['Content-Type'=>'text/csv']);
        $paidTotals=DB::table('payments')->selectRaw('wash_order_id, SUM(amount_cents) as paid')->groupBy('wash_order_id');
        $outstanding=(int)DB::table('wash_orders')->leftJoinSub($paidTotals,'p',fn($join)=>$join->on('p.wash_order_id','=','wash_orders.id'))->where('status','!=','cancelled')->selectRaw('COALESCE(SUM(total_cents - COALESCE(p.paid,0)),0) as outstanding')->value('outstanding');
        return view('reports',compact('from','to','collected','refunds','spent','rows','methods','outstanding'));
    }
    public function audit() { $logs=DB::table('audit_logs')->leftJoin('users','users.id','=','audit_logs.user_id')->select('audit_logs.*','users.name as staff')->orderByDesc('audit_logs.id')->paginate(30); return view('audit',compact('logs')); }
    public function summary() { return response()->json(['date'=>today()->toDateString(),'orders_today'=>WashOrder::whereDate('created_at',today())->count(),'net_collected_cents'=>(int)Payment::whereDate('created_at',today())->sum('amount_cents'),'queue'=>WashOrder::whereIn('status',['waiting','washing','ready'])->count()]); }
}
