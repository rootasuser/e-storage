<?php
require_once __DIR__ . '/../core/Database.php';

class UserModel {
    private $conn;

    /**
     * Constructor to initialize database connection
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * REGISTRATION FOR STUDENT
     */
    public function registerUser($data) {
        $sql = "INSERT INTO users_tbl (first_name, middle_name, last_name, gender, role, username, email, password, profile_pic, status)
        VALUES (:first_name, :middle_name, :last_name, :gender, :role, :username, :email, :password, :profile_pic, 'pending')";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * LOGIN FOR STUDENT/ADMIN
     */
    public function loginUser($usernameOrEmail, $password) {
        $sql = "SELECT * FROM users_tbl 
                WHERE (username = :usernameOrEmail OR email = :usernameOrEmail) 
                  AND status = 'approved'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['usernameOrEmail' => $usernameOrEmail]); 
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }

    /**
     * FOR SPECIFIC ACCOUNT
     */
    public function getUserById($userId) {
        $sql = "SELECT * FROM users_tbl WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * LOAD ALL STUDENTS WHERE ROLE = Student
     */
    public function getAllUsers() {
        $stmt = $this->conn->prepare("SELECT * FROM users_tbl WHERE role = 'Student'");
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
    
    /**
     * COUNT STUDENTS WHERE ROLE = Student for PAGINATION
     */
    public function getUserCount() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM users_tbl WHERE role = 'Student'");
        if ($stmt) {
            return $stmt->fetchColumn();
        }
        return 0;
    }
    
    /**
     * PAGINATION 
     */
    public function getUsersByPage($offset, $limit) {
        $stmt = $this->conn->prepare("SELECT * FROM users_tbl WHERE role = 'Student' LIMIT :limit OFFSET :offset");
        if ($stmt) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            if ($stmt->execute()) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        return [];
    }
    
    /**
     * ADMIN SEARCH STUDENT
     */
    public function searchUsers($searchTerm) {
        $sql = "SELECT * FROM users_tbl WHERE role = 'Student' 
                AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $search = "%" . $searchTerm . "%";
            $stmt->bindValue(':search', $search, PDO::PARAM_STR);
            if ($stmt->execute()) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        return [];
    }
    
    /**
     * UPDATE STUDENT
     */
    public function updateUserStatus($id, $status) {
        $sql = "UPDATE users_tbl SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $id]);
    }

    /**
     * DELETE STUDENT
     */
    public function deleteUser($id) {
        $sql = "DELETE FROM users_tbl WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * BEFORE:: CHECK EMAIL FIRST IF EXISTS
     */
    public function checkEmailExists($email, $userId) {
        $sql = "SELECT id FROM users_tbl WHERE email = ? AND id != ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * EDIT STUDENT
     */
    public function editUser($id, $data) {
        try {
        
            if ($this->checkEmailExists($data['email'], $id)) {
                return ['status' => 'error', 'message' => 'Email already exists!'];
            }
    
            $sql = "UPDATE users_tbl SET first_name = ?, last_name = ?, middle_name = ?, email = ?, role = ?, status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'],
                $data['email'],
                $data['role'],
                $data['status'],
                $id
            ]);
    
            return ['status' => 'success', 'message' => 'User updated successfully.'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * GET ALL SPECIALIZATIONS
     */
    public function getAllSpecializations() {
        $query = "SELECT DISTINCT specialization FROM research_titles";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN); 
    }
    
    /**
     * GET RESEARCH TITLES BY SPECIALIZATION
     */
    public function getResearchTitlesBySpecialization($specialization) {
        $query = "SELECT * FROM research_titles WHERE specialization = :specialization";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':specialization', $specialization);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }
    
    /**
     * STORE ACCESS REQUEST
     */
    public function storeAccessRequest($userId, $researchId, $fileType, $filePath) {
        $sql = "INSERT INTO access_requests (user_id, research_id, file_type, file_path, status) 
                VALUES (?, ?, ?, ?, 'Pending')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$userId, $researchId, $fileType, $filePath]);
    }
    
