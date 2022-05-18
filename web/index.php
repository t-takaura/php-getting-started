<?php

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// Our web handlers

$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.');
  return $app['twig']->render('index.twig');
});

$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->get('/hello', function() use($app) {
  $app['monolog']->addDebug('hello');
  return 'Hello, World!';
});

$app->get('/staff', function() use($app) {
  $app['monolog']->addDebug('staff');
  # 検証用：開発ではオンコーディングではなく、環境変数から取得すること
  # でないと、開発環境/検証環境/本番環境とそれぞれ異なるDB参照のために、同じプログラムが使えず、
  # 修正しながら デプロイすることになってしまうため。
  $mysqli = mysqli_init();
  $mysqli->ssl_set("../web/static/b27ab6ceb1a17d-key.pem","../web/static/b27ab6ceb1a17d-cert.pem", "../web/static/cleardb-ca.pem", null, null);
  # MYSQLI_CLIENT_SSL を使ったら、CA情報でエラーとなるので MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERTとする
  $mysqli->real_connect("us-cdbr-east-05.cleardb.net", "b27ab6ceb1a17d", "6bd39009", "heroku_798c3d7d6611094",null,null,MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
  # 画面表示データサンプル <1>
  $host_info = $mysqli->host_info;
  # SQLサンプル <2>
  $sql = 'SELECT id, name, branch, age FROM staff WHERE age < ?';
  $stmt = $mysqli->prepare($sql);
  $age = 38;
  # Bindサンプル バインド変数の型 s:string,i:integer,d:double,b:blob
  $stmt->bind_param('i', $age);
  $staffs = array();
  $stmt->execute();
  if ($result = $stmt->get_result()) {
      while ($row = $result->fetch_assoc()) {
        $staffs[] = $row;
      }
      $result->close();
  }
  $mysqli->close();
  # <1><2>をパラメータとしてテンプレートを使って画面出力
  return $app['twig']->render('staff.html', ['staffs' => $staffs, 'hostinfo' => $host_info]);
});

$app->run();
