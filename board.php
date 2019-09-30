<?php
//エラーの設定
ini_set("display_errors", 1); //エラーを画面に表示
error_reporting(E_ALL); //すべてのエラーを出力する

require_once('functions.php');
require_once('db.php');
require_once('dao/product_comment.php');
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
$product_comment_image = '';
//エラーメッセージの初期化
$errors = array();

if(!empty($_POST)){

  //コメントのみの投稿可
  //画像のみの投稿可
  //どちらもない場合はエラー

    //POSTされたデータを変数に入れる
    $product_comment = isset($_POST['product_comment']) ? $_POST['product_comment'] : NULL;

    //前後にある半角全角スペースを削除
    $product_comment = spaceTrim($product_comment);

    //入力判定
    if (empty($product_comment)) {
      //画像アップロードされていなかった場合
      if(empty($_FILES['image_file']['name'])){
        $errors['product_comment_none'] = "コメントが入力されていません。";
      }
    }elseif(mb_strlen($product_comment) > 1000){
      $errors['product_comment_length'] = "コメントは1000文字以内で入力して下さい。";
    }
  
  //アップロードファイルの判定
  if(!empty($_FILES['image_file']['name'])){
    try {
      if (is_uploaded_file ( $_FILES ['image_file'] ['tmp_name'] )) {
        //ファイルのMIMEタイプをチェック
        if (!isset($_FILES['image_file']['error']) || !is_int($_FILES['image_file']['error'])) {
            exit("パラメータが不正です");
        }
        // $_FILES['image_file']['error'] の値を確認
        switch ($_FILES['image_file']['error']) {
          case UPLOAD_ERR_OK: // OK
              break;
          case UPLOAD_ERR_NO_FILE:   // ファイル未選択
              exit("ファイルが選択されていません");
              break;
          case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
          case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過 (フォームで設定した場合のみ)
              exit("画像のファイルサイズは2MBまでです");
              break;
          default:
              exit("その他のエラーが発生しました");
              break;
        }

      //ここで定義するサイズ上限のオーバーチェック
      if ($_FILES['image_file']['size'] > 2097152 ) {
          exit("画像のファイルサイズは2MBまでです");
      }

      $product_comment_image = uniqid();
      switch (exif_imagetype ( $_FILES['image_file']['tmp_name'])) {
        case IMAGETYPE_JPEG :
            $product_comment_image .= '.jpg';
            break;
        case IMAGETYPE_GIF :
            $product_comment_image .= '.gif';
            break;
        case IMAGETYPE_PNG :
            $product_comment_image .= '.png';
            break;
        default :
            exit("アップロード可能なファイルは[jpeg] [png] [gif]のみです。");
            break;
      }

      //ファイルを一時フォルダから指定したディレクトリに移動
      if(!move_uploaded_file($_FILES['image_file']['tmp_name'], 'upload/'. $product_comment_image)){
          exit("ファイル保存時にエラーが発生しました");
      }
      }
    } catch (RuntimeException $e) {
      exit("アップロードに失敗しました");
  }
  }

//エラーが無ければデータベースに登録
if(count($errors) === 0){
  $pdo = db_connect();
  try{ //コメントをデータベースへ登録する
    insert_product_comment($product_comment,$product_comment_image);
  } catch (PDOException $e) {
    exit("登録に失敗しました");
  }
  //リロードによる二重サブミット防止策
  header('Location:http://192.168.33.10/board/board.php');

} elseif(count($errors) > 0){ ?>

	<li class="step3 active error">
	<p>エラー<br>下記メッセージをご確認ください。</p>
	<p class="number"><span class="fa-stack fa-lg">
	<i class="fa fa-circle fa-stack-2x"></i>
	<i class="fa fa-inverse fa-stack-1x">!</i>
	</span></p>
	</li>
    <?php foreach($errors as $error){ ?>
      <div class='alert-notify-box'><p class='alert-notify-box-text'><?=he($error)?></p></div>
    <?php
    }
}
}
?>

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
          <!-- <input type="hidden" name="MAX_FILE_SIZE" value="10"> -->
          <input type="file" name="image_file" accept="image/jpeg, image/png, image/gif" class="custom-file-input" id="customFile">
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
      <?php if ($product_rowcomment['image']){ ?>
        <img class="card-img-top" src="./upload/<?= he($product_rowcomment['image']);?>" alt="コメントの画像">
      <?php } ?>
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
