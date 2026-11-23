<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Shortlisted = 'shortlisted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::Shortlisted => 'Shortlisted',
            self::Approved => 'Accepted',
            self::Rejected => 'Not selected',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft, self::Withdrawn => 'slate',
            self::Submitted => 'blue',
            self::UnderReview => 'purple',
            self::Shortlisted => 'gold',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }

    /** What the applicant sees about this status. */
    public function applicantMessage(): string
    {
        return match ($this) {
            self::Draft => 'Your application is saved as a draft. Submit it before the deadline.',
            self::Submitted => 'Your application has been received and is waiting for review.',
            self::UnderReview => 'The review panel is assessing your application.',
            self::Shortlisted => 'Your application has been shortlisted. The Hub will contact you about next steps.',
            self::Approved => 'Congratulations, your application has been accepted. The Hub will contact you with next steps.',
            self::Rejected => 'Your application was not selected this time. Thank you for applying.',
            self::Withdrawn => 'You withdrew this application.',
        };
    }

    /** Statuses a reviewer can set. */
    public static function reviewable(): array
    {
        return [self::Submitted, self::UnderReview, self::Shortlisted, self::Approved, self::Rejected];
    }

    /** Submitted applications still waiting for a final decision. */
    public static function open(): array
    {
        return [self::Submitted, self::UnderReview, self::Shortlisted];
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Withdrawn], true);
    }

    public function canWithdraw(): bool
    {
        return in_array($this, [self::Draft, self::Submitted, self::UnderReview], true);
    }
}
