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

$userModel = new UserModel();
$user = $userModel->getUserById($_SESSION['user']['id']);
$researchData = $userModel->getAllResearchData();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_research'])) {
    $userId = $_SESSION['user']['id'];
    $researchId = $_POST['research_id'];
    $titleOfStudy = $_POST['title_of_study'];
    
    if ($userModel->saveResearchInterest($userId, $titleOfStudy)) {
        $userModel->logActivity($userId, "save", "Saved interest in research: " . $titleOfStudy);
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Research interest saved successfully.'
        ];
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Failed to save research interest.'
        ];
    }
    
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_access'])) {
    $requestedBy = $_SESSION['user']['id'];
    $researchId = $_POST['research_id'];
    $type = $_POST['type'];
    $file = $_POST['file_path'];
    
    if ($userModel->storeAccessRequest($requestedBy, $researchId, $type, $file)) {
        $userModel->logActivity($requestedBy, "request", "Requested access to {$type} file for research ID {$researchId}");
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Access request submitted successfully.'
        ];
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Failed to submit access request. Please try again.'
        ];
    }
    
}
?>

<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<?php if (isset($_SESSION['message'])): ?>
    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" style="position: fixed; top: 1rem; right: 1rem; z-index: 1080;">
        <div class="toast" data-delay="5000">
            <div class="toast-header">
                <strong class="mr-auto">
                    <?php 
                        if ($_SESSION['message']['type'] == 'success') {
                            echo 'Success';
                        } else {
                            echo 'Error';
                        }
                    ?>
                </strong>
                <small class="text-muted">just now</small>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                <?= $_SESSION['message']['text'] ?>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-end align-items-center">
            <div class="search-filters d-flex align-items-center">
                <!-- Year Filter Dropdown -->
                <select id="yearFilter" class="form-select form-select-sm me-2 mx-2" onchange="filterByYear()">
                    <option value="">Filter by Year</option>
                    <?php 
                        $years = array_unique(array_column($researchData, 'school_year'));
                        rsort($years); 
                        foreach ($years as $year) {
                            echo "<option value='" . htmlspecialchars($year) . "'>" . htmlspecialchars($year) . "</option>";
                        }
                    ?>
                </select>
                
                <!-- Clear Filter Button -->
                <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">
                    <i class="bi bi-x-circle-fill me-1"></i> Clear Filters
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="researchTable" class="table">
            <thead>
                <tr>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">#</th>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">S.Y</th>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">Members</th>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">Title</th>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">Adviser</th>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">Specialization</th>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">Manuscript</th>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">Abstract</th>
                    <th class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($researchData as $title): ?>
                    <tr data-year="<?= htmlspecialchars($title['school_year']); ?>">
                    <td class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">#<?= htmlspecialchars($title['id']) ?></td>
                    <td class="text-nowrap text-truncate" style="font-size: 15px; color: #000;"><?= htmlspecialchars($title['school_year']) ?></td>
                    <td class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">
                        <?php 
                            $membersNames = json_decode($title['members_name'], true); 
                            if (is_array($membersNames)) {
                                echo htmlspecialchars(implode(", ", $membersNames));
                            } else {
                                echo htmlspecialchars($title['members_name']);
                            }
                        ?>
                    </td>
                    <td class="text-nowrap text-truncate" style="font-size: 15px; color: #000;"><?= htmlspecialchars($title['title_of_study']) ?></td>
                    <td class="text-nowrap text-truncate" style="font-size: 15px; color: #000;">
                        <?php 
                            $adviser = json_decode($title['adviser'], true);
                            if (is_array($adviser)) {
                                echo htmlspecialchars(implode(", ", $adviser));
                            } else {
                                echo htmlspecialchars($title['adviser']);
                            }
                        ?>
                    </td>
                    <td class="text-nowrap text-truncate" style="font-size: 15px; color: #000;"><?= htmlspecialchars($title['specialization']) ?></td>
                    <td class="text-nowrap text-truncate" style="font-size: 15px;">
                        <?php 
                            $manuscript = json_decode($title['manuscript'], true); 
                            if (is_array($manuscript)) {
                                echo '<ol>';
                                foreach ($manuscript as $item) {
                                    echo '<li><a href="#" class="request-file" data-research-id="' . $title['id'] . '" data-file-type="Manuscript" data-file-path="' . $item . '" style="color: blue; text-decoration: underline;">' . htmlspecialchars(basename($item)) . '</a></li>'; 
                                }
                                echo '</ol>'; 
                            } else {
                                echo htmlspecialchars($title['manuscript']); 
                            }
                        ?>
                    </td>
                    <td class="text-nowrap text-truncate" style="font-size: 15px;">
                        <?php 
                            $abstract = json_decode($title['abstract'], true); 
                            if (is_array($abstract)) {
                                echo '<ol>'; 
                                foreach ($abstract as $item) {
                                    echo '<li><a href="#" class="request-file" data-research-id="' . $title['id'] . '" data-file-type="Abstract" data-file-path="' . $item . '" style="color: blue; text-decoration: underline;">' . htmlspecialchars(basename($item)) . '</a></li>'; 
                                }
                                echo '</ol>'; 
                            } else {
                                echo htmlspecialchars($title['abstract']); 
                            }
                        ?>
                    </td>
                    <td class="text-nowrap text-truncate" style="font-size: 15px;">
                        <form method="POST" action="">
                            <input type="hidden" name="save_research" value="1">
                            <input type="hidden" name="research_id" value="<?= htmlspecialchars($title['id']) ?>">
                            <input type="hidden" name="title_of_study" value="<?= htmlspecialchars($title['title_of_study']) ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                Save
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Request Access Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request File Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="research_id" id="research_id">
                    <input type="hidden" name="type" id="file_type">
                    <input type="hidden" name="file_path" id="file_path">
                    <div class="mb-3">
                        <p id="request_file_type"></p>
                    </div>
                    <div class="mb-3">
                        <p id="request_file_name"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Request (Optional):</label>
                        <textarea class="form-control" name="request_reason" rows="3"></textarea>
                    </div>
                    <button type="submit" name="request_access" class="btn btn-primary">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
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
 `;
 document.head.appendChild(style);


    let table = new DataTable('#researchTable');
   

   document.addEventListener('DOMContentLoaded', function() {
   const requestLinks = document.querySelectorAll('.request-file');
   
   requestLinks.forEach(link => {
       link.addEventListener('click', function(e) {
           e.preventDefault();
           
           const researchId = this.getAttribute('data-research-id');
           const fileType = this.getAttribute('data-file-type');
           const filePath = this.getAttribute('data-file-path');
           const fileName = filePath.split('/').pop();
           
           document.getElementById('research_id').value = researchId;
           document.getElementById('file_type').value = fileType;
           document.getElementById('file_path').value = filePath;
           document.getElementById('request_file_type').textContent = fileType;
           document.getElementById('request_file_name').textContent = fileName;
           
           const modal = new bootstrap.Modal(document.getElementById('requestModal'));
           modal.show();
       });
   });
   
   const requestForm = document.querySelector('form[name="request_access"]');
   if (requestForm) {
       requestForm.addEventListener('submit', function(e) {
           e.preventDefault();

           this.submit();
       });
   }
});

function searchContent() {
   const searchQuery = document.getElementById('searchInput').value.toLowerCase();
   const rows = document.querySelectorAll('#researchTable tbody tr');
   rows.forEach(row => {
       const title = row.cells[2].textContent.toLowerCase();
       const adviser = row.cells[3].textContent.toLowerCase();
       const specialization = row.cells[4].textContent.toLowerCase();
       row.style.display = title.includes(searchQuery) || adviser.includes(searchQuery) || specialization.includes(searchQuery) ? '' : 'none';
   });
}


function filterByYear() {
   const yearFilter = $('#yearFilter').val();
   $('#researchTable').DataTable().column(0).search(yearFilter).draw();
}

function clearFilters() {
   $('#yearFilter').val('');
   $('#specializationFilter').val('');
   $('#searchInput').val('');
   $('#statusFilter').val('');
   $('#researchTable').DataTable().search('').columns().search('').draw();
}

$(document).ready(function(){
   $('.toast').toast('show');
});


</script>