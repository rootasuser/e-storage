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


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
   

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
                            <div class="text-center mb-4">
                                <?php if ($user['profile_pic']): ?>
                                    <img src="<?= $user['profile_pic'] ? 'data:image/jpeg;base64,' . base64_encode($user['profile_pic']) : $profileImage ?>" alt="Profile Picture" class="profile-pic">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/150" alt="Profile Picture" class="profile-pic">
                                <?php endif; ?>
                                <div class="custom-file mt-2">
                                    <input type="file" name="profile_pic" class="custom-file-input" id="profile_pic" accept="image/*">
                                    <label class="custom-file-label" for="profile_pic">Choose profile picture</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" name="first_name" class="form-control" id="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" name="last_name" class="form-control" id="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const style = document.createElement('style');
 style.textContent = `
    .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
    .profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }
 `;
 document.head.appendChild(style);

    </script>
