<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Exam $exam): bool
    {
        return true; // All authenticated users can view
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Exam $exam): bool
    {
        return true;
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $user->isAdmin();
    }

    public function preview(User $user, Exam $exam): bool
    {
        return true;
    }

    public function duplicate(User $user, Exam $exam): bool
    {
        return true;
    }

    public function manageQuestions(User $user, Exam $exam): bool
    {
        return true;
    }
}
