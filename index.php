<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'database.php';

$app = new \Slim\App;
// $app->get('/hello/{name}/{nrp}', function (Request $request, Response $response, array $args) {
//     $name = $args['nrp'];
//     $response->getBody()->write("Hello, $name");

//     return $response;
// });

//buat menampilkan posisi barang, tanggal, dan statusnya (TRACKING!)
$app->get('/api/shipping_detail/{kode_resi}', function(Request $request, Response $response, array $args){
	global $con;
	$kode_resi=$args['kode_resi'];
	$sql="SELECT * FROM soa_express_detail where kode_resi LIKE '".$kode_resi."'";
	$res=mysqli_query($con,$sql);
	$data=mysqli_fetch_assoc($res);

	//set header
	$response = $response->withHeader('Content-Type','application/json');

	//set body
	$response->getBody()->write(json_encode($data));

	return $response;
});
//buat menampilkan data pengirim dan penerima (TRACKING!)
$app->get('/api/shipping_track/{kode_resi}', function(Request $request, Response $response, array $args){
	global $con;
	$data=[];
	$kode_resi=$args['kode_resi'];
	$sql="SELECT * FROM soa_express_detail where kode_resi LIKE '".$kode_resi."'";
	$res=mysqli_query($con,$sql);

	while($row=mysqli_fetch_assoc($res)){
		$data[]=$row;
	}

	//set header
	$response = $response->withHeader('Content-Type','application/json');

	//set body
	$response->getBody()->write(json_encode($data));
	
	return $response;
});
//buat passing harga (CHECK HARGA!)
$app->get('/api/shipping_price/s/{ori}&{dest}&{w}&{type}',function(Request $request, Response $response, array $args){
	global $con;
	$status=array(
		'err' => 0,
		'msg' => ""
	);
	$ori=$args['ori'];
	$dest=$args['dest'];
	$type=$args['type'];
	$w=$args['w'];


	$obj=$request->getParsedBody();
	$sql="SELECT harga FROM soa_shipping_price WHERE origin LIKE '".$ori."' AND destination LIKE '".$dest."'";
	$res=mysqli_query($con,$sql);
	$dat=mysqli_fetch_assoc($res);
	
	$data = $dat['harga'];
	
	$data *= $w;
	if($type==1){// 1=JNcK ,2= express,3= regular
		$data *= 3;
	}
	else if($type==2){
		$data *= 2;
	}

	$response = $response->withHeader('Content-Type','application/json');
	$response->getBody()->write(json_encode($data));

	return $response;
});

$app->delete('/api/delete_track/{kode_resi}',function(Request $request, Response $response, array $args){
	global $con;
	$kode_resi=$args['kode_resi'];
	$status=array(
		'err' => 0,
		'msg' => ""
	);
	$sql="DELETE FROM soa_express_detail WHERE kode_resi LIKE '".$kode_resi."' ";
	$res=mysqli_query($con,$sql);

	$sql="DELETE FROM soa_express WHERE kode_resi LIKE '".$kode_resi."' ";
	$res=mysqli_query($con,$sql);	

	if(!$res)
	{
		$status['err']=1;
		$status['msg']="error delete from database";
	}
	$response = $response->withHeader('Content-Type','application/json');
	$response->getBody()->write(json_encode($status));

	return $response;
});

// BUAT ADMIN

$app->post('/api/shipping_detail',function(Request $request, Response $response, array $args){
	global $con;
	$status=array(
		'err' => 0,
		'msg' => ""
	);

	while (true) {
		$kode_resi = 'JNcK' . rand(10000000,99999999);
		$sql = "SELECT COUNT(DISTINCT kode_resi) from soa_express WHERE kode_resi LIKE '".$kode_resi."'";
		$res = mysqli_query($con, $sql);
		$data = mysqli_fetch_assoc($res);

		if($data == 0){
			break;
		}
	}

	$obj=$request->getParsedBody();
	$sql="INSERT INTO soa_express VALUES(default,'".$obj['nama_pengirim']."', '".$obj['alamat_pengirim']."', '".$obj['nama_penerima']."', '".$obj['alamat_penerima']."', '".$obj['kode_resi']."')";
	$res=mysqli_query($con,$sql);

	$sql="INSERT INTO soa_express_detail VALUES(default,'".$obj['kode_resi']."', CURDATE(), CURTIME(), 1, '')";
	$res=mysqli_query($con,$sql);

	if(!$res)
	{
		$status['err']=1;
		$status['msg']="error insert to database";
	}
	$response = $response->withHeader('Content-Type','application/json');
	$response->getBody()->write(json_encode($status));

	return $response;
});

$app->post('/api/shipping_track_detail',function(Request $request, Response $response, array $args){
	global $con;
	$status=array(
		'err' => 0,
		'msg' => ""
	);

	$obj=$request->getParsedBody();

	$sql="INSERT INTO soa_express_detail VALUES(default,'".$obj['kode_resi']."', CURDATE(), CURTIME(), ".$obj['status'].", '".$obj['location']."')";
	$res=mysqli_query($con,$sql);

	if(!$res)
	{
		$status['err']=1;
		$status['msg']="error insert to database";
	}
	$response = $response->withHeader('Content-Type','application/json');
	$response->getBody()->write(json_encode($status));

	return $response;
});

$app->post('/api/shipping_price',function(Request $request, Response $response, array $args){
	global $con;
	$status=array(
		'err' => 0,
		'msg' => ""
	);

	$obj=$request->getParsedBody();

	$sql="INSERT INTO soa_shipping_price VALUES(default,'".$obj['origin']."', '".$obj['destination']."', ".$obj['harga'].")";
	$res=mysqli_query($con,$sql);

	if(!$res)
	{
		$status['err']=1;
		$status['msg']="error insert to database";
	}
	$response = $response->withHeader('Content-Type','application/json');
	$response->getBody()->write(json_encode($status));

	return $response;
});

$app->run();