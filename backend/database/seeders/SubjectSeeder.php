<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        Subject::query()->delete();

        $subjects = [
            // Grade 1-5
            ['name' => 'தமிழ்', 'price' => 500, 'category' => 'grade_1_to_5'],
            ['name' => 'ஆங்கிலம்', 'price' => 550, 'category' => 'grade_1_to_5'],
            ['name' => 'சூழற்றாடல்', 'price' => 500, 'category' => 'grade_1_to_5'],
            ['name' => 'சமயம்', 'price' => 500, 'category' => 'grade_1_to_5'],
            ['name' => 'சிங்களம்', 'price' => 500, 'category' => 'grade_1_to_5'],
            ['name' => 'புலமைப்பரிசில் வகுப்புகள்', 'price' => 600, 'category' => 'grade_1_to_5'],

            // Grade 6-11
            ['name' => 'தமிழ்', 'price' => 500, 'category' => 'grade_6_to_11'],
            ['name' => 'ஆங்கிலம்', 'price' => 550, 'category' => 'grade_6_to_11'],
            ['name' => 'கணிதம்', 'price' => 600, 'category' => 'grade_6_to_11'],
            ['name' => 'வரலாறு', 'price' => 500, 'category' => 'grade_6_to_11'],
            ['name' => 'சமயம்', 'price' => 500, 'category' => 'grade_6_to_11'],
            ['name' => 'விஞ்ஞானம்', 'price' => 600, 'category' => 'grade_6_to_11'],
            ['name' => 'குடியியல் கல்வி', 'price' => 500, 'category' => 'grade_6_to_11'],
            ['name' => 'புவியியல்', 'price' => 500, 'category' => 'grade_6_to_11'],
            ['name' => 'சிங்களம்', 'price' => 500, 'category' => 'grade_6_to_11'],
            ['name' => 'ICT', 'price' => 700, 'category' => 'grade_6_to_11'],
            ['name' => 'சுகாதாரம் உள்கல்வியும்', 'price' => 500, 'category' => 'grade_6_to_11'],
            ['name' => 'வணிகக் கல்வி', 'price' => 500, 'category' => 'grade_6_to_11'],
            ['name' => 'இலக்கியம் (தமிழ்)', 'price' => 500, 'category' => 'grade_6_to_11'],

            // A/L Arts Stream
            ['name' => 'தமிழ்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'வரலாறு', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'புவியியல்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'ICT', 'price' => 700, 'category' => 'arts_stream'],
            ['name' => 'அரசியல் விஞ்ஞானம்', 'price' => 600, 'category' => 'arts_stream'],
            ['name' => 'இந்து நாகரிகம்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'மனைப்பொருளியல்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'ஊடகக் கல்வி', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'நடனம்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'நாடகம்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'சித்திரம்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'சங்கீதம்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'கிறிஸ்தவ நாகரிகம்', 'price' => 500, 'category' => 'arts_stream'],
            ['name' => 'அளவையியல்', 'price' => 500, 'category' => 'arts_stream'],

            // A/L Bio & Maths Stream
            ['name' => 'இணைந்த கணிதம்', 'price' => 700, 'category' => 'bio_maths_stream'],
            ['name' => 'உயிரியல்', 'price' => 700, 'category' => 'bio_maths_stream'],
            ['name' => 'பெளதிகவியல்', 'price' => 700, 'category' => 'bio_maths_stream'],
            ['name' => 'இரசாயனவியல்', 'price' => 700, 'category' => 'bio_maths_stream'],
            ['name' => 'ICT', 'price' => 700, 'category' => 'bio_maths_stream'],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}
