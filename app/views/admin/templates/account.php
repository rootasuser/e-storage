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
    $id = $_SESSION['user']['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $profile_pic = $user['profile_pic']; 

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['profile_pic']['tmp_name'];
        $profile_pic = file_get_contents($tmpName);
    }

    if ($userModel->updateUser($id, [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'profile_pic' => $profile_pic
    ])) {
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Profile updated successfully.'
        ];
        $_SESSION['user'] = [
            'id' => $id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'profile_pic' => $profile_pic
        ];
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Failed to update profile.'
        ];
    }

  
}
?>

    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 100px;
        }
        .profile-pic {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }
    </style>

    <div class="container profile-container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?= $_SESSION['message']['type'] ?>">
                                <?= $_SESSION['message']['text'] ?>
                                <?php unset($_SESSION['message']); ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="text-center mb-4" style="display: flex; align-items: center; justify-content: center;">
                                <?php if ($user['profile_pic']): ?>
                                    <img src="<?= $user['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : $profileImage ?>" alt="Profile Picture" class="profile-pic">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/150" alt="Profile Picture" class="profile-pic">
                                <?php endif; ?>
                                <div class="file-input mt-2 mx-2">
                                    <input type="file" name="profile_pic" id="profile_pic" accept="image/*" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                </div>
                            </div>
                            <div class="mb-6">
                                <label for="first_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">First Name</label>
                                <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>
                            <div class="mb-6">
                                <label for="last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last Name</label>
                                <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>
                            <div class="mb-6">
                                <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                                <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                            </div>
                            <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

 