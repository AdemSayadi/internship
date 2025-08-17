<?php

namespace App\Http\Controllers;

use App\Models\CodeSubmission;
use App\Models\PullRequest;
use App\Models\Review;
use App\Models\PullRequestReview;
use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            Log::info('Dashboard stats requested for user: ' . $user->id);

            $stats = [
                'repositories' => $user->repositories()->count(),
                'code_submissions' => $user->codeSubmissions()->count(),
                'pull_requests' => PullRequest::whereHas('repository', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->count(),
                'total_reviews' => $this->calculateTotalReviews($user),
                'completion_rate' => $this->calculateCompletionRate(),
                'avg_overall_score' => $this->calculateAverageScore(),
                'avg_complexity_score' => $this->calculateAverageComplexityScore(),
                'avg_security_score' => $this->calculateAverageSecurityScore(),
                'avg_maintainability_score' => $this->calculateAverageMaintainabilityScore(),
                'total_bugs' => $this->calculateTotalBugs(),
                'monthly_stats' => $this->getMonthlyStats(),
                'latest_activity' => $this->getLatestActivity(),
            ];

            Log::info('Dashboard stats calculated:', $stats);

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in dashboard stats: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard stats: ' . $e->getMessage(),
                'stats' => [
                    'repositories' => 0,
                    'code_submissions' => 0,
                    'pull_requests' => 0,
                    'total_reviews' => 0,
                    'completion_rate' => 0,
                    'avg_overall_score' => 0,
                    'avg_complexity_score' => 0,
                    'avg_security_score' => 0,
                    'avg_maintainability_score' => 0,
                    'total_bugs' => 0,
                    'monthly_stats' => [
                        'code_submissions' => array_fill(0, 12, 0),
                        'pull_requests' => array_fill(0, 12, 0),
                        'reviews' => array_fill(0, 12, 0),
                    ],
                    'latest_activity' => [],
                ]
            ], 500);
        }
    }

    private function calculateTotalReviews($user)
    {
        try {
            $codeReviews = Review::whereHas('codeSubmission', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count();

            $prReviews = PullRequestReview::whereHas('pullRequest.repository', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count();

            return $codeReviews + $prReviews;
        } catch (\Exception $e) {
            Log::error('Error calculating total reviews: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateCompletionRate()
    {
        try {
            $user = Auth::user();
            $totalSubmissions = $user->codeSubmissions()->count();

            if ($totalSubmissions === 0) {
                return 0;
            }

            $totalReviews = Review::whereHas('codeSubmission', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'completed')->count() +
                PullRequestReview::whereHas('pullRequest.repository', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'completed')->count();

            return min(round(($totalReviews / $totalSubmissions) * 100, 2), 100);
        } catch (\Exception $e) {
            Log::error('Error calculating completion rate: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateAverageScore()
    {
        try {
            $user = Auth::user();
            $reviews = Review::whereHas('codeSubmission', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'completed')->pluck('overall_score')->merge(
                PullRequestReview::whereHas('pullRequest.repository', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'completed')->pluck('score')->map(function ($score) {
                    return min($score * 10, 100); // Cap at 100
                })
            )->filter();

            return round($reviews->avg() ?: 0, 2);
        } catch (\Exception $e) {
            Log::error('Error calculating average score: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateAverageComplexityScore()
    {
        try {
            $user = Auth::user();
            $reviews = Review::whereHas('codeSubmission', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'completed')->pluck('complexity_score');

            return round(min($reviews->avg() ?: 0, 100), 2);
        } catch (\Exception $e) {
            Log::error('Error calculating complexity score: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateAverageSecurityScore()
    {
        try {
            $user = Auth::user();
            $reviews = Review::whereHas('codeSubmission', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'completed')->pluck('security_score');

            return round(min($reviews->avg() ?: 0, 100), 2);
        } catch (\Exception $e) {
            Log::error('Error calculating security score: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateAverageMaintainabilityScore()
    {
        try {
            $user = Auth::user();
            $reviews = Review::whereHas('codeSubmission', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'completed')->pluck('maintainability_score');

            return round(min($reviews->avg() ?: 0, 100), 2);
        } catch (\Exception $e) {
            Log::error('Error calculating maintainability score: ' . $e->getMessage());
            return 0;
        }
    }

    private function calculateTotalBugs()
    {
        try {
            $user = Auth::user();
            $reviews = Review::whereHas('codeSubmission', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->where('status', 'completed')->pluck('bug_count');

            return $reviews->sum() ?: 0;
        } catch (\Exception $e) {
            Log::error('Error calculating total bugs: ' . $e->getMessage());
            return 0;
        }
    }

    private function getMonthlyStats()
    {
        try {
            $user = Auth::user();
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $stats = [
                'code_submissions' => array_fill(0, 12, 0),
                'pull_requests' => array_fill(0, 12, 0),
                'reviews' => array_fill(0, 12, 0),
            ];

            $submissions = $user->codeSubmissions()->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', date('Y'))
                ->groupBy('month')->get()->keyBy('month');

            $pullRequests = PullRequest::whereHas('repository', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', date('Y'))
                ->groupBy('month')->get()->keyBy('month');

            $reviews = Review::whereHas('codeSubmission', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', date('Y'))
                ->groupBy('month')->get()->keyBy('month');

            $prReviews = PullRequestReview::whereHas('pullRequest.repository', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->selectRaw('MONTH(reviewed_at) as month, COUNT(*) as count')
                ->whereYear('reviewed_at', date('Y'))
                ->groupBy('month')->get()->keyBy('month');

            foreach ($months as $index => $month) {
                $monthNum = $index + 1;
                $stats['code_submissions'][$index] = $submissions->get($monthNum)?->count ?? 0;
                $stats['pull_requests'][$index] = $pullRequests->get($monthNum)?->count ?? 0;
                $stats['reviews'][$index] = ($reviews->get($monthNum)?->count ?? 0) + ($prReviews->get($monthNum)?->count ?? 0);
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting monthly stats: ' . $e->getMessage());
            return [
                'code_submissions' => array_fill(0, 12, 0),
                'pull_requests' => array_fill(0, 12, 0),
                'reviews' => array_fill(0, 12, 0),
            ];
        }
    }

    private function getLatestActivity()
    {
        try {
            $user = Auth::user();
            $activities = collect();

            $latestSubmission = $user->codeSubmissions()->latest()->first();
            if ($latestSubmission) {
                $activities->push(['type' => 'Submission', 'title' => $latestSubmission->title, 'created_at' => $latestSubmission->created_at]);
            }

            $latestPullRequest = PullRequest::whereHas('repository', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->latest()->first();
            if ($latestPullRequest) {
                $activities->push(['type' => 'Pull Request', 'title' => $latestPullRequest->title, 'created_at' => $latestPullRequest->created_at]);
            }

            $latestReview = Review::whereHas('codeSubmission', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->latest()->first();
            if ($latestReview) {
                $activities->push(['type' => 'Review', 'title' => $latestReview->codeSubmission->title, 'created_at' => $latestReview->created_at]);
            }

            $latestPRReview = PullRequestReview::whereHas('pullRequest.repository', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->latest()->first();
            if ($latestPRReview) {
                $activities->push(['type' => 'Pull Request Review', 'title' => $latestPRReview->pullRequest->title, 'created_at' => $latestPRReview->reviewed_at]);
            }

            return $activities->sortByDesc('created_at')->take(5)->values()->all();
        } catch (\Exception $e) {
            Log::error('Error getting latest activity: ' . $e->getMessage());
            return [];
        }
    }
}
