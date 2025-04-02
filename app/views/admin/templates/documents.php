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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_id'])) {
        $requestId = $_POST['approve_id'];
        if ($userModel->approveRequest($requestId)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Request approved successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to approve request.'
            ];
        }
    } elseif (isset($_POST['reject_id'])) {
        $requestId = $_POST['reject_id'];
        if ($userModel->rejectRequest($requestId)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Request rejected successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to reject request.'
            ];
        }
    } elseif (isset($_POST['delete_id'])) {
        $requestId = $_POST['delete_id'];
        if ($userModel->deleteRequestStudent($requestId)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Request deleted successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to delete request.'
            ];
        }
    }

  
}


$userRequests = $userModel->getAllRequests();
?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .action-btn {
            margin-right: 5px;
        }
    </style>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .action-btn {
            margin-right: 5px;
        }
    </style>

    <div class="container-fluid px-4" style="margin-top: 100px;">
 
        <div class="row">
            <div class="col-md-12">
                <div class="card border p-4">
                    <div class="mb-4">
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">RES ID</th>
                                    <th class="px-4 py-3">File Type</th>
                                    <th class="px-4 py-3">File Path</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Created At</th>
                                    <th class="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userRequests as $request): ?>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-4 py-3">#<?= htmlspecialchars($request['id']) ?></td>
                                        <td class="px-4 py-3">#<?= htmlspecialchars($request['research_id']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($request['file_type']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($request['file_path']) ?></td>
                                        <td class="px-4 py-3">
                                            <span class="badge <?= ($request['status'] === 'Pending') ? 'bg-warning' : (($request['status'] === 'Approved') ? 'bg-success' : 'bg-danger') ?>">
                                                <?= htmlspecialchars($request['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($request['created_at']) ?></td>
                                        <td class="px-4 py-3">
                                            <form method="POST" action="">
                                                <input type="hidden" name="approve_id" value="<?= $request['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success action-btn" style="color: green;">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="">
                                                <input type="hidden" name="reject_id" value="<?= $request['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger action-btn" style="color: yellowgreen;">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                            <form method="POST" action="">
                                                <input type="hidden" name="delete_id" value="<?= $request['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger action-btn" style="color: red;">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


