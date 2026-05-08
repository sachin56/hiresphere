<?php

namespace Database\Seeders;

use App\Models\AvailabilitySlot;
use App\Models\CandidateProfile;
use App\Models\InterviewerProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createAdmin();
        $this->createInterviewers();
        $this->createCandidates();

        $this->command->info('');
        $this->command->info('Seeding complete. Test credentials:');
        $this->command->info('  Admin      admin@hiresphere.io / Admin@123!');
        $this->command->info('  Interviewer priya@hiresphere.io / Interview@123!');
        $this->command->info('  Candidate  alice@hiresphere.io / Candidate@123!');
        $this->command->info('Note: passwords must be set in AWS Cognito — these are reference only.');
    }

    private function createAdmin(): void
    {
        User::create([
            'id'          => Str::uuid(),
            'cognito_sub' => 'admin-cognito-sub-000',
            'name'        => 'HireSphere Admin',
            'email'       => 'admin@hiresphere.io',
            'role'        => 'admin',
        ]);
    }

    private function createInterviewers(): void
    {
        $interviewers = [
            [
                'name'     => 'Priya Sharma',
                'email'    => 'priya@hiresphere.io',
                'sub'      => 'interviewer-cognito-sub-001',
                'bio'      => 'Senior Software Engineer at Google with 8 years experience in distributed systems and ML infrastructure.',
                'company'  => 'Google',
                'title'    => 'Senior Software Engineer',
                'domains'  => ['algorithms', 'system-design', 'machine-learning'],
                'types'    => ['technical-coding', 'system-design'],
                'level'    => 'senior',
                'rate'     => 12000,
                'linkedin' => 'https://linkedin.com/in/priya-sharma',
                'github'   => 'https://github.com/priya-sharma',
            ],
            [
                'name'     => 'James Mitchell',
                'email'    => 'james@hiresphere.io',
                'sub'      => 'interviewer-cognito-sub-002',
                'bio'      => 'Staff Engineer at Meta specialising in front-end architecture and React performance at scale.',
                'company'  => 'Meta',
                'title'    => 'Staff Engineer',
                'domains'  => ['frontend', 'react', 'web-performance'],
                'types'    => ['technical-coding', 'portfolio-review'],
                'level'    => 'staff',
                'rate'     => 15000,
                'linkedin' => 'https://linkedin.com/in/james-mitchell',
                'github'   => 'https://github.com/james-mitchell',
            ],
            [
                'name'     => 'Aisha Al-Rashidi',
                'email'    => 'aisha@hiresphere.io',
                'sub'      => 'interviewer-cognito-sub-003',
                'bio'      => 'Principal Engineer at Amazon with deep expertise in AWS cloud architecture and microservices.',
                'company'  => 'Amazon',
                'title'    => 'Principal Engineer',
                'domains'  => ['cloud-architecture', 'microservices', 'devops'],
                'types'    => ['system-design', 'behavioral'],
                'level'    => 'principal',
                'rate'     => 18000,
                'linkedin' => 'https://linkedin.com/in/aisha-al-rashidi',
                'github'   => null,
            ],
            [
                'name'     => 'Ryan Chen',
                'email'    => 'ryan@hiresphere.io',
                'sub'      => 'interviewer-cognito-sub-004',
                'bio'      => 'Research Engineer at OpenAI focusing on LLM fine-tuning, RLHF, and applied ML systems.',
                'company'  => 'OpenAI',
                'title'    => 'Research Engineer',
                'domains'  => ['machine-learning', 'nlp', 'python'],
                'types'    => ['technical-coding', 'system-design'],
                'level'    => 'senior',
                'rate'     => 20000,
                'linkedin' => 'https://linkedin.com/in/ryan-chen',
                'github'   => 'https://github.com/ryan-chen',
            ],
        ];

        foreach ($interviewers as $data) {
            $user = User::create([
                'id'              => Str::uuid(),
                'cognito_sub'     => $data['sub'],
                'name'            => $data['name'],
                'email'           => $data['email'],
                'role'            => 'interviewer',
                'linkedin_url'    => $data['linkedin'],
                'github_url'      => $data['github'],
            ]);

            InterviewerProfile::create([
                'id'               => Str::uuid(),
                'user_id'          => $user->id,
                'bio'              => $data['bio'],
                'company'          => $data['company'],
                'job_title'        => $data['title'],
                'domains'          => $data['domains'],
                'interview_types'  => $data['types'],
                'experience_level' => $data['level'],
                'hourly_rate'      => $data['rate'],
                'is_active'        => true,
                'average_rating'   => round(4.5 + (mt_rand(0, 5) / 10), 1),
                'total_reviews'    => mt_rand(12, 80),
            ]);

            $this->createAvailabilitySlots($user->id);
        }
    }

    private function createAvailabilitySlots(string $userId): void
    {
        $startHours = [9, 11, 14, 16];

        for ($day = 1; $day <= 14; $day++) {
            $date = Carbon::today()->addDays($day);

            if ($date->isWeekend()) {
                continue;
            }

            foreach ($startHours as $hour) {
                AvailabilitySlot::create([
                    'id'           => Str::uuid(),
                    'interviewer_id' => $userId,
                    'start_time'   => $date->copy()->setHour($hour)->setMinute(0)->setSecond(0),
                    'end_time'     => $date->copy()->setHour($hour + 1)->setMinute(0)->setSecond(0),
                    'timezone'     => 'UTC',
                    'status'       => 'available',
                ]);
            }
        }
    }

    private function createCandidates(): void
    {
        $candidates = [
            [
                'name'     => 'Alice Johnson',
                'email'    => 'alice@hiresphere.io',
                'sub'      => 'candidate-cognito-sub-001',
                'skills'   => ['PHP', 'Laravel', 'Vue.js', 'PostgreSQL'],
                'targets'  => ['Google', 'Stripe', 'Shopify'],
                'level'    => 'intermediate',
                'linkedin' => 'https://linkedin.com/in/alice-johnson',
                'github'   => 'https://github.com/alice-johnson',
            ],
            [
                'name'     => 'Marcus Williams',
                'email'    => 'marcus@hiresphere.io',
                'sub'      => 'candidate-cognito-sub-002',
                'skills'   => ['Python', 'FastAPI', 'React', 'AWS'],
                'targets'  => ['Amazon', 'Airbnb', 'Datadog'],
                'level'    => 'junior',
                'linkedin' => 'https://linkedin.com/in/marcus-williams',
                'github'   => 'https://github.com/marcus-williams',
            ],
            [
                'name'     => 'Sofia Nguyen',
                'email'    => 'sofia@hiresphere.io',
                'sub'      => 'candidate-cognito-sub-003',
                'skills'   => ['TypeScript', 'Node.js', 'Kubernetes', 'Go'],
                'targets'  => ['Meta', 'Uber', 'Figma'],
                'level'    => 'senior',
                'linkedin' => 'https://linkedin.com/in/sofia-nguyen',
                'github'   => null,
            ],
        ];

        foreach ($candidates as $data) {
            $user = User::create([
                'id'           => Str::uuid(),
                'cognito_sub'  => $data['sub'],
                'name'         => $data['name'],
                'email'        => $data['email'],
                'role'         => 'candidate',
                'linkedin_url' => $data['linkedin'],
                'github_url'   => $data['github'],
            ]);

            CandidateProfile::create([
                'id'                => Str::uuid(),
                'user_id'           => $user->id,
                'skills'            => $data['skills'],
                'target_companies'  => $data['targets'],
                'preparation_level' => $data['level'],
            ]);
        }
    }
}
