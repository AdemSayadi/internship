<?php

namespace App\Services;

use App\Models\CodeSubmission;
use App\Models\PullRequest;
use App\Models\Review;
use App\Models\PullRequestReview;
use App\Models\PullRequestFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class AICodeReviewService
{
    // Model configurations with their capabilities
    private array $modelConfigs = [
        // Primary models (best quality)
        'llama3-70b-8192' => [
            'provider' => 'groq',
            'max_tokens' => 2000,
            'quality' => 'high',
            'speed' => 'slow',
            'cost' => 'high',
            'good_for' => ['complex_analysis', 'security_review', 'architecture_review']
        ],
        'mixtral-8x7b-32768' => [
            'provider' => 'groq',
            'max_tokens' => 2500,
            'quality' => 'high',
            'speed' => 'medium',
            'cost' => 'medium',
            'good_for' => ['code_review', 'bug_detection', 'best_practices']
        ],

        // Fallback models (balanced)
        'llama3-8b-8192' => [
            'provider' => 'groq',
            'max_tokens' => 1500,
            'quality' => 'medium',
            'speed' => 'fast',
            'cost' => 'low',
            'good_for' => ['syntax_check', 'basic_review', 'style_check']
        ],
        'gemma-7b-it' => [
            'provider' => 'groq',
            'max_tokens' => 1200,
            'quality' => 'medium',
            'speed' => 'fast',
            'cost' => 'low',
            'good_for' => ['quick_review', 'formatting', 'simple_bugs']
        ],

//        // External alternatives (if Groq fails)
//        'gpt-3.5-turbo' => [
//            'provider' => 'openai',
//            'max_tokens' => 2000,
//            'quality' => 'high',
//            'speed' => 'fast',
//            'cost' => 'medium',
//            'good_for' => ['comprehensive_review', 'explanation', 'suggestions']
//        ]
    ];

    private string $apiKey;
    private string $groqBaseUrl = 'https://api.groq.com/openai/v1';
    private string $openaiBaseUrl = 'https://api.openai.com/v1';
    private string $primaryModel = 'mixtral-8x7b-32768'; // Better balance for PR reviews
    private array $fallbackModels = ['llama3-8b-8192', 'gemma-7b-it'];
    private int $maxRetries = 3;
    private int $baseRetryDelay = 2000;
    private int $timeout = 60;
    private string $rateLimitCacheKey = 'ai_rate_limit';

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Groq API key is not configured');
        }

        // Override primary model if configured
        $this->primaryModel = config('services.groq.model', $this->primaryModel);
    }

    /**
     * Select the best model based on PR characteristics
     */
    private function selectOptimalModel(PullRequest $pullRequest): string
    {
        $files = $pullRequest->files;
        $totalChanges = $files->sum('changes');
        $fileCount = $files->count();

        // Complex PR - use best model
        if ($totalChanges > 500 || $fileCount > 10 || $this->hasSecuritySensitiveFiles($files)) {
            return 'llama3-70b-8192';
        }

        // Medium complexity - use balanced model
        if ($totalChanges > 100 || $fileCount > 3) {
            return 'mixtral-8x7b-32768';
        }

        // Simple PR - can use faster model
        return 'llama3-8b-8192';
    }

    /**
     * Check if PR contains security-sensitive files
     */
    private function hasSecuritySensitiveFiles($files): bool
    {
        $sensitivePatterns = [
            '/config/', '/security/', '/auth/', '/password/', '/token/',
            '.env', 'docker', 'nginx', 'apache', '/api/', '/routes/'
        ];

        foreach ($files as $file) {
            foreach ($sensitivePatterns as $pattern) {
                if (str_contains(strtolower($file->filename), $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Analyze code with intelligent model selection and fallback
     */
    private function analyzeCodeWithIntelligentFallback(string $code, string $language, string $context = '', ?string $diff = null, ?PullRequest $pullRequest = null): array
    {
        // Select optimal model
        $selectedModel = $pullRequest ?
            $this->selectOptimalModel($pullRequest) :
            $this->primaryModel;

        $attempt = 0;
        $lastError = null;
        $modelsToTry = array_unique([$selectedModel, ...$this->fallbackModels]);

        foreach ($modelsToTry as $modelIndex => $model) {
            try {
                $attempt++;

                Log::info("Attempting AI analysis", [
                    'model' => $model,
                    'attempt' => $attempt,
                    'model_quality' => $this->modelConfigs[$model]['quality'] ?? 'unknown'
                ]);

                $result = $this->analyzeCode($code, $language, $context, $diff, $model);

                // Add model info to result
                $result['model_used'] = $model;
                $result['model_quality'] = $this->modelConfigs[$model]['quality'] ?? 'unknown';

                return $result;

            } catch (Exception $e) {
                $lastError = $e;

                Log::warning("Model {$model} failed", [
                    'error' => $e->getMessage(),
                    'is_rate_limit' => $this->isRateLimitError($e)
                ]);

                // If rate limited, wait before trying next model
                if ($this->isRateLimitError($e)) {
                    $this->handleRateLimitError($e);

                    // If we have more models to try, wait a bit
                    if ($modelIndex < count($modelsToTry) - 1) {
                        sleep(5);
                    }
                }
            }
        }

        throw $lastError ?? new Exception('All AI models failed');
    }

    /**
     * Enhanced prompt for better code review results
     */
    private function buildEnhancedPrompt(string $code, string $language, string $context, ?string $diff = null, string $model = ''): string
    {
        $modelQuality = $this->modelConfigs[$model]['quality'] ?? 'medium';

        // Adjust prompt complexity based on model capability
        $detailLevel = match($modelQuality) {
            'high' => 'comprehensive',
            'medium' => 'balanced',
            'low' => 'focused'
        };

        $diffSection = $diff ? "\nChanges (diff):\n{$diff}\n" : '';

        // Truncate code based on model capacity
        $maxTokens = $this->modelConfigs[$model]['max_tokens'] ?? 1500;
        $maxCodeLength = $maxTokens * 3; // Rough estimate: 1 token ≈ 3 chars

        if (strlen($code) > $maxCodeLength) {
            $code = substr($code, 0, $maxCodeLength) . "\n... [Code truncated to fit model capacity]";
        }

        $basePrompt = <<<PROMPT
        You are an expert code reviewer analyzing {$language} code for a pull request.

        Context: {$context}
        {$diffSection}
        Code:
        ```{$language}
        {$code}
        ```

        PROMPT;

        // Add detailed instructions based on model capability
        if ($detailLevel === 'comprehensive') {
            $basePrompt .= $this->getComprehensiveInstructions($language);
        } elseif ($detailLevel === 'balanced') {
            $basePrompt .= $this->getBalancedInstructions($language);
        } else {
            $basePrompt .= $this->getFocusedInstructions($language);
        }

        return $basePrompt;
    }

    private function getComprehensiveInstructions(string $language): string
    {
        return <<<INSTRUCTIONS

        Provide a comprehensive analysis in JSON format with these fields:
        - overall_score (1-100)
        - complexity_score (1-100)
        - security_score (1-100)
        - maintainability_score (1-100)
        - performance_score (1-100)
        - bug_count (integer)
        - summary (detailed string)
        - feedback (detailed string)
        - suggestions (array of objects with: line, message, severity, type, priority)
        - security_issues (array with: line, issue, severity, recommendation, cwe_id)
        - performance_issues (array with: line, issue, impact, recommendation)
        - code_quality_issues (array with: line, issue, category, severity)
        - architectural_concerns (array)
        - testing_recommendations (array)

        Focus on:
        1. Security vulnerabilities (SQL injection, XSS, authentication bypasses)
        2. Performance bottlenecks and optimization opportunities
        3. Code maintainability and readability
        4. Best practices for {$language}
        5. Potential bugs and logic errors
        6. Code complexity and architectural issues
        7. Testing coverage and quality
        8. Documentation completeness

        Provide specific line numbers where possible and actionable recommendations.
        INSTRUCTIONS;
    }

    private function getBalancedInstructions(string $language): string
    {
        return <<<INSTRUCTIONS

        Provide a balanced analysis in JSON format with these fields:
        - overall_score (1-100)
        - complexity_score (1-100)
        - security_score (1-100)
        - maintainability_score (1-100)
        - bug_count (integer)
        - summary (string)
        - feedback (string)
        - suggestions (array of objects with: line, message, severity, type)
        - security_issues (array with: line, issue, severity, recommendation)
        - performance_issues (array with: line, issue, impact)
        - code_quality_issues (array with: line, issue, category)

        Focus on:
        1. Critical security issues
        2. Major performance problems
        3. Code maintainability
        4. {$language} best practices
        5. Obvious bugs and logic errors
        6. Code structure and readability

        Be concise but thorough.
        INSTRUCTIONS;
    }

    private function getFocusedInstructions(string $language): string
    {
        return <<<INSTRUCTIONS

        Provide a focused analysis in JSON format with these fields:
        - overall_score (1-100)
        - security_score (1-100)
        - bug_count (integer)
        - summary (string)
        - suggestions (array of objects with: message, severity)
        - security_issues (array with: issue, severity)
        - code_quality_issues (array with: issue, severity)

        Focus on:
        1. Critical security vulnerabilities
        2. Obvious bugs and errors
        3. Major {$language} best practice violations
        4. Code readability issues

        Be concise and prioritize the most important issues.
        INSTRUCTIONS;
    }

    private function analyzeCode(string $code, string $language, string $context = '', ?string $diff = null, ?string $model = null): array
    {
        if (empty($code)) {
            throw new \InvalidArgumentException('Code content cannot be empty');
        }

        $useModel = $model ?? $this->primaryModel;
        $modelConfig = $this->modelConfigs[$useModel] ?? $this->modelConfigs[$this->primaryModel];

        $prompt = $this->buildEnhancedPrompt($code, $language, $context, $diff, $useModel);
        $baseUrl = $modelConfig['provider'] === 'openai' ? $this->openaiBaseUrl : $this->groqBaseUrl;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->post($baseUrl . '/chat/completions', [
                'model' => $useModel,
                'messages' => $this->buildMessages($prompt),
                'temperature' => 0.1,
                'max_tokens' => $modelConfig['max_tokens'],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            $statusCode = $response->status();
            $error = $response->json()['error'] ?? $response->body();

            if ($statusCode === 429) {
                throw new Exception('Rate limit exceeded: ' . json_encode($error), 429);
            }

            throw new Exception("AI API request failed (HTTP $statusCode): " . json_encode($error));
        }

        $responseData = $response->json();
        $aiContent = $responseData['choices'][0]['message']['content'] ?? null;

        if (!$aiContent) {
            throw new Exception('Empty response from AI API');
        }

        $result = json_decode($aiContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid AI response format: ' . json_last_error_msg() . '. Response: ' . $aiContent);
        }

        return $this->validateAndNormalizeResponse($result, $modelConfig['quality']);
    }

    // Update the PR review method to use intelligent model selection
    public function reviewPullRequest(PullRequest $pullRequest): PullRequestReview
    {
        try {
            // Check rate limit before proceeding
            if ($this->isRateLimited()) {
                throw new Exception('Rate limit active. Please try again later.');
            }

            $review = PullRequestReview::create([
                'pull_request_id' => $pullRequest->id,
                'review_type' => 'ai_auto',
                'status' => 'pending',
                'reviewed_by' => null,
            ]);

            $files = $pullRequest->files;
            $codeAnalysis = [];

            foreach ($files as $file) {
                if ($file->isRemoved() || !$this->isCodeFile($file->filename)) {
                    continue;
                }

                $fileContent = $this->getFileContent($pullRequest, $file);

                if ($fileContent) {
                    $analysis = $this->analyzeCodeWithIntelligentFallback(
                        $fileContent,
                        $file->language ?? $this->detectLanguage($file->filename),
                        $file->filename,
                        $file->patch,
                        $pullRequest // Pass PR for intelligent model selection
                    );

                    $codeAnalysis[] = [
                        'file' => $file->filename,
                        'analysis' => $analysis
                    ];
                }
            }

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
                'model_info' => $aggregatedResults['model_info'] ?? null,
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

    // Rest of your existing helper methods...
    private function isRateLimitError(Exception $e): bool
    {
        return $e->getCode() === 429 ||
            str_contains($e->getMessage(), 'Rate limit') ||
            str_contains($e->getMessage(), '429');
    }

    private function handleRateLimitError(Exception $e): void
    {
        $waitTime = $this->extractWaitTimeFromError($e->getMessage());
        Cache::put($this->rateLimitCacheKey, true, now()->addMinutes($waitTime));

        Log::warning('Rate limit hit, cached for ' . $waitTime . ' minutes', [
            'error' => $e->getMessage(),
            'wait_time_minutes' => $waitTime
        ]);
    }

    private function extractWaitTimeFromError(string $errorMessage): int
    {
        if (preg_match('/try again in (\d+) minute/i', $errorMessage, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/try again in (\d+) second/i', $errorMessage, $matches)) {
            return max(1, ceil((int) $matches[1] / 60));
        }

        return 5;
    }

    private function isRateLimited(): bool
    {
        return Cache::has($this->rateLimitCacheKey);
    }

    private function buildMessages(string $prompt): array
    {
        return [
            [
                'role' => 'system',
                'content' => 'You are an expert code reviewer with years of experience in software security, performance optimization, and code quality. Analyze code thoroughly and provide actionable feedback in JSON format.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
    }

    private function validateAndNormalizeResponse(array $result, string $modelQuality): array
    {
        $defaults = [
            'overall_score' => 50,
            'complexity_score' => 50,
            'security_score' => 50,
            'maintainability_score' => 50,
            'performance_score' => 50,
            'bug_count' => 0,
            'summary' => 'AI analysis completed',
            'feedback' => '',
            'suggestions' => [],
            'security_issues' => [],
            'performance_issues' => [],
            'code_quality_issues' => [],
        ];

        $normalized = array_merge($defaults, $result);

        // Clamp scores
        foreach (['overall_score', 'complexity_score', 'security_score', 'maintainability_score', 'performance_score'] as $scoreField) {
            if (isset($normalized[$scoreField])) {
                $normalized[$scoreField] = max(1, min(100, (int) $normalized[$scoreField]));
            }
        }

        $normalized['bug_count'] = max(0, (int) $normalized['bug_count']);

        // Normalize issue arrays
        $normalized['suggestions'] = $this->normalizeIssues($normalized['suggestions'], 'suggestion');
        $normalized['security_issues'] = $this->normalizeIssues($normalized['security_issues'], 'security');
        $normalized['performance_issues'] = $this->normalizeIssues($normalized['performance_issues'], 'performance');
        $normalized['code_quality_issues'] = $this->normalizeIssues($normalized['code_quality_issues'], 'quality');

        return $normalized;
    }

    private function normalizeIssues(array $issues, string $type): array
    {
        return array_map(function ($issue) use ($type) {
            if (is_string($issue)) {
                return [
                    'line' => null,
                    'message' => $issue,
                    'severity' => 'medium',
                    'type' => $type === 'suggestion' ? 'improvement' : $type,
                ];
            }

            if (!is_array($issue)) {
                return [
                    'line' => null,
                    'message' => 'Invalid issue format',
                    'severity' => 'medium',
                    'type' => $type,
                ];
            }

            $defaults = [
                'line' => null,
                'message' => 'No details provided',
                'severity' => 'medium',
            ];

            return array_merge($defaults, $issue);
        }, $issues);
    }

    // Keep your existing helper methods for file processing, language detection, etc.
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

    private function detectLanguage(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $languageMap = [
            'php' => 'php', 'js' => 'javascript', 'ts' => 'typescript',
            'py' => 'python', 'java' => 'java', 'cpp' => 'cpp', 'c' => 'c',
            'cs' => 'csharp', 'rb' => 'ruby', 'go' => 'go', 'rs' => 'rust',
            'swift' => 'swift', 'kt' => 'kotlin', 'scala' => 'scala',
            'vue' => 'vue', 'jsx' => 'javascript', 'tsx' => 'typescript',
        ];

        return $languageMap[$extension] ?? 'text';
    }

    private function getFileContent(PullRequest $pullRequest, PullRequestFile $file): ?string
    {
        try {
            if (!$file->raw_url) return null;

            $token = $pullRequest->repository->user->github_token ?? config('services.github.token');
            $response = Http::withHeaders([
                'Authorization' => 'token ' . $token,
                'Accept' => 'application/vnd.github.v3.raw',
            ])->get($file->raw_url);

            return $response->successful() ? $response->body() : null;
        } catch (Exception $e) {
            Log::warning('Failed to fetch file content', ['file' => $file->filename, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function aggregateResults(array $analyses): array
    {
        if (empty($analyses)) {
            return [
                'summary' => 'No code files found for analysis',
                'overall_score' => 50,
                'feedback' => '',
                'suggestions' => [],
                'security_issues' => [],
                'performance_issues' => [],
                'code_quality_issues' => [],
            ];
        }

        $totalScore = 0;
        $modelInfo = [];
        $allSuggestions = $allSecurityIssues = $allPerformanceIssues = $allQualityIssues = [];
        $feedbacks = [];

        foreach ($analyses as $analysis) {
            $result = $analysis['analysis'];
            $fileName = $analysis['file'];

            $totalScore += $result['overall_score'];
            $feedbacks[] = "**{$fileName}**: " . $result['feedback'];

            if (isset($result['model_used'])) {
                $modelInfo[] = $result['model_used'];
            }

            foreach (['suggestions', 'security_issues', 'performance_issues', 'code_quality_issues'] as $issueType) {
                foreach ($result[$issueType] as $issue) {
                    $issue['file'] = $fileName;
                    ${'all' . ucfirst(str_replace('_', '', $issueType))}[] = $issue;
                }
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
            'model_info' => array_unique($modelInfo),
        ];
    }

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

        return "Analyzed {$fileCount} files with an average score of {$avgScore}/100. Found {$totalIssues} total issues and suggestions.";
    }
}
