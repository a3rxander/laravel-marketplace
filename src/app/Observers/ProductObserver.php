<?php

namespace App\Observers;

use App\Models\Product;
use Log;

class ProductObserver
{
    public function created(Product $product)
    {  
        
        if ($product->shouldBeSearchable()) {
            $product->searchableUsing($product->searchableAs())->add($product);
        }
    }

    public function updated(Product $product)
    {
        // Debug config
        Log::info('Scout config:', [
            'driver' => config('scout.driver'),
            'elasticsearch' => config('scout.elasticsearch'),
            'queue' => config('scout.queue'),
            'env' => app()->environment(),
        ]);
        Log::info('ProductObserver: updated');
        
        if ($product->shouldBeSearchable()) {
            $product->searchableUsing($product->searchableAs())->update($product);
        } else {
            $product->searchableUsing($product->searchableAs())->delete($product);
        }
    }

    public function deleted(Product $product)
    {
        // Debug config
        Log::info('Scout config:', [
            'driver' => config('scout.driver'),
            'elasticsearch' => config('scout.elasticsearch'),
            'queue' => config('scout.queue'),
            'env' => app()->environment(),
        ]);
        Log::info('ProductObserver: deleted');
        
        $product->searchableUsing($product->searchableAs())->delete($product);
    }
}