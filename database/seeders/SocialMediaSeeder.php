<?php

namespace Database\Seeders;

use App\Models\SocialMedia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SocialMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SocialMedia::create([
            'instagram' => 'https://www.instagram.com/yourcompany',
            'facebook' => 'https://www.facebook.com/yourcompany',
            'x' => 'https://twitter.com/yourcompany',
            'whatsapp' => '+1234567890',
            'email' => 'contact@yourcompany.com',
            'localisation' => 'https://maps.app.goo.gl/n43tjAAxZwytBzhh6',
            'telephone' => '+1234567890',
            'address' => '123 Main Street, City, Country'
        ]);
    }
}
