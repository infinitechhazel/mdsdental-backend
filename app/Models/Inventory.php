<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name',
        'category',
        'quantity',
        'unit',
        'expiry_date',
        'cost_price',
        'selling_price',
    ];
}
