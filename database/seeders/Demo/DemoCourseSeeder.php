<?php

namespace Database\Seeders\Demo;

use App\Models\User;
use Database\Seeders\Demo\Builders\DemoCourseBuilder;
use Database\Seeders\Demo\Catalogs\AccountingCourseCatalog;
use Illuminate\Database\Seeder;

final class DemoCourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::query()->updateOrCreate(
            ['phone' => '09120000001'],
            [
                'name' => 'مرتضی رهبر',
                'email' => 'instructor@rahbarhesab.com',
                'mobile' => '09120000001',
                'password' => 'password',
                'role' => User::ROLE_INSTRUCTOR,
                'status' => 'active',
            ]
        );

        $catalog = app(AccountingCourseCatalog::class);
        $builder = app(DemoCourseBuilder::class);

        $lessonCount = 0;

        foreach ($catalog->courses() as $courseDefinition) {
            $course = $builder->build($courseDefinition, $instructor);
            $lessonCount += $course->lessonsCount();
        }

        $this->command?->info(sprintf(
            'Demo courses ready: %d courses, %d lessons.',
            count($catalog->courses()),
            $lessonCount
        ));
    }
}
