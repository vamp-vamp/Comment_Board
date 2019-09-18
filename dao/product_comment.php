<?php //データアクセスオブジェクト
  /* =======================================
  機能　 : 商品コメント一覧を取得
  引数　 : なし
  戻り値 : 商品コメントレコードの配列
  ======================================= */
  function get_all_product_comment() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM comment");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }

  /* =======================================
  機能　 : 商品コメントテーブルにレコードを追加
  引数　 : 商品タイトル,商品コメント
  戻り値 : なし
  ======================================= */
  function insert_product_comment($product_comment,$product_comment_image) {
    global $pdo;
    $now_date = new DateTime();
    $now_date = $now_date->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO comment (comment,image,create_date) VALUES(:product_comment,:product_comment_image,:now_date)");
    $stmt->bindValue(':product_comment', $product_comment);
    $stmt->bindValue(':product_comment_image', $product_comment_image);
    $stmt->bindValue(':now_date', $now_date);
    $stmt->execute();
  }
