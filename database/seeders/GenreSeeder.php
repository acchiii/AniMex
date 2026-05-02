<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Genre;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            ['name' => 'Action', 'slug' => 'action', 'color' => '#EF4444', 'sort_order' => 1],
            ['name' => 'Adventure', 'slug' => 'adventure', 'color' => '#F59E0B', 'sort_order' => 2],
            ['name' => 'Comedy', 'slug' => 'comedy', 'color' => '#10B981', 'sort_order' => 3],
            ['name' => 'Drama', 'slug' => 'drama', 'color' => '#6366F1', 'sort_order' => 4],
            ['name' => 'Fantasy', 'slug' => 'fantasy', 'color' => '#8B5CF6', 'sort_order' => 5],
            ['name' => 'Horror', 'slug' => 'horror', 'color' => '#1F2937', 'sort_order' => 6],
            ['name' => 'Mystery', 'slug' => 'mystery', 'color' => '#6B7280', 'sort_order' => 7],
            ['name' => 'Romance', 'slug' => 'romance', 'color' => '#EC4899', 'sort_order' => 8],
            ['name' => 'Sci-Fi', 'slug' => 'sci-fi', 'color' => '#06B6D4', 'sort_order' => 9],
            ['name' => 'Slice of Life', 'slug' => 'slice-of-life', 'color' => '#84CC16', 'sort_order' => 10],
            ['name' => 'Sports', 'slug' => 'sports', 'color' => '#F97316', 'sort_order' => 11],
            ['name' => 'Supernatural', 'slug' => 'supernatural', 'color' => '#A855F7', 'sort_order' => 12],
            ['name' => 'Thriller', 'slug' => 'thriller', 'color' => '#DC2626', 'sort_order' => 13],
            ['name' => 'Mecha', 'slug' => 'mecha', 'color' => '#3B82F6', 'sort_order' => 14],
            ['name' => 'Psychological', 'slug' => 'psychological', 'color' => '#7C3AED', 'sort_order' => 15],
            ['name' => 'Isekai', 'slug' => 'isekai', 'color' => '#0EA5E9', 'sort_order' => 16],
            ['name' => 'Martial Arts', 'slug' => 'martial-arts', 'color' => '#E11D48', 'sort_order' => 17],
            ['name' => 'School', 'slug' => 'school', 'color' => '#14B8A6', 'sort_order' => 18],
        ];

        foreach ($genres as $genre) {
            Genre::create($genre);
        }
    }
}
