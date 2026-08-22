<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BadgeResource;
use App\Models\Badge;
use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

final class BadgeController extends Controller
{
    #[OA\Get(
        path: '/badges',
        summary: 'Seluruh badge + status earned/locked (BR-07)',
        tags: ['Gamifikasi'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'journey_id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'icon_url', type: 'string'),
                        new OA\Property(property: 'earned', type: 'boolean'),
                        new OA\Property(property: 'earned_at', type: 'string', format: 'date-time', nullable: true),
                    ], type: 'object')),
                ])
            ),
            new OA\Response(response: 401, description: 'Belum login'),
        ]
    )]
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
