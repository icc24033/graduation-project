<?php
// TeacherItemTemplateRepository.php
require_once __DIR__ . '/../BaseRepository.php';

class TeacherItemTemplateRepository extends BaseRepository {

    /**
     * テンプレート一覧取得
     */
    public function getTemplates($teacherId, $subjectId) {
        $sql = "SELECT template_id, item_name 
                FROM teacher_item_templates 
                WHERE teacher_id = :teacher_id 
                AND subject_id = :subject_id
                ORDER BY template_id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':subject_id' => $subjectId
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * テンプレート新規作成
     */
    public function createTemplate($teacherId, $subjectId, $itemName) {
        $sql = "INSERT INTO teacher_item_templates (teacher_id, subject_id, item_name)
                VALUES (:teacher_id, :subject_id, :item_name)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':teacher_id' => $teacherId,
            ':subject_id' => $subjectId,
            ':item_name' => $itemName
        ]);
    }

    /**
     * テンプレート削除
     * ※他人のテンプレートを消さないよう teacher_id も条件に含める
     */
    public function deleteTemplate($templateId, $teacherId) {
        $sql = "DELETE FROM teacher_item_templates 
                WHERE template_id = :template_id 
                AND teacher_id = :teacher_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':template_id' => $templateId,
            ':teacher_id' => $teacherId
        ]);
    }
}