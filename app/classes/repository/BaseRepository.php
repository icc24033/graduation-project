<?php
// BaseRepository.php

class BaseRepository {
    protected $pdo;

    // コンストラクタでPDOを受け取る
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * getConnection
     * 概要: 保持しているPDOインスタンスを返す（トランザクション管理などで使用）
     * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
}