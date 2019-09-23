<?php

include('Net/SSH2.php');

use exface\Core\DataConnectors\MySqlConnector;

const COST = 12;
const TEST = '$2y$12$/Wh9wxtVb9aukb8yCaNwt.pZWHgGiF8uBW4a9UaHPUv6un9eewSLu';
const TEST_PLAIN = ilovecats123;

$password = 'xxxxxxxxx';
    
Class CredentialsVerifier{
    
    public function validatePassword(string $user_name, string (string $password){
        return password_hash($password . PEPPER) ;
    }
    
}

echo("<h2>SSH-Verbindung zum AccessPoint</h2><br />");
.
$ssh = new Net_SSH2('193.10.1.224');
.
if (!$ssh->login('clg', $passwod )) {

    exit('Login Failed');

}
.
function packet_handler($str)
{
    echo $str;
}

shell_exec('tar cvfz x.tar.gz y/')
$ssh->exec('get ssid', 'packet_handler');



echo "Peppered Hash:", $pepperedHash;

password_hash('' . PEPPER,
    PASSWORD_BCRYPT, [ 'cost' => COST ]);



