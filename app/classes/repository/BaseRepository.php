<?php
// BaseRepository.php

class BaseRepository {
    protected $pdo;

    // コンストラクタでPDOを受け取る
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
}