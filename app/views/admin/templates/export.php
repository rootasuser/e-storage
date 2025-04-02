<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once realpath(__DIR__ . '/../../../core/Database.php');

class ResearchTitleModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getResearchByCategory($category) {
        $stmt = $this->conn->prepare("SELECT * FROM research_titles WHERE category = ?");
        $stmt->execute([$category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$model = new ResearchTitleModel();
$category = $_POST['category'] ?? null;

if (!$category) {
    die('Category not specified.');
}

$researchData = $model->getResearchByCategory($category);
$csvContent = "ID,School Year,Members,Title,Adviser,Status,Category,Special Order\n";

foreach ($researchData as $research) {
    $members = implode(', ', json_decode($research['members_name'], true));
    $adviser = implode(', ', json_decode($research['adviser'], true));

    $csvContent .= implode(',', [
        $research['id'],
        $research['school_year'],
        '"' . $members . '"', 
        '"' . $research['title_of_study'] . '"',
        '"' . $adviser . '"',
        $research['status'],
        $research['category'],
        $research['special_order'] ?? ''
    ]) . "\n";
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="research_' . str_replace(' ', '_', $category) . '.csv"');
header('Content-Length: ' . strlen($csvContent));
echo $csvContent;
exit;