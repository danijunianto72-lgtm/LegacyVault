<?php
// File: app/Models/Plan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'price', 'max_assets'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}