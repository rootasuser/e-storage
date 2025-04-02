<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once realpath(__DIR__ . '/../../../core/Database.php');
require_once realpath(__DIR__ . '/../../../core/ok.php');
/**
 * Normalize the file array to ensure that the 'name', 'tmp_name', etc. values are arrays.
 *
 * @param array $file The $_FILES array element.
 * @return array The normalized file array.
 */
function normalizeFiles($file) {
    if (!is_array($file['name'])) {
        return [
            'name'     => [$file['name']],
            'type'     => [$file['type']],
            'tmp_name' => [$file['tmp_name']],
            'error'    => [$file['error']],
            'size'     => [$file['size']]
        ];
    }
    return $file;
}

class ResearchTitleModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAllResearchTitles() {
        return $this->conn->query("SELECT * FROM research_titles")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchResearchTitles($searchTerm) {
        $stmt = $this->conn->prepare("SELECT * FROM research_titles WHERE title_of_study LIKE ? OR school_year LIKE ?");
        $search = "%{$searchTerm}%";
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResearchCount() {
        $result = $this->conn->query("SELECT COUNT(*) AS count FROM research_titles")->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['count'] : 0;
    }

    public function approveResearch($id) {
        $stmt = $this->conn->prepare("UPDATE research_titles SET status = 'Approved' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteResearch($id) {
        $stmt = $this->conn->prepare("DELETE FROM research_titles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function addResearch($data) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM research_titles WHERE title_of_study = ?");
            $stmt->execute([$data['title_of_study']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("Research title already exists.");
            }
    
            $this->conn->beginTransaction();
            $manuscriptDir = 'uploads/manuscripts/';
            $abstractDir = 'uploads/abstracts/';
    
            if (!is_dir($manuscriptDir)) {
                mkdir($manuscriptDir, 0777, true);
            }
            if (!is_dir($abstractDir)) {
                mkdir($abstractDir, 0777, true);
            }
        
            $manuscriptPaths = [];
            $abstractPaths = [];
            $manuscriptFiles = normalizeFiles($_FILES['manuscript']);
            $abstractFiles   = normalizeFiles($_FILES['abstract']);
            if (!empty($manuscriptFiles['name'][0])) {
                foreach ($manuscriptFiles['tmp_name'] as $key => $tmp_name) {
                    $fileName = basename($manuscriptFiles['name'][$key]);
                    $filePath = $manuscriptDir . $fileName;
                    
                    if (move_uploaded_file($tmp_name, $filePath)) {
                        $manuscriptPaths[] = $filePath;
                    } else {
                        throw new Exception("Failed to upload manuscript: " . $fileName);
                    }
                }
            }
        
            if (!empty($abstractFiles['name'][0])) {
                foreach ($abstractFiles['tmp_name'] as $key => $tmp_name) {
                    $fileName = basename($abstractFiles['name'][$key]);
                    $filePath = $abstractDir . $fileName;
                    
                    if (move_uploaded_file($tmp_name, $filePath)) {
                        $abstractPaths[] = $filePath;
                    } else {
                        throw new Exception("Failed to upload abstract: " . $fileName);
                    }
                }
            }
    
            $stmt = $this->conn->prepare("INSERT INTO research_titles 
                (school_year, members_name, title_of_study, adviser, manuscript, abstract, status, specialization, special_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
            $result = $stmt->execute([
                $data['school_year'],
                json_encode($data['members_name']),
                $data['title_of_study'],
                json_encode($data['adviser']),
                json_encode($manuscriptPaths),  
                json_encode($abstractPaths),   
                $data['status'],
                $data['specialization'],
                $data['special_order']
            ]);
        
            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Err in addResearch: " . $e->getMessage());
            return false;
        }
    }

    public function getResearchById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM research_titles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateResearch($id, $data) {
        try {
            $this->conn->beginTransaction();
            $existingResearch = $this->getResearchById($id);
            $manuscriptPaths = !empty($data['manuscript']) ? $data['manuscript'] : json_decode($existingResearch['manuscript'], true);
            $abstractPaths = !empty($data['abstract']) ? $data['abstract'] : json_decode($existingResearch['abstract'], true);
            $stmt = $this->conn->prepare("UPDATE research_titles SET 
                school_year = ?, 
                members_name = ?, 
                title_of_study = ?, 
                adviser = ?, 
                manuscript = ?, 
                abstract = ?, 
                status = ?,
                specialization = ?,
                special_order = ? 
                WHERE id = ?");
    
            $result = $stmt->execute([
                $data['school_year'],
                json_encode($data['members_name']),
                $data['title_of_study'],
                json_encode($data['adviser']),
                json_encode($manuscriptPaths),
                json_encode($abstractPaths),
                $data['status'],
                $data['specialization'],
                $data['special_order'],
                $id
            ]);
    
            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Err in updateResearch: " . $e->getMessage());
            return false;
        }
    }
    
    public function getResearchFiles($id) {
        $stmt = $this->conn->prepare("SELECT manuscript, abstract FROM research_titles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$model = new ResearchTitleModel();
$searchTerm = isset($_POST['search_term']) ? trim($_POST['search_term']) : ($_SESSION['search_term'] ?? '');

if (isset($_POST['search_term'])) {
    $_SESSION['search_term'] = $searchTerm;
}

if (!empty($searchTerm)) {
    $researchTitles = $model->searchResearchTitles($searchTerm);
} else {
    $researchTitles = $model->getAllResearchTitles();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $data = [
            'school_year'   => $_POST['school_year'],
            'members_name'  => $_POST['members_name'],
            'title_of_study'=> $_POST['title_of_study'],
            'adviser'       => $_POST['adviser'],
            'status'        => $_POST['status'],
            'specialization'      => $_POST['specialization'],
            'special_order' => $_POST['special_order']
        ];
        
        $_SESSION['message'] = $model->addResearch($data) ? 
            ['type' => 'success', 'text' => 'Research added successfully!'] : 
            ['type' => 'error', 'text' => 'Failed to add research!'];
    } elseif (isset($_POST['delete'])) {
        $_SESSION['message'] = $model->deleteResearch($_POST['id']) ? 
            ['type' => 'success', 'text' => 'Research deleted successfully!'] : 
            ['type' => 'error', 'text' => 'Failed to delete research!'];
    } elseif (isset($_POST['approve'])) {
        $_SESSION['message'] = $model->approveResearch($_POST['id']) ? 
            ['type' => 'success', 'text' => 'Research approved successfully!'] : 
            ['type' => 'error', 'text' => 'Failed to approve research!'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_type'], $_POST['download_id'])) {
    $type = $_POST['download_type'];
    $id = (int)$_POST['download_id'];

    $fileData = $model->getResearchFiles($id);
    if (!$fileData) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Research not found!'];
    }

    $files = json_decode($fileData[$type], true);
    if (empty($files) || !isset($files[0]) || !file_exists($files[0])) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'File not found!'];
    }
    
    $filePath = $files[0];
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} elseif (isset($_POST['edit'])) {
    $manuscriptPaths = [];
    $manuscriptFiles = normalizeFiles($_FILES['manuscript']);
    if (!empty($manuscriptFiles['name'][0])) {
        $manuscriptDir = 'uploads/manuscripts/';
        if (!is_dir($manuscriptDir)) {
            mkdir($manuscriptDir, 0777, true);
        }
        foreach ($manuscriptFiles['tmp_name'] as $key => $tmp_name) {
            $fileName = basename($manuscriptFiles['name'][$key]);
            $filePath = $manuscriptDir . $fileName;
            if (move_uploaded_file($tmp_name, $filePath)) {
                $manuscriptPaths[] = $filePath;
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to upload manuscript: " . $fileName];
            }
        }
    }

    $abstractPaths = [];
    $abstractFiles = normalizeFiles($_FILES['abstract']);
    if (!empty($abstractFiles['name'][0])) {
        $abstractDir = 'uploads/abstracts/';
        if (!is_dir($abstractDir)) {
            mkdir($abstractDir, 0777, true);
        }
        foreach ($abstractFiles['tmp_name'] as $key => $tmp_name) {
            $fileName = basename($abstractFiles['name'][$key]);
            $filePath = $abstractDir . $fileName;
            if (move_uploaded_file($tmp_name, $filePath)) {
                $abstractPaths[] = $filePath;
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to upload abstract: " . $fileName];
            }
        }
    }

    $data = [
        'school_year'   => $_POST['school_year'],
        'members_name'  => $_POST['members_name'],
        'title_of_study'=> $_POST['title_of_study'],
        'adviser'       => $_POST['adviser'],
        'manuscript'    => $manuscriptPaths,
        'abstract'      => $abstractPaths,
        'status'        => $_POST['status'],
        'specialization'      => $_POST['specialization'],
        'special_order' => $_POST['special_order']
    ];

    $_SESSION['message'] = $model->updateResearch($_POST['id'], $data) ? 
        ['type' => 'success', 'text' => 'Research updated successfully!'] : 
        ['type' => 'error', 'text' => 'Failed to update research!'];
}
?>

<div class="container mx-auto px-4 py-8" style="margin-top: 10px;">
    <!-- Display Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message']['type'] ?>">
            <?= $_SESSION['message']['text'] ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Button to Show the Form -->
    <div class="flex justify-end p-4 mt-10">
        <button class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" id="showFormButton">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <!-- Search Form -->
    <form method="POST" class="mb-4">
        <div class="flex items-center">
            <input type="text" name="search_term" placeholder="Search research titles..." class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-25 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center ml-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>

    <!-- Specialization Cards -->
    <div class="flex flex-wrap gap-4 mb-6">
        <?php
        $groupedResearch = [];
        foreach ($researchTitles as $research) {
            $groupedResearch[$research['specialization']][] = $research;
        }
        ?>
        <?php foreach ($groupedResearch as $specialization => $records): ?>
            <div class="bg-white border rounded-lg p-4 cursor-pointer hover:shadow-md transform hover:scale-105 transition duration-300 ease-in-out"
                 onclick="showSpecializationTable('<?= htmlspecialchars($specialization) ?>')">
                <h3 class="text-lg font-semibold"><?= htmlspecialchars($specialization) ?></h3>
                <p class="text-gray-600"><?= count($records) ?> research titles</p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Tables Content -->
    <div id="tablesContainer">
        
        <div class="mb-6 flex justify-end">
        </div>
        <?php foreach ($groupedResearch as $specialization => $records): ?>
    <div id="<?= htmlspecialchars('table-' . str_replace(' ', '-', strtolower($specialization))) ?>" class="table-content hidden">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <div class="mb-6 flex justify-end">
                <button class="export-btn text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" data-specialization="<?= htmlspecialchars($specialization); ?>">
                    <i class="fas fa-file-export"></i> <?php echo $export_name; ?>
                </button>
            </div>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <!-- Table header and body here -->
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $id_course; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_school; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_members; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_title; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_adviser; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_manuscript; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_abstract; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_status; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_specialorder; ?></th>
                        <th class="py-3 px-6 whitespace-nowrap"><?php echo $tbl_c_actions; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $research): ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="py-4 px-6 whitespace-nowrap font-medium text-gray-900 dark:text-white">#<?= htmlspecialchars($research['id']); ?></th>
                            <td class="py-4 px-6 whitespace-nowrap"><?= htmlspecialchars($research['school_year']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="truncate-text" data-full-content="<?= htmlspecialchars(implode(', ', json_decode($research['members_name'], true))) ?>">
                                    <?= htmlspecialchars(implode(', ', json_decode($research['members_name'], true))) ?>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-semibold whitespace-nowrap">
                                <div class="truncate-text" data-full-content="<?= htmlspecialchars($research['title_of_study']) ?>">
                                    <?= htmlspecialchars($research['title_of_study']) ?>
                                </div>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap"><?= htmlspecialchars(implode(', ', json_decode($research['adviser'], true))); ?></td>
                            
                            <!-- Manuscript Column -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <?php 
                                $manuscripts = json_decode($research['manuscript'], true);
                                if (!empty($manuscripts) && is_array($manuscripts)): 
                                    foreach ($manuscripts as $file): 
                                        if (file_exists($file)): ?>
                                            <a href="<?= htmlspecialchars($file); ?>" download="<?= basename($file); ?>"
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-600 block">
                                                <?= basename($file); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-500 italic">File missing: <?= htmlspecialchars(basename($file)) ?></span>
                                        <?php endif;
                                    endforeach; 
                                else: ?>
                                    <span class="text-gray-500 italic">No file</span>
                                <?php endif; ?>
                            </td>

                            <!-- Abstract Column -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <?php 
                                $abstracts = json_decode($research['abstract'], true);
                                if (!empty($abstracts) && is_array($abstracts)): 
                                    foreach ($abstracts as $file): 
                                        if (file_exists($file)): ?>
                                            <a href="<?= htmlspecialchars($file); ?>" download="<?= basename($file); ?>"
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-600 block">
                                                <?= basename($file); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-500 italic">File missing: <?= htmlspecialchars(basename($file)) ?></span>
                                        <?php endif;
                                    endforeach; 
                                else: ?>
                                    <span class="text-gray-500 italic">No file</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="badge <?= ($research['status'] == 'Approved') ? 'badge-success' : 'badge-warning' ?>">
                                    <?= htmlspecialchars($research['status']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                <?php
                                $specialOrder = $research['special_order'];
                                if (filter_var($specialOrder, FILTER_VALIDATE_URL)) {
                                    echo '<a href="' . htmlspecialchars($specialOrder) . '" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-600">'
                                        . htmlspecialchars($specialOrder) .
                                        '</a>';
                                } else {
                                    echo htmlspecialchars($specialOrder);
                                }
                                ?>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap">
                                <button class="text-blue-600 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-600" 
                                        onclick="showEditModal(<?= htmlspecialchars(json_encode($research, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)">
                                    Edit
                                </button>
                                <button class="text-green-600 hover:text-green-800 dark:text-green-500 dark:hover:text-green-600" onclick="showApproveModal(<?= $research['id'] ?>)">Approve</button>
                                <button class="text-red-600 hover:text-red-800 dark:text-red-500 dark:hover:text-red-600" onclick="showDeleteModal(<?= $research['id'] ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

    </div>
</div>

 <div id="formContainer" class="hidden p-6 bg-white border border-gray-200 rounded-lg shadow-md">
        <button id="hideFormButton" class="text-gray-400 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white float-right">X</button>
        <h2 class="text-xl font-semibold mb-4">Add Research Title</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-6">
                <input type="text" name="school_year" placeholder="School Year (e.g., 2023-2024)" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-dark">Members Name:</label>
                <div id="members-container">
                    <div class="input-group">
                        <input type="text" name="members_name[]" placeholder="Member Name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                        <button type="button" onclick="addMember()" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center ml-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" style="margin-top: 5px; margin-bottom: 5px;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <input type="text" name="title_of_study" placeholder="Title of Study" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-dark">Advisers:</label>
                <div id="advisers-container">
                    <div class="input-group">
                        <input type="text" name="adviser[]" placeholder="Adviser Name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                        <button type="button" onclick="addAdviser()" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 text-center ml-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800" style="margin-top: 5px; margin-bottom: 5px;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-dark">Upload Manuscript:</label>
                <input type="file" name="manuscript[]" accept=".docx, .pdf, .txt" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" required multiple>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-dark">Upload Abstract:</label>
                <input type="file" name="abstract[]" accept=".docx, .pdf, .txt" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" required multiple>
            </div>

            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-dark">Specialization:</label>
                <input type="text" name="specialization" id="specialization" class="block w-full text-m text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" required>
            </div>

            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-dark">Special Order:</label>
                <input type="text" name="special_order" id="special_order" class="block w-full text-m text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
            </div>

            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-dark">Status:</label>
                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                </select>
            </div>
            <div class="mb-6 flex justify-end">
                <button type="submit" name="add" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Save
                </button>
            </div>

        </form>
    </div>


<!-- Edit Modal -->
<div id="editModal" class="fixed top-0 left-0 w-full bg-black bg-opacity-50 flex items-center justify-center hidden" style="z-index: 99999; margin-top: 100px;">
    <div class="bg-white rounded-lg w-full max-w-md p-6" style="overflow-y: auto; max-height: 500px;">
        <span class="close text-red-400 hover:text-gray-900 dark:text-gray-900 dark:hover:text-danger float-right cursor-pointer" onclick="closeModal('editModal')">X</span>
        <h2 class="text-xl font-semibold mb-4">Edit Research Title</h2>
        <form id="editForm" action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editId">
            <div class="mb-6">
                <input type="text" name="school_year" id="editSchoolYear" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900">Members Name:</label>
                <div id="editMembersContainer">
                    <!-- Populated  -->
                </div>
            </div>
            <div class="mb-6">
                <input type="text" name="title_of_study" id="editTitle" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" required>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900">Advisers:</label>
                <div id="editAdvisersContainer">
                    <!-- Populated  -->
                </div>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900">Upload Manuscript:</label>
                <input type="file" name="manuscript" accept=".docx, .pdf, .txt" class="block w-full text-sm border border-gray-300 rounded-lg" multiple>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900">Upload Abstract:</label>
                <input type="file" name="abstract" accept=".docx, .pdf, .txt" class="block w-full text-sm border border-gray-300 rounded-lg" multiple>
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900">Specialization:</label>
                <input type="text" name="specialization" id="editSpecialization" class="block w-full text-sm border border-gray-300 rounded-lg">
            </div>
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900">Special Order:</label>
                <input type="text" name="special_order" id="editSpecialOrder" class="block w-full text-sm border border-gray-300 rounded-lg">
            </div>

            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-900">Status:</label>
                <select name="status" id="editStatus" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                </select>
            </div>
            <div class="mb-6">
                <button type="submit" name="edit" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Update</button>
            </div>
        </form>
    </div>
</div>
<!-- Approve Modal -->
<div id="approveModal" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg w-full max-w-md p-6">
        <span class="close text-gray-400 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white float-right cursor-pointer" onclick="closeModal('approveModal')">&times;</span>
        <h2 class="text-xl font-semibold mb-4">Approve Research</h2>
        <p class="mb-4">Are you sure you want to approve this research?</p>
        <form id="approveForm" action="" method="POST">
            <input type="hidden" name="id" id="approveId">
            <div class="flex justify-end gap-2">
                <button type="submit" name="approve" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">Approve</button>
                <button type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600" onclick="closeModal('approveModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg w-full max-w-md p-6">
        <span class="close text-gray-400 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white float-right cursor-pointer" onclick="closeModal('deleteModal')">&times;</span>
        <h2 class="text-xl font-semibold mb-4">Delete Research</h2>
        <p class="mb-4">Are you sure you want to delete this research?</p>
        <form id="deleteForm" action="" method="POST">
            <input type="hidden" name="id" id="deleteId">
            <div class="flex justify-end gap-2">
                <button type="submit" name="delete" class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">Delete</button>
                <button type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600" onclick="closeModal('deleteModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Modal ---
    function showEditModal(research) {
        try {
            research.id = parseInt(research.id);
            document.getElementById('editId').value = research.id;
            document.getElementById('editSchoolYear').value = research.school_year;
            document.getElementById('editTitle').value = research.title_of_study;
            document.getElementById('editStatus').value = research.status;
            document.getElementById('editSpecialization').value = research.specialization;
            document.getElementById('editSpecialOrder').value = research.special_order;

            const membersContainer = document.getElementById('editMembersContainer');
            if (membersContainer) {
                membersContainer.innerHTML = '';
                const members = Array.isArray(research.members_name) ?
                    research.members_name :
                    JSON.parse(research.members_name.replace(/&quot;/g, '"'));
                members.forEach((member, index) => {
                    const div = document.createElement('div');
                    div.className = 'input-group mb-3';
                    div.innerHTML = `
                        <input type="text" name="members_name[]" value="${member.replace(/"/g, '&quot;')}" 
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"
                            required>
                        ${index > 0 ? '<button type="button" onclick="removeMember(this)" class="text-red-600 hover:text-red-800" style="margin-top: 5px; margin-bottom: 5px;"><i class="fas fa-trash"></i></button>' : ''}
                    `;
                    membersContainer.appendChild(div);
                });
            }
            
            const advisersContainer = document.getElementById('editAdvisersContainer');
            if (advisersContainer) {
                advisersContainer.innerHTML = '';
                const advisers = Array.isArray(research.adviser) ?
                    research.adviser :
                    JSON.parse(research.adviser.replace(/&quot;/g, '"'));
                advisers.forEach((adviser, index) => {
                    const div = document.createElement('div');
                    div.className = 'input-group mb-3';
                    div.innerHTML = `
                        <input type="text" name="adviser[]" value="${adviser.replace(/"/g, '&quot;')}" 
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"
                            required>
                        ${index > 0 ? '<button type="button" onclick="removeAdviser(this)" class="text-red-600 hover:text-red-800" style="margin-top: 5px; margin-bottom: 5px;"><i class="fas fa-trash"></i></button>' : ''}
                    `;
                    advisersContainer.appendChild(div);
                });
            }
            const editModal = document.getElementById('editModal');
            if (editModal) {
                editModal.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error parsing research data:', error);
            alert('Err loading check console.');
        }
    }

    function showApproveModal(id) {
        const modal = document.getElementById('approveModal');
        if (modal) {
            modal.classList.remove('hidden');
        } else {
            console.error('El id "approveModal" not found.');
        }
    }

    function showDeleteModal(id) {
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            document.getElementById('deleteId').value = id;
            deleteModal.classList.remove('hidden');
        } else {
            console.error('El id "deleteModal" not found.');
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // --- Form Display Functions ---
    function addMember() {
        const container = document.getElementById('members-container');
        if (container) {
            const div = document.createElement('div');
            div.className = 'input-group mb-3';
            div.innerHTML = `
                <input type="text" name="members_name[]" placeholder="Member Name" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                <button type="button" onclick="removeMember(this)" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 ml-2" style="margin-top: 5px; margin-bottom: 5px;"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(div);
        }
    }

    function addAdviser() {
        const container = document.getElementById('advisers-container');
        if (container) {
            const div = document.createElement('div');
            div.className = 'input-group mb-3';
            div.innerHTML = `
                <input type="text" name="adviser[]" placeholder="Adviser Name" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                <button type="button" onclick="removeAdviser(this)" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 ml-2" style="margin-top: 5px; margin-bottom: 5px;"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(div);
        }
    }

    function removeMember(button) {
        button.parentElement.remove();
    }

    function removeAdviser(button) {
        button.parentElement.remove();
    }

    // --- Text Truncation Functions ---
    function toggleContent(element) {
        const fullContent = element.getAttribute('data-full-content');
        const truncatedContent = truncateText(fullContent, 50);
        if (fullContent.length > 50) {
            element.innerHTML = truncatedContent + ' <span class="text-blue-600 cursor-pointer show-more">Show more</span>';
            element.addEventListener('click', function(e) {
                if (e.target.classList.contains('show-more') || e.target.classList.contains('show-less')) {
                    e.preventDefault();
                    if (element.classList.contains('show-more')) {
                        element.innerHTML = fullContent + ' <span class="text-blue-600 cursor-pointer show-less">Show less</span>';
                        element.classList.remove('show-more');
                    } else {
                        element.innerHTML = truncatedContent + ' <span class="text-blue-600 cursor-pointer show-more">Show more</span>';
                        element.classList.add('show-more');
                    }
                }
            });
        }
    }

    function truncateText(text, maxLength) {
        return text.length <= maxLength ? text : text.substring(0, maxLength) + '...';
    }

    // --- Download & Export Functions ---
    function downloadFile(type, id) {
        const link = document.createElement('a');
        link.href = `download.php?type=${type}&id=${id}`;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportTableToCSV(specialization) {
    const tableId = 'table-' + specialization.toLowerCase().replace(/\s+/g, '-');
    const table = document.getElementById(tableId);
    if (!table) {
        console.error('Table not found:', specialization);
        return;
    }

    let csvContent = '';

    const headerCells = table.querySelectorAll('thead th');
    const headerArr = [];
    for (let i = 0; i < headerCells.length - 1; i++) {
        headerArr.push('"' + headerCells[i].textContent.trim() + '"');
    }
    csvContent += headerArr.join(',') + '\r\n';

    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowArr = [];
        for (let i = 0; i < cols.length - 1; i++) {
            rowArr.push('"' + cols[i].textContent.trim() + '"');
        }
        csvContent += rowArr.join(',') + '\r\n';
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `research_${specialization.replace(/\s+/g, '_')}.csv`;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.export-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const specialization = this.getAttribute('data-specialization');
            if (specialization) {
                exportTableToCSV(specialization);
            } else {
                console.error('No specialization defined.');
            }
        });
    });
});



    // --- Specialization Table ---
    function showSpecializationTable(specialization) {
        document.querySelectorAll('.table-content').forEach(table => {
            table.classList.add('hidden');
        });
        const tableId = 'table-' + specialization.replace(' ', '-').toLowerCase();
        const table = document.getElementById(tableId);
        if (table) {
            table.classList.remove('hidden');
        }
    }

    // --- Tabs Initialization ---
    function initializeTabs() {
        document.querySelectorAll('.tab-link').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.tab-link').forEach(link => {
                    link.classList.remove('text-blue-700', 'border-b-2', 'border-blue-700', 'font-semibold');
                    link.classList.add('text-blue-500');
                });
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.add('hidden');
                });
                this.classList.add('text-blue-700', 'border-b-2', 'border-blue-700', 'font-semibold');
                const tabId = this.getAttribute('data-tab');
                const tabContent = document.getElementById(tabId);
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                }
            });
        });
    }

    // --- Consolidated DOMContentLoaded ---
    document.addEventListener('DOMContentLoaded', function() {
        const showFormButton = document.getElementById('showFormButton');
        const hideFormButton = document.getElementById('hideFormButton');
        const tableContainer = document.getElementById('tableContainer');
        const formContainer = document.getElementById('formContainer');

        if (showFormButton) {
            showFormButton.addEventListener('click', function() {
                if (tableContainer) {
                    tableContainer.classList.add('hidden');
                }
                if (formContainer) {
                    formContainer.classList.remove('hidden');
                }
            });
        }
        if (hideFormButton) {
            hideFormButton.addEventListener('click', function() {
                if (tableContainer) {
                    tableContainer.classList.remove('hidden');
                }
                if (formContainer) {
                    formContainer.classList.add('hidden');
                }
            });
        }

        const truncateTexts = document.querySelectorAll('.truncate-text');
        truncateTexts.forEach(element => {
            toggleContent(element);
        });

        initializeTabs();

        // Auto CAPSLOCK for category inputs
        const editCategoryInput = document.getElementById('editCategory');
        if (editCategoryInput) {
            editCategoryInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
        const addCategoryInput = document.getElementById('category');
        if (addCategoryInput) {
            addCategoryInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }

        document.querySelectorAll('.table-content').forEach(table => {
            table.classList.add('hidden');
        });
    });
</script>
