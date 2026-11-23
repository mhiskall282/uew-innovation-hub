<?php

declare(strict_types=1);

namespace App\Enums;

enum GrantStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Shortlisted = 'shortlisted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::Shortlisted => 'Shortlisted',
            self::Approved => 'Approved',
            self::Rejected => 'Not funded',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'slate',
            self::UnderReview => 'blue',
            self::Shortlisted => 'gold',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }

    /** What the applicant sees on the public tracking page. */
    public function applicantMessage(): string
    {
        return match ($this) {
            self::Submitted => 'Your application has been received and is waiting for review.',
            self::UnderReview => 'The review panel is assessing your application.',
            self::Shortlisted => 'Your application has been shortlisted. The Hub will contact you about a presentation.',
            self::Approved => 'Your application has been approved for funding. The Hub will contact you about next steps.',
            self::Rejected => 'Your application was not selected for funding in this round.',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
