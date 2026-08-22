<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BadgeResource;
use App\Models\Badge;
use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BadgeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $badges = Badge::query()->get();

        $userBadgesByBadgeId = UserBadge::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('badge_id', $badges->pluck('id'))
            ->get()
            ->keyBy('badge_id');

        $badges->each(
            fn (Badge $badge) => $badge->setAttribute('user_badge', $userBadgesByBadgeId->get($badge->id))
        );

        return BadgeResource::collection($badges);
    }
}
