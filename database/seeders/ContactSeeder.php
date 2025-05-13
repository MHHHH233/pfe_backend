<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Contact::create([
            'nom' => 'John Doe',
            'email' => 'john.doe@example.com',
            'sujet' => 'General Inquiry',
            'message' => 'Hello, I would like more information about your services.'
        ]);
        
        Contact::create([
            'nom' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'sujet' => 'Support Request',
            'message' => 'I am having trouble accessing my account. Can you please help?'
        ]);
        
        Contact::create([
            'nom' => 'Robert Johnson',
            'email' => 'robert.johnson@example.com',
            'sujet' => 'Partnership Opportunity',
            'message' => 'We are interested in discussing potential partnership opportunities with your organization.'
        ]);
    }
}
