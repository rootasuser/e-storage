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
$userResearchInterests = $userModel->getResearchInterestsByUserId($user['id']);
?>

<div class="card border-0 mt-4">
    <div class="card-body">
        <h5 class="card-title" style="color: #000;">Research Interests</h5>
        <?php if (!empty($userResearchInterests)): ?>
            <ul class="list-group">
                <?php foreach ($userResearchInterests as $interest): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="color: #000;">
                        <span id="interest-<?= $interest['id'] ?>" style="color: #000;"><?= htmlspecialchars($interest['title_of_study']) ?></span>
                        <div>
                            <small class="text-muted me-3" style="color: #000;"><?= htmlspecialchars($interest['created_at']) ?></small>
                            <button class="btn btn-sm btn-outline-secondary copy-btn" data-interest-id="<?= $interest['id'] ?>">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted">No research interests found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
  const style = document.createElement('style');
        style.textContent = `
            .toast-notification {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background-color: #28a745;
                color: white;
                padding: 15px 25px;
                border-radius: 4px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                z-index: 1090;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
        `;
        document.head.appendChild(style);

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".copy-btn").forEach(button => {
                button.addEventListener("click", function() {
                    const interestId = this.getAttribute("data-interest-id");
                    const interestTitle = document.getElementById(`interest-${interestId}`).textContent;
                    
                    const tempInput = document.createElement("textarea");
                    tempInput.value = interestTitle;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand("copy");
                    document.body.removeChild(tempInput);
                
                    const toast = document.createElement("div");
                    toast.className = "toast-notification";
                    toast.textContent = "Copied to clipboard!";
                    document.body.appendChild(toast);
                    
                    toast.style.opacity = "1";
                    
                    setTimeout(() => {
                        toast.style.opacity = "0";
                        setTimeout(() => {
                            document.body.removeChild(toast);
                        }, 300);
                    }, 3000);
                });
            });
        });
</script>