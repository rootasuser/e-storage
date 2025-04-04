<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once realpath(__DIR__ . '/../../../core/Database.php');
require_once realpath(__DIR__ . '/../../../models/UserModel.php');

if (!isset($_SESSION['user'])) {
    header("Location: ../../public/index.php");
    exit;
}

$userModel   = new UserModel();
$user        = $userModel->getUserById($_SESSION['user']['id']);
$userRequests = $userModel->getRequestsByUserId($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = $_POST['request_id'];
    
    if ($userModel->deleteRequest($requestId)) {
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Request cancelled successfully.'
        ];
        $userModel->logActivity($user['id'], 'cancel_request', "User cancelled request with ID: $requestId");
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Failed to cancel request.'
        ];
        $userModel->logActivity($user['id'], 'cancel_request_failed', "User failed to cancel request with ID: $requestId");
    }
    
}

$userRequests = $userModel->getRequestsByUserId($user['id']);
?>

<link href="//cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="//cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>


<div aria-live="polite" aria-atomic="true" style="position: fixed; top: 1rem; right: 1rem; z-index: 1080;">
    <div class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">
                <?php 
                    if (isset($_SESSION['message'])) {
                        echo ($_SESSION['message']['type'] === 'success') ? 'Success' : 'Error';
                    }
                ?>
            </strong>
            <small class="text-muted">just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?= isset($_SESSION['message']) ? $_SESSION['message']['text'] : '' ?>
        </div>
    </div>
</div>

<div class="card border-0 mt-4">
    <div class="card-body">
        <div class="table-responsive">
            <table id="requestsTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th class="d-none" style="font-size: 15px; color: #000;">#</th>
                        <th class="d-none" style="font-size: 15px; color: #000;">Res #</th>
                        <th class="text-nowrap" style="font-size: 15px; color: #000;">File Type</th>
                        <th class="text-nowrap" style="font-size: 15px; color: #000;">File Path</th>
                        <th class="text-nowrap" style="font-size: 15px; color: #000;">Status</th>
                        <th class="text-nowrap" style="font-size: 15px; color: #000;">Created</th>
                        <th class="text-nowrap" style="font-size: 15px; color: #000;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userRequests as $request): ?>
                        <tr>
                            <td class="d-none" style="font-size: 15px; color: #000;"><?= htmlspecialchars($request['id']) ?></td>
                            <td class="d-none" style="font-size: 15px; color: #000;"><?= htmlspecialchars($request['research_id']) ?></td>
                            <td class="text-nowrap" style="font-size: 15px; color: #000;"><?= htmlspecialchars($request['file_type']) ?></td>
                            <td class="text-nowrap" style="font-size: 15px; color: #000;">
                                <?php if (is_array($request['file_path']) && !empty($request['file_path'])): ?>
                                    <ul class="file-list">
                                        <?php foreach ($request['file_path'] as $file): ?>
                                            <li>
                                                <?= htmlspecialchars($file) ?>
                                                <?php if ($request['status'] === 'Approved'): ?>
                                                    <button class="btn btn-sm btn-success download-btn" 
                                                        data-file-path="<?= htmlspecialchars($file) ?>">
                                                        <i class="fas fa-download"></i> Download
                                                    </button>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted">No files available</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap" style="font-size: 15px; color: #000;">
                                <span class="badge <?= ($request['status'] === 'Pending') ? 'bg-warning' : (($request['status'] === 'Approved') ? 'bg-success' : 'bg-danger') ?>">
                                    <?= htmlspecialchars($request['status']) ?>
                                </span>
                            </td>
                            <td class="text-nowrap" style="font-size: 15px; color: #000;"><?= htmlspecialchars($request['created_at']) ?></td>
                            <td class="text-nowrap" style="font-size: 15px; color: #000;">
                                <div class="btn-group">
                                    <form method="POST" action="">
                                        <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
 const style = document.createElement('style');
 style.textContent = `
    .search-filters {
        background-color: #f8f9fa;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .search-filters:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .search-filters select, .search-filters button {
        height: 36px;
        border-radius: 4px;
    }
    .search-filters button {
        white-space: nowrap;
    }
    .btn-group {
        display: inline-flex;
        gap: 0.5rem;
    }
    .file-list {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }
    .file-list li {
        margin-bottom: 0.25rem;
    }
 `;
 document.head.appendChild(style);


let table = new DataTable('#requestsTable');

$(document).ready(function() {
    $('#requestsTable').DataTable({
        language: {
            emptyTable: "No requests found."
        }
    });
    
    <?php if(isset($_SESSION['message'])): ?>
    var toast = new bootstrap.Toast(document.querySelector('.toast'));
    toast.show();
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
});


document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".download-btn").forEach(button => {
        button.addEventListener("click", async function () {
            const filePath = this.getAttribute("data-file-path");
            if (!filePath) {
                alert("File path is not available.");
                return;
            }
            try {
                const decodedPath = decodeURIComponent(filePath);
                let fullUrl = '';
                if (decodedPath.includes('abstracts')) {
                    fullUrl = window.location.origin + '/e-storage/app/views/admin/uploads/abstracts/' + decodedPath.split('/').pop();
                } else if (decodedPath.includes('manuscript')) {
                    fullUrl = window.location.origin + '/e-storageapp/views/admin//uploads/manuscripts/' + decodedPath.split('/').pop();
                } else {
                    fullUrl = window.location.origin + '/' + decodedPath;
                }
                const response = await fetch(fullUrl);
                if (!response.ok) {
                    alert("404 Not Found.");
                    return;
                }
                const blob = await response.blob();
                const filename = decodedPath.split('/').pop();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Err DL file:", error);
                alert("Err DL file.");
            }
        });
    });
    
});



</script>
