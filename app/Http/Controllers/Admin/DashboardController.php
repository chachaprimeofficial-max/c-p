<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'productCount' => Product::count(),
            'publishedCount' => Product::where('status', 'published')->count(),
            'draftCount' => Product::where('status', 'draft')->count(),
            'categoryCount' => Category::count(),
            'recentProducts' => Product::latest()->take(8)->get(),
        ]);
    }
}
