<?php

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Builders\DemoCourseBuilder;
use Database\Seeders\Demo\Catalogs\AccountingCourseCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\Support\SampleInstructor;
use Illuminate\Database\Seeder;

final class DemoCourseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoCourseSeeder در production اجرا نشد.');

            return;
        }

        $this->call(RolesAndPermissionsSeeder::class);

        $instructor = SampleInstructor::findOrCreate()
            ?? throw new \RuntimeException('مدرس نمونه ساخته نشد.');

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
