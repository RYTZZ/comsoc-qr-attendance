<?php

namespace App\Services;

class ProfileMetadataService
{
    /**
     * Get all structured skill categories and their items.
     *
     * @return array<string, array<int, string>>
     */
    public static function getSkillCategories(): array
    {
        return [
            'Programming Languages' => [
                'Python', 'Java', 'JavaScript', 'TypeScript', 'PHP', 'C', 'C++', 'C#', 
                'Dart', 'Kotlin', 'Swift', 'Go', 'Rust', 'SQL', 'HTML', 'CSS',
            ],
            'Frameworks & Libraries' => [
                'Laravel', 'React', 'Next.js', 'Vue.js', 'Angular', 'Node.js', 'Express.js', 
                'Django', 'Flask', 'FastAPI', 'Spring Boot', '.NET', 'Flutter', 'React Native', 
                'Tailwind CSS', 'Bootstrap', 'Livewire',
            ],
            'Databases & Data Management' => [
                'MySQL', 'PostgreSQL', 'SQLite', 'MongoDB', 'Firebase', 'Redis', 
                'Supabase', 'Oracle Database', 'Microsoft SQL Server',
            ],
            'Development Tools & Platforms' => [
                'Git', 'GitHub', 'GitLab', 'Docker', 'Linux', 'Postman', 'Figma', 
                'Visual Studio Code', 'Android Studio', 'Visual Studio', 'Vercel', 'Render', 'Cloudflare',
            ],
            'Cloud & DevOps' => [
                'AWS', 'Microsoft Azure', 'Google Cloud', 'CI/CD', 'Infrastructure as Code', 
                'Containerization', 'Deployment Automation', 'Server Administration',
            ],
            'Design & Frontend' => [
                'UI/UX Design', 'Responsive Web Design', 'Wireframing', 'Prototyping', 
                'Design Systems', 'Accessibility', 'Graphic Design',
            ],
            'Software Engineering' => [
                'API Development', 'REST APIs', 'Software Architecture', 'Software Testing', 
                'Quality Assurance', 'Version Control', 'Database Design', 'System Analysis and Design',
            ],
            'Networking & Cybersecurity' => [
                'Computer Networking', 'Network Administration', 'Ethical Hacking', 
                'Penetration Testing', 'Information Security', 'Digital Forensics', 'Network Troubleshooting',
            ],
            'Data & Emerging Technologies' => [
                'Data Analytics', 'Data Science', 'Artificial Intelligence', 'Machine Learning', 
                'Generative AI', 'Internet of Things (IoT)', 'Robotics', 'Embedded Systems', 'Automation',
            ],
            'IT Operations & Professional Skills' => [
                'Technical Support', 'System Administration', 'IT Project Management', 
                'Requirements Analysis', 'Technical Documentation', 'Troubleshooting',
            ],
        ];
    }

    /**
     * Get all structured interest categories and their items.
     *
     * @return array<string, array<int, string>>
     */
    public static function getInterestCategories(): array
    {
        return [
            'Software Development' => [
                'Web Development', 'Mobile App Development', 'Software Engineering', 
                'Full-Stack Development', 'Frontend Development', 'Backend Development', 
                'Game Development', 'Open-Source Development', 'Software Architecture',
            ],
            'AI & Emerging Technologies' => [
                'Artificial Intelligence', 'Machine Learning', 'Generative AI', 
                'Data Science', 'Data Analytics', 'Cloud Computing', 'Blockchain', 
                'Internet of Things', 'Robotics', 'Automation', 'Augmented Reality', 'Virtual Reality',
            ],
            'Design & Creativity' => [
                'UI/UX Design', 'Graphic Design', 'Digital Illustration', 'Animation', 
                'Video Editing', 'Motion Graphics', 'Product Design', 'Creative Coding',
            ],
            'IT Infrastructure & Security' => [
                'Networking', 'Ethical Hacking', 'Cybersecurity', 'Information Security', 
                'Cloud Infrastructure', 'DevOps', 'Database Administration', 
                'System Administration', 'IT Support', 'Digital Forensics',
            ],
            'Career & Entrepreneurship' => [
                'Freelancing', 'Startups', 'Entrepreneurship', 'Tech Careers', 
                'Internships', 'Remote Work', 'Portfolio Building', 
                'Technical Interview Preparation', 'Certifications', 
                'Professional Networking', 'Leadership', 'Project Management',
            ],
            'Community & Learning' => [
                'Programming Competitions', 'Hackathons', 'Ideathons', 'Tech Workshops', 
                'Research and Innovation', 'Open-Source Contributions', 'Mentorship', 
                'Peer Learning', 'Tech Events', 'Community Building', 'Competitive Programming',
            ],
        ];
    }

    /**
     * Normalize tags/items (trims whitespace, deduplicates case-insensitively while preserving original capitalization).
     *
     * @param array<int, string>|string|null $items
     * @param int $maxItems
     * @return array<int, string>
     */
    public static function normalizeList(mixed $items, int $maxItems = 50): array
    {
        if (is_string($items)) {
            // Split comma-separated string if provided
            $items = array_map('trim', explode(',', $items));
        }

        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        $seen = [];

        foreach ($items as $item) {
            if (!is_string($item)) {
                continue;
            }
            $trimmed = trim(preg_replace('/\s+/', ' ', $item));
            if ($trimmed === '' || strlen($trimmed) > 80) {
                continue;
            }
            $lower = mb_strtolower($trimmed);
            if (!isset($seen[$lower])) {
                $seen[$lower] = true;
                $normalized[] = $trimmed;
            }
            if (count($normalized) >= $maxItems) {
                break;
            }
        }

        return $normalized;
    }
}