    public function logActivity($userId, $activityType, $activityDetails) {
        $sql = "INSERT INTO activity_logs (user_id, activity_type, activity_details) 
                VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$userId, $activityType, $activityDetails]);
    }
    
    /**
     * GET ALL RESEARCH DATA
     */
    public function getAllResearchData() {
        $sql = "SELECT * FROM research_titles";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * RESEARCH HISTORY
     */
    public function saveResearchInterest($userId, $titleOfStudy) {
        $sql = "INSERT INTO research_interests (user_id, title_of_study) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$userId, $titleOfStudy]);
    }

  
       /**
 * GET REQUESTS BY USER ID
 */
public function getRequestsByUserId($userId) {
    $sql = "SELECT * FROM access_requests WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$userId]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($requests as &$request) {
       
        $decodedFilePath = json_decode($request['file_path'], true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilePath)) {
            $request['file_path'] = $decodedFilePath;
        } else {
           
            $request['file_path'] = [$request['file_path']];
        }
    }

    return $requests;
}
/**
 * CANCEL REQUEST
 */
public function deleteRequest($requestId) {
    $sql = "DELETE FROM access_requests WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([$requestId]);
}

/**
 * GET FILE BY ID
 */
public function getFileById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM access_requests WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * GET RESEARCH INTERESTS BY USER ID
 */
public function getResearchInterestsByUserId($userId) {
    $sql = "SELECT * FROM research_interests WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * UPDATE USER
 */
public function updateUser($id, $data) {
    $sql = "UPDATE users_tbl SET 
            first_name = :first_name, 
            last_name = :last_name, 
            email = :email, 
            profile_pic = :profile_pic 
            WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        ':first_name' => $data['first_name'],
        ':last_name' => $data['last_name'],
        ':email' => $data['email'],
        ':profile_pic' => $data['profile_pic'],
        ':id' => $id
    ]);
}

/**
 * COUNT BY STATUS 
 */
public function countRequestsByStatus($userId, $status) {
    $sql = "SELECT COUNT(*) AS count FROM access_requests WHERE user_id = ? AND status = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$userId, $status]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 * COUNT ACT. LOGS 
 */
public function countActivityLogs($userId) {
    $sql = "SELECT COUNT(*) AS count FROM activity_logs WHERE user_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 * COUNT # OF INTEREST 
 */
public function countResearchInterests($userId) {
    $sql = "SELECT COUNT(*) AS count FROM research_interests WHERE user_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}


public function getAllRequests() {
    $sql = "SELECT * FROM access_requests ORDER BY created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 *  APPROVED REQ.
 */
public function approveRequest($requestId) {
    $sql = "UPDATE access_requests SET status = 'Approved' WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([$requestId]);
}

/**
 *  REJECT REQ.
 */
public function rejectRequest($requestId) {
    $sql = "UPDATE access_requests SET status = 'Rejected' WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([$requestId]);
}

/**
 *  DEL REQ.
 */
public function deleteRequestStudent($requestId) {
    $sql = "DELETE FROM access_requests WHERE id = ?";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([$requestId]);
}

/**
 *  GET LOGS
 */
public function getAllActivityLogs() {
    $sql = "SELECT * FROM activity_logs ORDER BY created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 *  COUNT ACCOUNT ROLE = STUDENT
 */
  public function countStudents() {
    $sql = "SELECT COUNT(*) AS count FROM users_tbl WHERE role = 'Student'";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
/**
 *  COUNT REQ. TITLES
 */
public function countResearchTitles() {
    $sql = "SELECT COUNT(*) AS count FROM research_titles";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 *  COUNT RES.TITLE BASED ON STATUS
 */
public function countResearchTitlesByStatus($status) {
    $sql = "SELECT COUNT(*) AS count FROM research_titles WHERE status = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$status]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 *  COUNT ACCESS BASED ON STATUS
 */
public function countRequestsByStatuss($status) {
    $sql = "SELECT COUNT(*) AS count FROM access_requests WHERE status = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$status]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 *  COUNT ACCESS REQ BASED ON FILE TYPE
 */
public function countRequestsByFileType($fileType) {
    $sql = "SELECT COUNT(*) AS count FROM access_requests WHERE file_type = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$fileType]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
   
}