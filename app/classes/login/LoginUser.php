<?php
// LoginUser.php
// ログインユーザーの基本インターフェースを定義
interface LoginUser {
    public function getUserId(): string;
    public function getUserGrade(): string;
    public function getHomeUrl(): string;
}