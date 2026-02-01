<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class HighRiskStudentsDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The high-risk student predictions
     */
    public Collection $predictions;

    /**
     * Create a new event instance.
     */
    public function __construct(Collection $predictions)
    {
        $this->predictions = $predictions;
    }

    /**
     * Get predictions grouped by exam
     */
    public function getGroupedByExam(): Collection
    {
        return $this->predictions->groupBy('exam_id');
    }

    /**
     * Get critical risk predictions only
     */
    public function getCriticalOnly(): Collection
    {
        return $this->predictions->filter(fn ($p) => $p->isCritical());
    }

    /**
     * Get count of high-risk students
     */
    public function getCount(): int
    {
        return $this->predictions->count();
    }
}
