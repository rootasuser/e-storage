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

$userLogs = $userModel->getAllActivityLogs();
?>

    <style>
        .action-btn {
            margin-right: 5px;
        }
    </style>

    <div class="container-fluid px-4" style="margin-top: 100px;">
 
        <div class="row">
            <div class="col-md-12">
                <div class="card border p-4">
              
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">User ID</th>
                                    <th class="px-4 py-3">Activity Type</th>
                                    <th class="px-4 py-3">Activity Details</th>
                                    <th class="px-4 py-3">Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userLogs as $log): ?>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-4 py-3">#<?= htmlspecialchars($log['id']) ?></td>
                                        <td class="px-4 py-3">#<?= htmlspecialchars($log['user_id']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($log['activity_type']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($log['activity_details']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($log['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
        });
    </script>
