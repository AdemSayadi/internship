<?php

namespace App\Services;

use App\Models\CodeSubmission;
use App\Models\PullRequest;
use App\Models\Review;
use App\Models\PullRequestReview;
use App\Models\PullRequestFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AICodeReviewService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.groq.com/openai/v1';
    private string $model = 'llama3-70b-8192';
    private int $maxRetries = 3;
    private int $retryDelay = 1000; // milliseconds
    private int $timeout = 30; // seconds

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Groq API key is not configured');
        }
    }

    public function reviewCodeSubmission(CodeSubmission $codeSubmission, ?Review $review = null): Review
    {
        if (!$codeSubmission->exists) {
            throw new ModelNotFoundException('Code submission does not exist');
        }

        try {
            // Create review if not provided
            $review = $review ?? $this->createReviewRecord($codeSubmission);

            $startTime = microtime(true);

            $aiResponse = $this->analyzeCodeWithRetry(
                $codeSubmission->code_content,
                $codeSubmission->language,
                $codeSubmission->title
            );

            $this->updateReviewWithResults(
                $review,
                $aiResponse,
                $startTime
            );

            return $review;
        } catch (Exception $e) {
            $this->handleReviewError($review ?? null, $codeSubmission, $e);
            throw $e;
        }
    }

    private function createReviewRecord(CodeSubmission $codeSubmission): Review
    {
        return Review::create([
            'code_submission_id' => $codeSubmission->id,
            'status' => Review::STATUS_PROCESSING,
        ]);
    }

    private function updateReviewWithResults(Review $review, array $aiResponse, float $startTime): void
    {
        $processingTime = microtime(true) - $startTime;

        $review->update([
            'overall_score' => $aiResponse['overall_score'],
            'complexity_score' => $aiResponse['complexity_score'],
            'security_score' => $aiResponse['security_score'],
            'maintainability_score' => $aiResponse['maintainability_score'],
            'bug_count' => $aiResponse['bug_count'],
            'ai_summary' => $aiResponse['summary'],
            'suggestions' => $aiResponse['suggestions'],
            'status' => Review::STATUS_COMPLETED,
            'processing_time' => $processingTime,
        ]);
    }

    private function handleReviewError(?Review $review, CodeSubmission $codeSubmission, Exception $e): void
    {
        Log::error('AI Code Review failed for submission ' . $codeSubmission->id, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($review) {
            $review->update([
                'status' => Review::STATUS_FAILED,
                'ai_summary' => 'Review failed: ' . $e->getMessage()
            ]);
        } else {
            Log::error('Failed to create review record for submission', [
                'submission_id' => $codeSubmission->id
            ]);
        }
    }

    private function analyzeCodeWithRetry(string $code, string $language, string $context = ''): array
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            try {
                return $this->analyzeCode($code, $language, $context);
            } catch (Exception $e) {
                $lastError = $e;
                $attempt++;
                Log::warning("AI API attempt $attempt failed", [
                    'error' => $e->getMessage(),
                    'next_retry_ms' => $this->retryDelay
                ]);

                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        throw $lastError ?? new Exception('Unknown error occurred during AI analysis');
    }

    private function analyzeCode(string $code, string $language, string $context = '', ?string $diff = null): array
    {
        if (empty($code)) {
            throw new \InvalidArgumentException('Code content cannot be empty');
        }

        $prompt = $this->buildPrompt($code, $language, $context, $diff);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry(2, 500)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => $this->buildMessages($prompt),
                'temperature' => 0.1,
                'max_tokens' => 2000,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            $error = $response->json()['error'] ?? $response->body();
            throw new Exception('AI API request failed: ' . json_encode($error));
        }

        $aiContent = $response->json()['choices'][0]['message']['content'];
        $result = json_decode($aiContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid AI response format: ' . json_last_error_msg());
        }

        return $this->validateAndNormalizeResponse($result);
    }

    private function buildMessages(string $prompt): array
    {
        return [
            [
                'role' => 'system',
                'content' => 'You are an expert code reviewer. Analyze code and provide detailed feedback in JSON format.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
    }

    private function buildPrompt(string $code, string $language, string $context, ?string $diff = null): string
    {
        $diffSection = $diff ? "\nChanges (diff):\n{$diff}\n" : '';
        return <<<PROMPT
        Analyze the following {$language} code and provide a comprehensive review.

        Context: {$context}
        {$diffSection}
        Code:
        ```{$language}
        {$code}
        ```

        Provide your analysis in JSON format with these fields:
        - overall_score (1-10)
        - complexity_score (1-10)
        - security_score (1-10)
        - maintainability_score (1-10)
        - bug_count
        - summary
        - feedback
        - suggestions (array)
        - security_issues (array)
        - performance_issues (array)
        - code_quality_issues (array)

        Focus on:
        1. Code security vulnerabilities
        2. Performance issues
        3. Code maintainability and readability
        4. Best practices for {$language}
        5. Potential bugs or logic errors
        6. Code complexity and structure
        PROMPT;
    }

    private function validateAndNormalizeResponse(array $result): array
    {
        $defaults = [
            'overall_score' => 5,
            'complexity_score' => 5,
            'security_score' => 5,
            'maintainability_score' => 5,
            'bug_count' => 0,
            'summary' => 'AI analysis completed',
            'feedback' => '',
            'suggestions' => [],
            'security_issues' => [],
            'performance_issues' => [],
            'code_quality_issues' => [],
        ];

        $normalized = array_merge($defaults, $result);

        // Ensure scores are within bounds
        $normalized['overall_score'] = $this->clampScore($normalized['overall_score']);
        $normalized['complexity_score'] = $this->clampScore($normalized['complexity_score']);
        $normalized['security_score'] = $this->clampScore($normalized['security_score']);
        $normalized['maintainability_score'] = $this->clampScore($normalized['maintainability_score']);
        $normalized['bug_count'] = max(0, (int) $normalized['bug_count']);

        // Ensure arrays have proper structure
        $normalized['suggestions'] = $this->normalizeIssues($normalized['suggestions'], 'suggestion');
        $normalized['security_issues'] = $this->normalizeIssues($normalized['security_issues'], 'security');
        $normalized['performance_issues'] = $this->normalizeIssues($normalized['performance_issues'], 'performance');
        $normalized['code_quality_issues'] = $this->normalizeIssues($normalized['code_quality_issues'], 'quality');

        return $normalized;
    }

    private function clampScore($score): int
    {
        return max(1, min(10, (int) $score));
    }

    private function normalizeIssues(array $issues, string $type): array
    {
        return array_map(function ($issue) use ($type) {
            $defaults = [
                'line' => null,
                'message' => 'No details provided',
                'severity' => 'medium',
            ];

            $normalized = array_merge($defaults, $issue);

            // Type-specific normalizations
            if ($type === 'suggestion') {
                $normalized['type'] = $issue['type'] ?? 'improvement';
            } elseif ($type === 'security') {
                $normalized['recommendation'] = $issue['recommendation'] ?? 'Review and fix';
            } elseif ($type === 'performance') {
                $normalized['impact'] = $issue['impact'] ?? 'medium';
            } elseif ($type === 'quality') {
                $normalized['category'] = $issue['category'] ?? 'best_practices';
            }

            return $normalized;
        }, $issues);
    }
//////////////////////////////////////////////////////////////////////////////////
    /**
     * Review pull request using AI
     */
    public function reviewPullRequest(PullRequest $pullRequest): PullRequestReview
    {
        try {
            $review = PullRequestReview::create([
                'pull_request_id' => $pullRequest->id,
                'review_type' => 'ai_auto',
                'status' => 'pending',
                'reviewed_by' => null, // AI review
            ]);

            // Get all files in the pull request
            $files = $pullRequest->files;
            $codeAnalysis = [];

            foreach ($files as $file) {
                // Skip deleted files and non-code files
                if ($file->isRemoved() || !$this->isCodeFile($file->filename)) {
                    continue;
                }

                // Get file content from GitHub
                $fileContent = $this->getFileContent($pullRequest, $file);

                if ($fileContent) {
                    $analysis = $this->analyzeCode(
                        $fileContent,
                        $file->language ?? $this->detectLanguage($file->filename),
                        $file->filename,
                        $file->patch // Include diff context if available
                    );

                    $codeAnalysis[] = [
                        'file' => $file->filename,
                        'analysis' => $analysis
                    ];
                }
            }

            // Aggregate results
            $aggregatedResults = $this->aggregateResults($codeAnalysis);

            $review->update([
                'status' => 'completed',
                'summary' => $aggregatedResults['summary'],
                'score' => $aggregatedResults['overall_score'],
                'feedback' => $aggregatedResults['feedback'],
                'suggestions' => $aggregatedResults['suggestions'],
                'security_issues' => $aggregatedResults['security_issues'],
                'performance_issues' => $aggregatedResults['performance_issues'],
                'code_quality_issues' => $aggregatedResults['code_quality_issues'],
                'reviewed_at' => now(),
            ]);

            return $review;
        } catch (Exception $e) {
            Log::error('AI Pull Request Review failed for PR ' . $pullRequest->id, [
                'error' => $e->getMessage()
            ]);

            if (isset($review)) {
                $review->update(['status' => 'failed']);
            }

            throw $e;
        }
    }

    /**
     * Main AI analysis method
     */
//    private function analyzeCode(string $code, string $language, string $context = '', string $diff = null): array
//    {
//        $prompt = $this->buildPrompt($code, $language, $context, $diff);
//
//        $response = Http::withHeaders([
//            'Authorization' => 'Bearer ' . $this->apiKey,
//            'Content-Type' => 'application/json',
//        ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
//            'model' => $this->model,
//            'messages' => [
//                [
//                    'role' => 'system',
//                    'content' => 'You are an expert code reviewer. Analyze code and provide detailed feedback in JSON format.'
//                ],
//                [
//                    'role' => 'user',
//                    'content' => $prompt
//                ]
//            ],
//            'temperature' => 0.1,
//            'max_tokens' => 2000,
//        ]);
//
//        if (!$response->successful()) {
//            throw new Exception('AI API request failed: ' . $response->body());
//        }
//
//        $aiContent = $response->json()['choices'][0]['message']['content'];
//
//        // Parse JSON response
//        $result = json_decode($aiContent, true);
//
//        if (!$result) {
//            throw new Exception('Invalid AI response format');
//        }
//
//        return $this->validateAndNormalizeResponse($result);
//    }
//
//    /**
//     * Build comprehensive prompt for AI analysis
//     */
//    private function buildPrompt(string $code, string $language, string $context, string $diff = null): string
//    {
//        $basePrompt = "
//        Analyze the following {$language} code and provide a comprehensive review.
//
//        Context: {$context}
//        " . ($diff ? "\nChanges (diff):\n{$diff}" : "") . "
//
//        Code:
//        ```{$language}
//        {$code}
//        ```
//
//        Please provide your analysis in the following JSON format:
//        {
//            \"overall_score\": (1-10),
//            \"complexity_score\": (1-10),
//            \"security_score\": (1-10),
//            \"maintainability_score\": (1-10),
//            \"bug_count\": (number),
//            \"summary\": \"Brief summary of the code quality\",
//            \"feedback\": \"Detailed feedback about the code\",
//            \"suggestions\": [
//                {
//                    \"line\": (line number or null),
//                    \"type\": \"improvement|bug|security|performance\",
//                    \"message\": \"Specific suggestion\",
//                    \"severity\": \"low|medium|high\"
//                }
//            ],
//            \"security_issues\": [
//                {
//                    \"line\": (line number or null),
//                    \"issue\": \"Description of security issue\",
//                    \"severity\": \"low|medium|high\",
//                    \"recommendation\": \"How to fix it\"
//                }
//            ],
//            \"performance_issues\": [
//                {
//                    \"line\": (line number or null),
//                    \"issue\": \"Performance concern\",
//                    \"impact\": \"low|medium|high\",
//                    \"recommendation\": \"Optimization suggestion\"
//                }
//            ],
//            \"code_quality_issues\": [
//                {
//                    \"line\": (line number or null),
//                    \"issue\": \"Code quality issue\",
//                    \"category\": \"readability|maintainability|best_practices\",
//                    \"recommendation\": \"How to improve\"
//                }
//            ]
//        }
//
//        Focus on:
//        1. Code security vulnerabilities
//        2. Performance issues
//        3. Code maintainability and readability
//        4. Best practices for {$language}
//        5. Potential bugs or logic errors
//        6. Code complexity and structure
//        ";
//
//        return $basePrompt;
//    }
//
//    /**
//     * Validate and normalize AI response
//     */
    private function aggregateResults(array $analyses): array
    {
        if (empty($analyses)) {
            return [
                'summary' => 'No code files found for analysis',
                'overall_score' => 5,
                'feedback' => '',
                'suggestions' => [],
                'security_issues' => [],
                'performance_issues' => [],
                'code_quality_issues' => [],
            ];
        }

        $totalScore = 0;
        $allSuggestions = [];
        $allSecurityIssues = [];
        $allPerformanceIssues = [];
        $allQualityIssues = [];
        $feedbacks = [];

        foreach ($analyses as $analysis) {
            $result = $analysis['analysis'];
            $fileName = $analysis['file'];

            $totalScore += $result['overall_score'];
            $feedbacks[] = "**{$fileName}**: " . $result['feedback'];

            // Add file context to issues
            foreach ($result['suggestions'] as $suggestion) {
                $suggestion['file'] = $fileName;
                $allSuggestions[] = $suggestion;
            }

            foreach ($result['security_issues'] as $issue) {
                $issue['file'] = $fileName;
                $allSecurityIssues[] = $issue;
            }

            foreach ($result['performance_issues'] as $issue) {
                $issue['file'] = $fileName;
                $allPerformanceIssues[] = $issue;
            }

            foreach ($result['code_quality_issues'] as $issue) {
                $issue['file'] = $fileName;
                $allQualityIssues[] = $issue;
            }
        }

        $avgScore = round($totalScore / count($analyses));

        return [
            'summary' => $this->generateSummary($analyses, $avgScore),
            'overall_score' => $avgScore,
            'feedback' => implode("\n\n", $feedbacks),
            'suggestions' => $allSuggestions,
            'security_issues' => $allSecurityIssues,
            'performance_issues' => $allPerformanceIssues,
            'code_quality_issues' => $allQualityIssues,
        ];
    }

    /**
     * Generate summary for multiple files
     */
    private function generateSummary(array $analyses, int $avgScore): string
    {
        $fileCount = count($analyses);
        $totalIssues = 0;

        foreach ($analyses as $analysis) {
            $result = $analysis['analysis'];
            $totalIssues += count($result['suggestions']) +
                count($result['security_issues']) +
                count($result['performance_issues']) +
                count($result['code_quality_issues']);
        }

        return "Analyzed {$fileCount} files with an average score of {$avgScore}/10. Found {$totalIssues} total issues and suggestions.";
    }

    /**
     * Get file content from GitHub
     */
    private function getFileContent(PullRequest $pullRequest, PullRequestFile $file): ?string
    {
        try {
            if (!$file->raw_url) {
                return null;
            }

            // Get GitHub token for the repository
            $token = $pullRequest->repository->user->github_token ?? config('services.github.token');

            $response = Http::withHeaders([
                'Authorization' => 'token ' . $token,
                'Accept' => 'application/vnd.github.v3.raw',
            ])->get($file->raw_url);

            return $response->successful() ? $response->body() : null;
        } catch (Exception $e) {
            Log::warning('Failed to fetch file content', [
                'file' => $file->filename,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if file is a code file
     */
    private function isCodeFile(string $filename): bool
    {
        $codeExtensions = [
            'php', 'js', 'ts', 'py', 'java', 'cpp', 'c', 'cs', 'rb', 'go',
            'rust', 'swift', 'kt', 'scala', 'vue', 'jsx', 'tsx', 'html',
            'css', 'scss', 'sass', 'sql', 'yaml', 'yml', 'json'
        ];

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $codeExtensions);
    }

    /**
     * Detect programming language from filename
     */
    private function detectLanguage(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $languageMap = [
            'php' => 'php',
            'js' => 'javascript',
            'ts' => 'typescript',
            'py' => 'python',
            'java' => 'java',
            'cpp' => 'cpp',
            'c' => 'c',
            'cs' => 'csharp',
            'rb' => 'ruby',
            'go' => 'go',
            'rs' => 'rust',
            'swift' => 'swift',
            'kt' => 'kotlin',
            'scala' => 'scala',
            'vue' => 'vue',
            'jsx' => 'javascript',
            'tsx' => 'typescript',
            'html' => 'html',
            'css' => 'css',
            'scss' => 'scss',
            'sql' => 'sql',
            'yaml' => 'yaml',
            'yml' => 'yaml',
            'json' => 'json',
        ];

        return $languageMap[$extension] ?? 'text';
    }
}
