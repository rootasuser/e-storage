<?php
require_once realpath(__DIR__ . '/../../../core/Database.php');

if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = (int) $_GET['id'];

    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("
        SELECT 
            manuscript, manuscript_filename, manuscript_mime, 
            abstract, abstract_filename, abstract_mime 
        FROM research_titles WHERE id = ?
    ");
    $stmt->execute([$id]);
    $fileData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fileData) {
        die("File not found in DB.");
    }

    if ($type === 'manuscript') {
        $fileContent = $fileData['manuscript'];
        $fileName = $fileData['manuscript_filename'];
        $mimeType = $fileData['manuscript_mime'];
    } else {
        $fileContent = $fileData['abstract'];
        $fileName = $fileData['abstract_filename'];
        $mimeType = $fileData['abstract_mime'];
    }

    if (!$fileContent) {
        die("File not available.");
    }

    header('Content-Description: File Transfer');
    header("Content-Type: $mimeType");
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header('Content-Length: ' . strlen($fileContent));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo $fileContent;
    exit;
}
?>
