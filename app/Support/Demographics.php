<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Affiliation;
use App\Models\User;

/**
 * Option lists and labels for member demographic and profile data, and the
 * mapping used to pre-fill application questions from a member's profile.
 *
 * Why each item is collected (shown to members in the UI):
 *  - phone: contact about applications
 *  - gender, region, age, disability: eligibility for targeted programmes and
 *    anonymous inclusion reporting to funders (Data Protection Act, 2012 (Act 843))
 *  - education level, institution: eligibility and reviewer context
 */
final class Demographics
{
    public const GENDERS = [
        'female' => 'Female',
        'male' => 'Male',
        'prefer_not' => 'Prefer not to say',
    ];

    public const REGIONS = [
        'Ahafo', 'Ashanti', 'Bono', 'Bono East', 'Central', 'Eastern', 'Greater Accra', 'North East',
        'Northern', 'Oti', 'Savannah', 'Upper East', 'Upper West', 'Volta', 'Western', 'Western North',
        'Outside Ghana',
    ];

    public const EDUCATION_LEVELS = [
        'shs' => 'Senior High School / WASSCE',
        'tvet' => 'TVET / Certificate',
        'diploma' => 'Diploma / HND',
        'undergraduate' => 'Undergraduate (Bachelor\'s)',
        'masters' => 'Master\'s',
        'doctorate' => 'Doctorate (PhD)',
        'other' => 'Other',
    ];

    public const DISABILITY = [
        'no' => 'No',
        'yes' => 'Yes',
        'prefer_not' => 'Prefer not to say',
    ];

    /** Minimum age to create an account. */
    public const MIN_AGE = 15;

    /**
     * Profile values an application question can be pre-filled from.
     *
     * @return array<string, string> key => label (shown in the form builder)
     */
    public static function prefillSources(): array
    {
        return [
            'name' => 'Full name',
            'email' => 'E-mail address',
            'phone' => 'Phone number',
            'gender' => 'Gender',
            'date_of_birth' => 'Date of birth',
            'age' => 'Age',
            'region' => 'Region',
            'affiliation' => 'Affiliation',
            'institution' => 'Institution or organisation',
            'department' => 'Department or programme of study',
            'education_level' => 'Highest education level',
            'bio' => 'Short bio',
        ];
    }

    /** A member's profile value for a pre-fill source, formatted for the given question type. */
    public static function prefillValue(User $user, string $source, string $type): ?string
    {
        $value = match ($source) {
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => self::GENDERS[$user->gender] ?? null,
            'date_of_birth' => $user->date_of_birth?->format($type === 'date' ? 'Y-m-d' : 'j F Y'),
            'age' => $user->age() !== null ? (string) $user->age() : null,
            'region' => $user->region,
            'affiliation' => $user->affiliation?->label(),
            'institution' => $user->institution,
            'department' => $user->department,
            'education_level' => self::EDUCATION_LEVELS[$user->education_level] ?? null,
            'bio' => $user->bio,
            default => null,
        };

        return filled($value) ? (string) $value : null;
    }

    /**
     * The applicant details frozen onto an application when it is submitted,
     * so later profile edits do not change what reviewers assessed.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'date_of_birth' => $user->date_of_birth?->toDateString(),
            'region' => $user->region,
            'affiliation' => $user->affiliation?->value,
            'institution' => $user->institution,
            'department' => $user->department,
            'education_level' => $user->education_level,
            'disability' => $user->disability,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    public static function affiliationLabel(?string $value): ?string
    {
        return $value ? Affiliation::tryFrom($value)?->label() : null;
    }

    /** Age-band label for anonymous reporting. */
    public static function ageBand(?int $age): string
    {
        return match (true) {
            $age === null => 'Not given',
            $age < 18 => 'Under 18',
            $age <= 24 => '18–24',
            $age <= 35 => '25–35',
            $age <= 50 => '36–50',
            default => 'Over 50',
        };
    }
}
