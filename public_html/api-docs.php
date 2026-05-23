<?php
// 設定為公開模式，跳過 Session 與 API Key 獲取邏輯
$isPublicApiPage = true;

// 引入原有檔案
require_once __DIR__ . '/my-api.php';