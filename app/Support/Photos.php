<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Campus photography used across the site, with the attribution its licence
 * requires. Files live in public/images/uew/ (resized to 1600px for the web).
 * Source: Wikimedia Commons. The UEW crest is the University's own mark.
 */
final class Photos
{
    public const ALL = [
        'monument' => [
            'src' => 'images/uew/student-centre.jpg',
            'alt' => 'The UEW monument in front of the student centre, North Campus, University of Education, Winneba',
            'credit' => 'Uprising Man',
            'license' => 'CC BY-SA 4.0',
            'source' => 'https://commons.wikimedia.org/wiki/File:Student_centre_of_the_University_of_Education_Winneba,_North_Campus.jpg',
        ],
        'north-campus' => [
            'src' => 'images/uew/campus-north.jpg',
            'alt' => 'Academic building on the North Campus, University of Education, Winneba',
            'credit' => 'OER Africa',
            'license' => 'CC BY 2.0',
            'source' => 'https://commons.wikimedia.org/wiki/File:University_of_Education,_Winneba_(UEW)_North_Campus.jpg',
        ],
        'study-kiosk' => [
            'src' => 'images/uew/study-kiosk.jpg',
            'alt' => 'Students working together at a study kiosk on the UEW campus',
            'credit' => 'OER Africa',
            'license' => 'CC BY 2.0',
            'source' => 'https://commons.wikimedia.org/wiki/File:University_of_Education,_Winneba_Study_Kiosk.jpg',
        ],
        'creative-arts' => [
            'src' => 'images/uew/campus-1.jpg',
            'alt' => 'School of Creative Arts building, University of Education, Winneba',
            'credit' => 'Leooney',
            'license' => 'CC BY-SA 4.0',
            'source' => 'https://commons.wikimedia.org/wiki/File:University_of_Education,_Winneba_1.jpg',
        ],
    ];

    /** @return array{src: string, alt: string, credit: string, license: string, source: string} */
    public static function get(string $key): array
    {
        return self::ALL[$key] ?? self::ALL['monument'];
    }

    public static function url(string $key): string
    {
        return asset(self::get($key)['src']);
    }

    public static function credit(string $key): string
    {
        $p = self::get($key);

        return "Photo: {$p['credit']}, {$p['license']}, via Wikimedia Commons";
    }
}
