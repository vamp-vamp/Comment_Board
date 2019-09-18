<?php // データベース接続を確立
  function db_connect(){
    // 設定値定義
    $db_host = "localhost"; // データベースのホスト
    $db_name = "board"; // データベースの名前
    $db_user = "root"; // データベース接続ユーザー
    $db_pass = "vagrant"; // データベース接続パスワード

      try{
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // エラーモードの設定 レポートを表示
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // prepareのエミュレーションを停止
        return $pdo;
      } catch (PDOException $e) {
        // エラー発生時
        exit("データベースの接続に失敗しました");
      }
  }