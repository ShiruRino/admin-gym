<?php

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Member
 */
class MemberPtSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeBatches = $this->activePtQuotas;
        $recentLogs = $this->ptSessionLogs()->with('trainer')->latest('used_at')->limit(10)->get();

        return [
            'total_quota' => (int) $activeBatches->sum('total_sessions'),
            'used_quota' => (int) $activeBatches->sum('used_sessions'),
            'remaining_quota' => (int) $activeBatches->sum('remaining_sessions'),
            'active_batches_count' => $activeBatches->count(),
            'recent_sessions' => $recentLogs->map(fn ($log) => [
                'id' => $log->id,
                'trainer_name' => $log->trainer?->name ?? 'Trainer',
                'trainer_specialty' => $log->trainer?->specialty,
                'used_at' => $log->used_at->format('Y-m-d H:i:s'),
                'notes' => $log->notes,
            ]),
        ];
    }
}
