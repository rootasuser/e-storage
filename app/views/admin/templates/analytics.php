<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once realpath(__DIR__ . '/../../../models/UserModel.php');

if (!isset($_SESSION['user'])) {
    header("Location: ../../public/index.php");
    exit;
}

$userModel = new UserModel();
$user = $userModel->getUserById($_SESSION['user']['id']);


$studentCount = $userModel->countStudents();
$totalResearchTitles = $userModel->countResearchTitles();
$pendingResearch = $userModel->countResearchTitlesByStatus('Pending');
$approvedResearch = $userModel->countResearchTitlesByStatus('Approved');
$rejectedResearch = $userModel->countResearchTitlesByStatus('Rejected');
$manuscriptRequests = $userModel->countRequestsByFileType('Manuscript');
$abstractRequests = $userModel->countRequestsByFileType('Abstract');
$pendingRequests = $userModel->countRequestsByStatuss('Pending');
$approvedRequests = $userModel->countRequestsByStatuss('Approved');
$rejectedRequests = $userModel->countRequestsByStatuss('Rejected');
?>

    <style>
        .dashboard-card {
            margin-bottom: 20px;
        }
    </style>

    <div class="container-fluid px-4">
        <h2 class="text-2xl font-bold mb-6">Dashboard Overview</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Students Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-user-graduate text-blue-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Students</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $studentCount ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Research Titles Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-book-open text-blue-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Total Research Titles</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $totalResearchTitles ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-clock text-yellow-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Pending Approvals</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $pendingResearch ?></p>
                    </div>
                </div>
            </div>

            <!-- Approved Research Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Approved Research</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $approvedResearch ?></p>
                    </div>
                </div>
            </div>

            <!-- Rejected Research Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-times-circle text-red-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Rejected Research</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $rejectedResearch ?></p>
                    </div>
                </div>
            </div>

            <!-- Manuscript Requests Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-file-alt text-blue-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Manuscript Requests</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $manuscriptRequests ?></p>
                    </div>
                </div>
            </div>

            <!-- Abstract Requests Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-file text-blue-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Abstract Requests</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $abstractRequests ?></p>
                    </div>
                </div>
            </div>

            <!-- Pending Requests Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-clock text-yellow-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Pending Requests</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $pendingRequests ?></p>
                    </div>
                </div>
            </div>

            <!-- Approved Requests Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Approved Requests</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $approvedRequests ?></p>
                    </div>
                </div>
            </div>

            <!-- Rejected Requests Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-times-circle text-red-500 text-3xl"></i>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold">Rejected Requests</h3>
                        <p class="text-gray-600 text-2xl font-bold"><?= $rejectedRequests ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
