// Mock Data
const mockRepositories = [
    { id: 1, name: 'Project Alpha', url: 'https://github.com/user/project-alpha', provider: 'github', created_at: '2025-07-01' },
    { id: 2, name: 'Project Beta', url: null, provider: 'manual', created_at: '2025-07-15' },
];

const mockSubmissions = [
    { id: 1, repository_id: 1, title: 'Feature X', language: 'javascript', code_content: 'console.log("Hello World");', file_path: 'src/feature-x.js', created_at: '2025-07-02', reviews: [] },
    { id: 2, repository_id: 1, title: 'Bug Fix', language: 'python', code_content: 'def fix_bug():\n  return True', file_path: null, created_at: '2025-07-03', reviews: [] },
];

const mockReviews = [
    {
        id: 1,
        code_submission_id: 1,
        status: 'completed',
        overall_score: 85,
        complexity_score: 80,
        security_score: 90,
        maintainability_score: 85,
        bug_count: 2,
        ai_summary: 'Good code structure, but consider reducing complexity in loops.',
        suggestions: ['Use array methods instead of for loops', 'Add input validation'],
        created_at: '2025-07-02',
    },
];

const mockNotifications = [
    { id: 1, message: 'New review for Feature X', read: false, created_at: '2025-07-02', review: { code_submission_id: 1 } },
    { id: 2, message: 'Review updated for Bug Fix', read: true, created_at: '2025-07-03', review: { code_submission_id: 2 } },
];
