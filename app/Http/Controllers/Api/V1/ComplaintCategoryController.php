<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ComplaintCategoryResource;
use App\Models\ComplaintCategory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ComplaintCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = ComplaintCategory::query()
            ->active()
            ->with(['agencies' => fn (BelongsToMany $query) => $query->where('complaint_agencies.is_active', true)])
            ->orderBy('order')
            ->get();

        return ComplaintCategoryResource::collection($categories);
    }
}
