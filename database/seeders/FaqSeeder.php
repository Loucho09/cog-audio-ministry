<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Demo/initial content seeder. Uses firstOrCreate so re-running never
 * overwrites edits made in Filament.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        // TODO: replace with FAQ content from the Audio Head
        $faqs = [
            ['Do I need audio experience to join?', 'Not necessarily. Share your experience and interest in your application, and the team will guide you from there.'],
            ['How do I apply?', 'Use the Join Us form on this site and choose your branch. Someone from the ministry will get back to you.'],
            ['Which branch should I choose?', 'Pick the branch you attend or are closest to. If you are unsure, send your application and the team can help.'],
            ['How often will I serve?', 'This depends on your branch and the team schedule. Your branch leaders will discuss this with you.'],
            ['Can I ask a question before applying?', 'Yes. Please reach out through the contact details on this page.'],
        ];

        foreach ($faqs as $i => [$q, $a]) {
            Faq::firstOrCreate(['question' => $q], ['answer' => $a, 'sort_order' => $i + 1, 'is_published' => true]);
        }
    }
}
