<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'image_url',
        'price',
        'quantity',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'integer',
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['category'];

    /**
     * Boot method to handle status updates based on quantity.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {
            // Auto-update status based on quantity
            if ($product->quantity <= 0) {
                $product->status = 'out_of_stock';
            } elseif ($product->quantity < 10) {
                $product->status = 'low_stock';
            } else {
                $product->status = 'in_stock';
            }
        });
    }

    /**
     * Get the category that owns the product.
     */
    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the stock movements for the product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the category name attribute.
     */
    public function getCategoryAttribute(): ?string
    {
        return $this->categoryRelation?->name;
    }

    /**
     * Scope for searching products.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('sku', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by category.
     */
    public function scopeByCategory($query, ?string $category)
    {
        if (!$category || $category === 'all') {
            return $query;
        }

        return $query->whereHas('categoryRelation', function ($q) use ($category) {
            $q->where('name', $category);
        });
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeByStatus($query, ?string $status)
    {
        if (!$status || $status === 'all') {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope for low stock products.
     */
    public function scopeLowStock($query)
    {
        return $query->where('quantity', '<', 10)->where('quantity', '>', 0);
    }

    /**
     * Scope for out of stock products.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Check if product is low on stock.
     */
    public function isLowStock(): bool
    {
        return $this->quantity > 0 && $this->quantity < 10;
    }

    /**
     * Check if product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }
}
