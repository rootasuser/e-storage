<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once realpath(__DIR__ . '/../../../models/UserModel.php');
require_once realpath(__DIR__ . '/../../../core/ok.php');
$userModel = new UserModel();

if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    $userId = $_GET['id'];
    if ($userModel->updateUserStatus($userId, 'approved')) {
        $_SESSION['success'] = 'User approved successfully.';
    } else {
        $_SESSION['error'] = 'Failed to approve user.';
    }
 
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $userId = $_GET['id'];
    if ($userModel->deleteUser($userId)) {
        $_SESSION['success'] = 'User deleted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to delete user.';
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $userId = $_POST['user_id'];
    $data = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'middle_name' => $_POST['middle_name'],
        'email' => $_POST['email'],
        'role' => $_POST['role'],
        'status' => $_POST['status']
    ];

    $result = $userModel->editUser($userId, $data);

    if ($result['status'] === 'success') {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$searchTerm = isset($_POST['search_term']) ? trim($_POST['search_term']) : ($_SESSION['search_term'] ?? '');
if (isset($_POST['search_term'])) {
    $_SESSION['search_term'] = $searchTerm;
}

if (!empty($searchTerm)) {
    $users = $userModel->searchUsers($searchTerm);
    $totalUsers = count($users);
} else {
    $users = $userModel->getAllUsers();
    $totalUsers = $userModel->getUserCount();
}

$usersPerPage = 10;
$totalPages = max(1, ceil($totalUsers / $usersPerPage));
$currentPage = isset($_GET['page_number']) && is_numeric($_GET['page_number']) ? (int)$_GET['page_number'] : 1;
$currentPage = max(1, min($currentPage, $totalPages));
$offset = ($currentPage - 1) * $usersPerPage;
if (!empty($searchTerm)) {
    $users = array_slice($users, $offset, $usersPerPage);
} else {
    $users = $userModel->getUsersByPage($offset, $usersPerPage);
}

function getProfilePic($profilePic) {
    return !empty($profilePic) ? 'data:image/png;base64,' . base64_encode($profilePic) : 'default-avatar.png';
}
?>

<!-- Search Form -->
<form method="post" class="mb-4 flex items-center" style="margin-top: 100px;">
    <div class="relative flex-grow">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <input type="text" name="search_term" value="<?= htmlspecialchars($searchTerm ?? ''); ?>" class="block p-2 pl-10 w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search users...">
    </div>
    <button type="submit" class="px-4 py-2 ml-2 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-800 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
        <i class="fas fa-search"></i>
    </button>
</form>

<!-- Success/Error Alerts -->
<?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?= $_SESSION["success"]; ?>',
        });
        <?php unset($_SESSION['success']); ?>
    </script>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '<?= $_SESSION["error"]; ?>',
        });
        <?php unset($_SESSION['error']); ?>
    </script>
<?php endif; ?>

<!-- Users Table -->
<div class="relative overflow-x-auto shadow-md sm:rounded-lg" style="margin-top: 20px;">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="p-4 whitespace-nowrap"><?php echo $id_name; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_name; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_gender; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_role; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_username; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_email; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_status; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_created; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_profile; ?></th>
                <th class="px-6 py-3 whitespace-nowrap"><?php echo $tbl_actions; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="10" class="p-4 text-center">No users found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="p-4 whitespace-nowrap">#<?= htmlspecialchars($user['id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['gender']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['role']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['username']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['status']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($user['created_at']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($user['role'] === 'Student' && !empty($user['profile_pic'])): ?>
                                <img src="<?= getProfilePic($user['profile_pic']); ?>" alt="Profile Pic" width="30" height="30" style="border-radius:50%;">
                            <?php else: ?>
                                - 
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="?page=users&action=approve&id=<?= $user['id']; ?>" class="text-green-600 hover:text-green-900">Approve</a> |
                            <a href="#" class="text-blue-600 hover:text-blue-900" onclick="openEditModal(<?= htmlspecialchars(json_encode($user)); ?>)">Edit</a> |
                            <a href="?page=users&action=delete&id=<?= $user['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<nav class="flex items-center justify-between pt-4" aria-label="Table navigation">
    <div class="flex items-center">
        <p class="text-sm text-gray-700 dark:text-gray-400">
            Showing <span class="font-semibold"><?= count($users) ?></span> of <span class="font-semibold"><?= $totalUsers ?></span> entries
        </p>
    </div>
    <ul class="inline-flex items-center -space-x-px">
        <li>
            <a href="dashboard.php?page=users&page_number=<?= $currentPage - 1; ?>&search_term=<?= urlencode($searchTerm ?? ''); ?>"
               class="block px-3 py-2 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 <?= $currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''; ?>">
                <span class="sr-only">Previous</span>
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li>
                <?php if ($i == $currentPage): ?>
                    <span class="px-3 py-2 leading-tight text-blue-600 bg-blue-50 border border-gray-300"><?= $i; ?></span>
                <?php else: ?>
                    <a href="dashboard.php?page=users&page_number=<?= $i; ?>&search_term=<?= urlencode($searchTerm ?? ''); ?>"
                       class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700"><?= $i; ?></a>
                <?php endif; ?>
            </li>
        <?php endfor; ?>
        <li>
            <a href="dashboard.php?page=users&page_number=<?= $currentPage + 1; ?>&search_term=<?= urlencode($searchTerm ?? ''); ?>"
               class="block px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 <?= $currentPage >= $totalPages ? 'cursor-not-allowed opacity-50' : ''; ?>">
                <span class="sr-only">Next</span>
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>


<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 hidden bg-gray-600 bg-opacity-50 flex justify-center items-center" style="z-index: 99999;">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6 relative">
        <div class="border-b pb-2">
            <h2 class="text-lg font-semibold">Edit User</h2>
        </div>
        <div class="mt-4">
            <form id="editUserForm" method="post" action="<?= $_SERVER['PHP_SELF']; ?>?page=users">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="editUserId">

                <label class="block mb-2">First Name</label>
                <input type="text" id="editFirstName" name="first_name" class="w-full p-2 border rounded">

                <label class="block mt-2 mb-2">Last Name</label>
                <input type="text" id="editLastName" name="last_name" class="w-full p-2 border rounded">

                <label class="block mt-2 mb-2">Middle Name</label>
                <input type="text" id="editMiddleName" name="middle_name" class="w-full p-2 border rounded">

                <label class="block mt-2 mb-2">Email</label>
                <input type="email" id="editEmail" name="email" class="w-full p-2 border rounded">

                <label class="block mt-2 mb-2">Role</label>
                <select id="editRole" name="role" class="w-full p-2 border rounded">
                    <option value="Student">Student</option>
                    <option value="Admin">Admin</option>
                </select>

                <label class="block mt-2 mb-2">Status</label>
                <select id="editStatus" name="status" class="w-full p-2 border rounded">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="inactive">Inactive</option>
                </select>

                <div class="flex justify-end mt-4">
                    <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded ml-2">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById("editUserId").value = user.id;
    document.getElementById("editFirstName").value = user.first_name;
    document.getElementById("editLastName").value = user.last_name;
    document.getElementById("editMiddleName").value = user.middle_name;
    document.getElementById("editEmail").value = user.email;
    document.getElementById("editRole").value = user.role;
    document.getElementById("editStatus").value = user.status;
    document.getElementById("editUserModal").classList.remove("hidden");
}

function closeModal() {
    document.getElementById("editUserModal").classList.add("hidden");
}
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editUserForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
    }
});
</script>
