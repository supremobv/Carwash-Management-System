<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WashOrder extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['scheduled_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime']; }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function bay() { return $this->belongsTo(Bay::class); }
    public function getPaidCentsAttribute(): int { return (int) $this->payments->sum('amount_cents'); }
    public function getBalanceCentsAttribute(): int { return $this->total_cents - $this->paid_cents; }
    public function getNumberAttribute(): string { return 'CW-'.str_pad($this->id, 5, '0', STR_PAD_LEFT); }
}
