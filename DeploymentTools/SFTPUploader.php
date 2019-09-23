<?php
$pathToPhpseclib = 'C:\\wamp\\www\\exface\\exface\\vendor\\phpseclib\\vendor';

$buildServer = 'sdrexf2.salt-solutions.de';
$sourcePath = '/C/wamp/www/exface/exface/tmp';
// $sourcePath = 'C:\\wamp\\www\\exface\\exface\\vendor'; 
$deployPath = 'testpath2/'; 

set_include_path(get_include_path() . PATH_SEPARATOR . $pathToPhpseclib);

include 'autoload.php';

$loader = new \Composer\Autoload\ClassLoader();
$loader->addPsr4('phpseclib\\', __DIR__ . 'phpseclib');
$loader->register();


use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use phpseclib\System\SSH\Agent;
use phpseclib\Net\SFTP;

$key = new RSA();
$key->loadKey(file_get_contents('C:\\Users\\clg\\.ssh\\id_rsa'));

// Domain can be an IP too

$accounts = array( "clg" => "fa9ae92eebf9d015eda0e6c0b41c79c4");
$user = "clg";

//$hash = md5($password);

// if (md5($password) !== $accounts[$user]) {
//      exit('Password incorrect. Program aborted.');
// }
 
define('NET_SFTP_LOGGING', SFTP::LOG_COMPLEX);

$sftp = new SFTP($buildServer);
if (!$sftp->login($user, 'xxxxxx')) {
    exit('Login failed. Program aborted');
}

$sftp->chdir('testpath2');
$sftp->put('test_archive.tar.gz' , 'C:/wamp/www/exface/exface/test_archive.tar.gz' , SFTP::SOURCE_LOCAL_FILE);

// echo $sftp->getSFTPLog(); 

?>



