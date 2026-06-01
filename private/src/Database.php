<?php
/**
 * SoulMD Hub - Professional Database Service (PDO)
 * Centralized, Singleton-based MySQL connection manager.
 * 🚀 Patched: Added getFreshConnection() for long-running AI API scripts to prevent timeouts.
 */

class Database {
    private static $instance = null;
    private $pdo;

    // 私有化建構子，防止外部直接實例化
    private function __construct() {
        $this->connect();
    }

    // 核心連線邏輯
    private function connect() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 15 // 加入 15 秒連線超時容忍
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // 🚨 發生錯誤時記錄至系統 Log，絕對不在前端暴露連線字串及密碼！
            error_log('Database Connection Error: ' . $e->getMessage());
            
            // 判斷是否為 API 請求，若是則回傳 JSON，否則 Die 掉
            if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                die(json_encode(['success' => false, 'error' => 'Critical: Database connection failed.']));
            } else {
                die('System Critical: Database connection failed. Please check server logs.');
            }
        }
    }

    /**
     * 取得單例實例 (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 取得當前共用的 PDO 連線 (適用於 99% 的一般頁面及極速 API)
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * 🚀 獲取一個全新的、獨立的 PDO 連線 (Get Fresh Connection)
     * 專門用於 `api/chat.php` 等會被 cURL 阻塞超過 30-100 秒的長進程，
     * 避免因等待 AI 回覆而導致 "MySQL server has gone away" 錯誤。
     */
    public static function getFreshConnection() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 15
        ];
        
        try {
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Fresh Database Connection Error: ' . $e->getMessage());
            throw new Exception('Failed to establish a fresh database connection.');
        }
    }

    /**
     * 通用查詢 Helper (自動 Prepare 與 Execute)
     */
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * 封鎖 Cloning，確保單例模式嚴格性
     */
    private function __clone() {}

    /**
     * 封鎖 Unserialization，確保單例模式嚴格性
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize a database singleton.");
    }
}
?>