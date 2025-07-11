<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        for ($i = 0; $i < 50; $i++) {
            Student::create([
                'full_name' => $faker->name,
                'date_of_birth' => $faker->dateTimeBetween('1980-01-01', '2005-12-31')->format('Y-m-d'),
                'place_of_birth' => $faker->city,
                'citizen_id' => $faker->unique()->numerify('############'),
                'ethnicity' => 'Kinh',
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->unique()->phoneNumber,
                'address' => $faker->address,
                'gender' => $faker->randomElement(['male', 'female', 'other']),
                'status' => $faker->randomElement(['active', 'inactive', 'potential']),
                'notes' => $faker->optional()->sentence,
            ]);
        }
    }
}
