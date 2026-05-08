<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Studio;
use Illuminate\Support\Str;

class StudioSeeder extends Seeder
{
    public function run(): void
    {
        // Make this seeder idempotent for local development (avoid UNIQUE slug collisions)
        \Illuminate\Support\Facades\DB::table('studios')->delete();

        $studios = [

            ['name' => 'MAPPA', 'slug' => 'mappa', 'description' => 'Japanese animation studio known for Attack on Titan Final Season, Jujutsu Kaisen, and Chainsaw Man.'],
            ['name' => 'Ufotable', 'slug' => 'ufotable', 'description' => 'Known for Demon Slayer and Fate series with stunning animation quality.'],
            ['name' => 'Wit Studio', 'slug' => 'wit-studio', 'description' => 'Former studio for Attack on Titan Seasons 1-3, also known for Spy x Family and Vinland Saga.'],
            ['name' => 'Kyoto Animation', 'slug' => 'kyoto-animation', 'description' => 'Known for high-quality animation in Violet Evergarden, A Silent Voice, and K-On!.'],
            ['name' => 'Studio Ghibli', 'slug' => 'studio-ghibli', 'description' => 'Legendary studio behind Spirited Away, My Neighbor Totoro, and Princess Mononoke.'],
            ['name' => 'Madhouse', 'slug' => 'madhouse', 'description' => 'Known for Death Note, Hunter x Hunter (2011), and One Punch Man Season 1.'],
            ['name' => 'A-1 Pictures', 'slug' => 'a1-pictures', 'description' => 'Produced Sword Art Online, Kaguya-sama: Love is War, and Solo Leveling.'],
            ['name' => 'Bones', 'slug' => 'bones', 'description' => 'Known for My Hero Academia, Fullmetal Alchemist: Brotherhood, and Mob Psycho 100.'],
            ['name' => 'CloverWorks', 'slug' => 'cloverworks', 'description' => 'Split from A-1 Pictures, known for The Promised Neverland and Bocchi the Rock!.'],
            ['name' => 'TMS Entertainment', 'slug' => 'tms-entertainment', 'description' => 'One of the oldest anime studios, known for Lupin III and Dr. Stone.'],
            ['name' => 'Toei Animation', 'slug' => 'toei-animation', 'description' => 'Known for Dragon Ball, One Piece, and Sailor Moon.'],
            ['name' => 'Studio Pierrot', 'slug' => 'studio-pierrot', 'description' => 'Known for Naruto, Bleach, and Tokyo Ghoul.'],
            ['name' => 'Production I.G', 'slug' => 'production-ig', 'description' => 'Known for Haikyuu!!, Psycho-Pass, and Ghost in the Shell.'],
            ['name' => 'J.C.Staff', 'slug' => 'jc-staff', 'description' => 'Known for Toradora!, Food Wars!, and One Punch Man Season 2.'],
            ['name' => 'David Production', 'slug' => 'david-production', 'description' => 'Known for JoJo\'s Bizarre Adventure and Fire Force.'],
        ];

        foreach ($studios as $studio) {
            Studio::create($studio);
        }
    }
}
