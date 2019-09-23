<?php



$pathToPhpseclib = 'C:\\wamp\\www\\exface\\exface\\vendor\\phpseclib\\vendor';
set_include_path(get_include_path() . PATH_SEPARATOR . $pathToPhpseclib);



include 'autoload.php';

$loader = new \Composer\Autoload\ClassLoader();
$loader->addPsr4('phpseclib\\', __DIR__ . 'phpseclib');
$loader->register();

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
//use phpseclib\System\SSH\Agent;
use phpseclib\Net\SCP;
use phpseclib\Crypt\AES;

// const COST = 12;
// const PEPPER = '.m9h-RL=^M/72;tdU\Bz';
// echo password_hash('ilovecats123' . PEPPER,
//     PASSWORD_BCRYPT, [ 'cost' => COST ]);


// Server data 
$buildServer = "sdrexf2.salt-solutions.de";

// Private Key Data
//$privateKeyFile= "/cygdrive/c/Users/clg/.ssh/id_ed25519";
//$passPhrase = "";

// $cipher = new AES(AES::MODE_ECB);
// $cipher->setPassword('xxxxxxxx');

// Login Credentials
$login = 'clg';
$password = 'xxxxxxx'; // TODO: Read encrypted password from exf.LOGIN_CREDENTIALS with $buildServer and $login

// Paths on local server (source) and build server (deploy path)
$deployPath = "/cygdrive/c/Users/" . $login . "/testpath3";
$localExfacePath = "C:\wamp\www\exface\exface";
$sourceDir = "vendor";
$archiveFileName = 'test_archive3.tar.gz';
// Compress Source directory

$cdSourcePathCommand = 'cd ' . $localExfacePath;
$tarCommand = 'tar czf ' . $localExfacePath  . PATH_SEPARATOR . $archiveFileName . $sourceDir; // ' C:\wamp\www\exface\exface' . PATH_SEPARATOR . $sourceDir ;

try {
    shell_exec($cdSourcePathCommand);
    echo shell_exec($showWorkingDirectory);
    // shell_exec( gpg --output bla.txt --decrypt bla.gpg;)
    // $password = readFile(bla.txt);
    // $sshCommand = 'sshpass -p $password /cygdrive/c/WINDOWS/System32/OpenSSH/ssh.exe ' . $login . '@' . $buildServer . ' "(cd /cygdrive/c/Users/clg/testpath3; tar xzf -)"'; 
    shell_exec($tarCommand); //'tar czf - ' . $sourcePath . ' | ' . $sshCommand );     
 
} catch (Exception $e) {
    exit('Tar failed with Exception ' . e);
}
//$key = \phpseclib\Crypt\PublicKeyLoader::load($privateKeyPath, $passphrase);

// Connect to build server
$ssh2 = new SSH2($buildServer);
if (!$ssh2->login($login, $password )) { // $key)) {
    exit('Login Failed');
}

// Create deployment directory on build server if not existent
$ssh2->exec('mkdir -p ' . $deployPath);

// Transfer zipped Tar archive to build server
$scp = new SCP($ssh2);

echo " SCP command started...\n".
$remoteFile = $deployPath . "/" . $archiveFileName;
if(!$scp->put( $remoteFile, $localExfacePath . PATH_SEPARATOR . $archiveFileName, SCP::SOURCE_LOCAL_FILE)){ // SCP::SOURCE_LOCAL_FILE has value 1 (if error occurs saying "nonnumerical blabla")
    exit('SCP Transfer aborted with error.');
}

echo "SCP command done\n";
echo "Tar extraction started";
// Decompress zipped Tar archive at deploy path
try{
    $ssh2->exec("cd $deployPath; tar xzf" . $archiveFileName . ' -C ./  ' ); // TODO: Cleanup tar archive on both servers
} 
catch (Exception $e) { 
    exit("Decompression aborted due to exception." . $e); 
}

echo "Tar extraction completed.";
    
$ssh2->exec('ls -la');

?>



