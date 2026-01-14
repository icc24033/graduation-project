<?php
// 必要なリポジトリファイルをすべてここで読み込む
require_once __DIR__ . '/CourseRepository.php';
require_once __DIR__ . '/SubjectRepository.php';
require_once __DIR__ . '/TeacherRepository.php';
require_once __DIR__ . '/StudentRepository.php';
require_once __DIR__ . '/CsvRepository.php';
require_once __DIR__ . '/ErrorStudentRepository.php';
require_once __DIR__ . '/StudentLoginRepository.php';
require_once __DIR__ . '/StudentGradeRepository.php';
require_once __DIR__ . '/MaintenanceRepository.php';
require_once __DIR__ . '/TeacherLoginRepository.php';
require_once __DIR__ . '/timetable/TimetableRepository.php';
require_once __DIR__ . '/TempTeacherCsvRepository.php';
require_once __DIR__ . '/ErrorTeacherTableRepository.php';



// 新しいリポジトリができたらここに追加する
class RepositoryFactory {
    // DB接続を保持する静的プロパティ
    private static $pdo = null;

    /**
     * データベース接続を取得（シングルトンパターン）
     * 初回のみ接続し、2回目以降は使い回す
     */
    public static function getPdo() {
        // まだ接続が確立されていない場合のみ接続を作成
        if (self::$pdo === null) {
            // 設定ファイルの読み込み
            $config_path = __DIR__ . '/../../../config/secrets_local.php';
            if (!file_exists($config_path)) {
                throw new Exception('Config file not found.');
            }
            $config = require $config_path;

            $db_host = $config['db_host'] ?? '';
            $db_name = $config['db_name'] ?? '';
            $db_user = $config['db_user'] ?? '';
            $db_pass = $config['db_pass'] ?? '';

            try {
                $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$pdo = new PDO($dsn, $db_user, $db_pass, $options);
            } catch (PDOException $e) {
                error_log("DB Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed.");
            }
        }
        // 既に接続が確立されている場合はそれを返す
        // 自身のプロパティに接続情報を格納する
        return self::$pdo;
    }

    /**
     * データベース接続を終了する
     * 必要なデータをすべて取得した後に呼び出す
     */
    public static function closePdo() {
        self::$pdo = null;
    }

    /**
     * コースリポジトリのインスタンスを取得
     */
    public static function getCourseRepository() {
        return new CourseRepository(self::getPdo());
    }

    /**
     * 科目リポジトリのインスタンスを取得
     */
    public static function getSubjectRepository() {
        return new SubjectRepository(self::getPdo());
    }

    /**
     * 教員リポジトリのインスタンスを取得
     */
    public static function getTeacherRepository() {
        return new TeacherRepository(self::getPdo());
    }

    /**
     * 生徒リポジトリのインスタンスを取得
     */
    public static function getStudentRepository() {
        return new StudentRepository(self::getPdo());
    }

    /**
     * CSVリポジトリのインスタンスを取得
     */
    public static function getCsvRepository() {
        return new CsvRepository(self::getPdo());
    }

    /**
     * エラーデータリポジトリのインスタンスを取得
     */
    public static function getErrorStudentRepository() {
        return new errorStudentRepository(self::getPdo());
    }

    /**
     * 学生ログインリポジトリのインスタンスを取得
     */
    public static function getStudentLoginRepository() {
        return new StudentLoginRepository(self::getPdo());
    }

    /**
     * 学生学年リポジトリのインスタンスを取得
     */
    public static function getStudentGradeRepository() {
        return new StudentGradeRepository(self::getPdo());
    }

    /**
     * 自動処理履歴リポジトリのインスタンスを取得
     */
    public static function getMaintenanceRepository() {
        return new MaintenanceRepository(self::getPdo());
    }

    /**
     * 時間割リポジトリのインスタンスを取得
     */
    public static function getTimetableRepository() {
        return new TimetableRepository(self::getPdo());
    }
    
    /**
     * 先生ログインリポジトリのインスタンスを取得
     */
    public static function getTeacherLoginRepository() {
        return new TeacherLoginRepository(self::getPdo());
    }

    /**
     * 一時教員CSVリポジトリのインスタンスを取得
     */
    public static function getTempTeacherCsvRepository() {
        return new TempTeacherCsvRepository(self::getPdo());
    }

    /**
     * エラー教員テーブルリポジトリのインスタンスを取得
     */
    public static function getErrorTeacherTableRepository() {
        return new ErrorTeacherTableRepository(self::getPdo());
    }
}