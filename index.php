<?php
$f3 = require('lib/base.php');

$f3->config('config.ini');

$f3->route('GET /', function ($f3) {
    echo 'REST API FOR customers';
});

$f3->route('GET /setup', function ($f3) {
    $db = new DB\SQL('sqlite:' . $f3->get('DB'));
    $db->exec("CREATE TABLE IF NOT EXISTS customers (
               id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
               fio varchar(255) NOT NULL,
               login varchar(120) NOT NULL,
               birthday DATE NOT NULL,
               phone varchar(120) NOT NULL,
               password varchar(32) NOT NULL
          )"
    );
    $db->exec("CREATE TABLE IF NOT EXISTS comments
          (
               id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
               customer_id INTEGER NOT NULL,
               user varchar(255) NOT NULL,
               dt DATE NOT NULL,
               comment TEXT NOT NULL
          )"
    );
});

/** Show all customers */
$f3->route('GET /customers', function ($f3) {
    $db = new DB\SQL('sqlite:' . $f3->get('DB'));
    $customers = new DB\SQL\Mapper($db, 'customers');
    $customers = $customers->find()->cast();
    echo json_encode(array('status' => 200, 'message' => array('customers' => $customers)));
});

/** New record */
$f3->route('POST /customers', function ($f3) {
    $db = new DB\SQL('sqlite:' . $f3->get('DB'));
    $customer = new DB\SQL\Mapper($db, 'customers');
    $customer->copyfrom('POST');
    $customer->save();
    echo json_encode(array('status' => 200, 'message' => array('customer' => $customer->cast())));
});

/** get record */
$f3->route('GET /customers/@id', function ($f3, $params) {
    $db = new DB\SQL('sqlite:' . $f3->get('DB'));
    $customer = new DB\SQL\Mapper($db, 'customers');
    $customer->load(array("id=?", $params['id']));

    $comments = new DB\SQL\Mapper($db, 'comments');
    $comments = $comments->find(array("customer_id=?", $customer->id));
    $arComments = array();
    foreach($comments as $c){
        $arComments[] = $c->cast();
    }
    echo json_encode(array('status' => 200, 'message' => array('customer' => $customer->cast(), 'comments' => $arComments)) );
});

/** change record */
$f3->route('PUT /customers/@id', function ($f3, $params) {
    $db = new DB\SQL('sqlite:' . $f3->get('DB'));
    $customer = new DB\SQL\Mapper($db, 'customers');
    $customer->load(array("id=?", $params['id']));
    $customer->copyfrom('GET');
    $customer->save();
    echo json_encode(array('status' => 200, 'message' => array('customer_id' => $customer->id)));
});

/** Delete record */
$f3->route('DELETE /customers/@id', function ($f3, $params) {
    $db = new DB\SQL('sqlite:' . $f3->get('DB'));
    $customer = new DB\SQL\Mapper($db, 'customers');
    $customer->load(array("id=?", $params['id']));
    $customer->erase();
    echo json_encode(array('status' => 200, 'message' => 'customer deleted'));
});

/** Comment add */
$f3->route('POST /customers/@id/comments', function ($f3, $params) {
    $db = new DB\SQL('sqlite:' . $f3->get('DB'));
    $customer = new DB\SQL\Mapper($db, 'customers');
    $customer->load(array("id = ?", $params['id']));
    $id = $customer->get('id');
    if (isset($id)) {
        $comments = new DB\SQL\Mapper($db, 'comments');
        $comments->copyfrom('POST');
        $comments->customer_id = $id;
        $comments->save();
        echo json_encode(array('status' => 200, 'message' => array('comment' => $comments->cast())));
    } else {
        echo json_encode(array('status' => 404, 'message' => 'Customer not found'));
    }
});

$f3->set('ONERROR',function($f3) {
    echo json_encode(array('status' => 400, 'message' => $f3->get('ERROR.text')));
});

$f3->run();
