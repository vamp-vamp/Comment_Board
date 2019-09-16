<?php
//エラーの設定
ini_set("display_errors", 1); //エラーを画面に表示
error_reporting(E_ALL); //すべてのエラーを出力する

// commonファイル
  // データベース接続を確立
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

//functionsファイル
  // htmlentitiesのショートカット関数
  function he($str){
      return htmlentities($str, ENT_QUOTES, "UTF-8");
  }

//データアクセスオブジェクト(関数?)ファイル
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
  function insert_product_comment($product_comment,$image) {
    global $pdo;
    $now_date = new DateTime();
    $now_date = $now_date->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO comment (comment,image,create_date) VALUES(:product_comment,:image,:now_date)");
    $stmt->bindValue(':product_comment', $product_comment);
    $stmt->bindValue(':image', $image);
    $stmt->bindValue(':now_date', $now_date);
    $stmt->execute();
  }

?>

<!doctype html>
<html lang="ja">
 
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
 
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
 
    <title>画像投稿コメントボード</title>
</head>
 
<body>

<?php
$product_comment = '';
$image = '';

if(!empty($_POST)){
  var_dump($_POST);
  if(!empty($_POST['product_comment'])){
    $product_comment = $_POST['product_comment'];
  }
  if(!empty($_FILES['image_file']['name'])){
    try {
      if (is_uploaded_file ( $_FILES ['image_file'] ['tmp_name'] )) {
      $image = uniqid();
      switch (exif_imagetype ( $_FILES['image_file']['tmp_name'])) {
        case IMAGETYPE_JPEG :
            $image .= '.jpg';
            break;
        case IMAGETYPE_GIF :
            $image .= '.gif';
            break;
        case IMAGETYPE_PNG :
            $image .= '.png';
            break;
        default :
            header ( 'Location: board.php' );
            exit ();
      }
      // ファイルを一時フォルダから指定したディレクトリに移動
      move_uploaded_file($_FILES['image_file']['tmp_name'], 'upload/'. $image);
      }
    } catch (PDOException $e) {
      exit("アップロードに失敗しました");
    }
  }

  $pdo = db_connect();
  try{ //コメント投稿があればデータベースへ登録する
    insert_product_comment($product_comment,$image);
  } catch (PDOException $e) {
    exit("登録に失敗しました");
  }
  //リロードによる二重サブミット防止策
  header('Location:http://192.168.33.10/board/board.php');
} ?>

<div class="container-fluid">

<h1>画像投稿コメントボード</h1>
<!-- 投稿フォームの設置 -->
<form action="board.php" method="post" enctype="multipart/form-data">
  <div class="form-group">
    <label for="InputComment">コメント</label>
    <textarea class="form-control" name="product_comment" id="InputComment" rows="3" placeholder="コメントを入力してください"></textarea>
    <small class="text-muted">※コメントは1000字以内で書いてください</small>
  </div>
  <div class="form-group">
    <div class="input-group">
      <div class="custom-file">
          <input type="file" name="image_file" class="custom-file-input" id="customFile">
          <label class="custom-file-label" for="customFile" data-browse="参照">ファイル選択...</label>
      </div>
      <div class="input-group-append">
          <button type="button" class="btn btn-outline-secondary reset">取消</button>
      </div>
    </div>
  </div>
  <button type="submit" class="btn btn-primary">投稿する</button>
</form>

<br>

  <div class="card-columns">

    <?php
    $pdo = db_connect();
    $product_comments = get_all_product_comment();

    //コメントループの開始
    foreach ($product_comments as $product_rowcomment) { ?>
      <div class="card bg-light">
        <img class="card-img-top" src="./upload/<?= he($product_rowcomment['image']);?>" alt="コメントの画像">
        <div class="card-body">
          <p class="card-text"><?= he($product_rowcomment['comment']);?></p>
          <p class="card-text"><small class="text-muted"><?= he($product_rowcomment['create_date']);?></small></p>
        </div>
      </div>
  <?php } ?>


  </div>
</div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="http://192.168.33.10/board/js/imageup.js"></script>

</body>
 
</html>
