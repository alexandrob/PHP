<?php

require("/usr/share/php/libphp-phpmailer/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * PHP API usage example
 *
 * contributed by: Art of WiFi
 * description:    example basic PHP script to change the WPA2 password/PSK of a WLAN, returns true on success
 */

/**
 * using the composer autoloader
 */
require_once 'vendor/autoload.php';    // Permanece sem alteracao

/**
 * include the config file (place your credentials etc. there if not already present)
 * see the config.template.php file for an example
 */
require_once 'config/config.php';     // Ajustado conforme novo local do arquivo config.php

/**
 * The site to which the WLAN you want to modify belongs
 */
$site_id = ['default'];       // Este campo é case sensitive, o campo dtim_mode é default minusculo.

/**
 * the id of the WLAN you wish to modify
 */
$wlan_id = ['64126c51f9e604144549cbf5'];   // Acesse API Configuration > list wlan configuration encontre o campo name com o SSID desejado e obtenha o valor do campo _id.

/**
 * the new WPA2 password/PSK to apply to the above WLAN
 */
//$new_password = '';
$predefined_password = '';

//
if ( $predefined_password == '' ) {
        $new_password = generateStrongPassword();
} else {
        $new_password = $predefined_password;
}


/**
 * initialize the UniFi API connection class and log in to the controller
 */
for ($i=0;$i<count($wlan_id);$i++){
	$unifi_connection = new UniFi_API\Client($controlleruser, $controllerpassword, $controllerurl, $site_id[$i], $controllerversion);
	$set_debug_mode   = $unifi_connection->set_debug($debug);
	$loginresults     = $unifi_connection->login();
	$results          = $unifi_connection->set_wlansettings($wlan_id[$i], $new_password);
}

smtpMailer( $new_password );


// FUNCAO GERADOR DE SENHA
// the new WPA2 password/PSK to apply to the above WLAN min = 8 / max = 63 characters
// A variavel $available_sets o l=letras minuscolas o u=letras maiusculas o d=numeros e o s=caracter especial
function generateStrongPassword($length = 8, $add_dashes = false, $available_sets = 'd')
{
        $sets = array();

        if(strpos($available_sets, 'l') !== false)
                $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if(strpos($available_sets, 'u') !== false)
                $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if(strpos($available_sets, 'd') !== false)
                $sets[] = '0123456789';
        if(strpos($available_sets, 's') !== false)
                $sets[] = '!@#$%&*?';

        $all = '';
        $password = '';

        foreach($sets as $set)
        {
                $password .= $set[array_rand(str_split($set))];
                $all .= $set;
        }

        $all = str_split($all);

        for($i = 0; $i < $length - count($sets); $i++)
                $password .= $all[array_rand($all)];

        $password = str_shuffle($password);

        if(!$add_dashes)
                return $password;

        $dash_len = floor(sqrt($length));
        $dash_str = '';

        while(strlen($password) > $dash_len)
        {
                $dash_str .= substr($password, 0, $dash_len) . '-';
                $password = substr($password, $dash_len);
        }

        $dash_str .= $password;

        return $dash_str;
}


// FUNCAO ENVIA EMAIL
// https://github.com/PHPMailer/PHPMailer para orientacoes de uso
function smtpMailer($password) {
	global $error;

	//Server settings
	$mail = new PHPMailer();
	$mail->IsSMTP();					// Ativar SMTP
	$mail->SMTPDebug = 2;					// Debug: 0=OFF, 1=CLIENT, 2=SERVER, 3=CONNECTION, 4=LOWLEVEL
	$mail->Host = 'smtp.gmail.com';				// SMTP utilizado
	$mail->SMTPAuth = true;					// Autenticação ativada
	$mail->Username = "noreplyaddtechnologia@gmail.com";	// Conta de autenticacao
	$mail->Password = "SENHA";				// Password gerado por 2FA > Password APPS
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;	// Requerido pelo GMail
	$mail->Port = 587;  					// A porta 587 deverá estar aberta em seu servidor

	//Recipients
	$mail->SetFrom("noreplyaddtechnologia@gmail.com");
	$mail->AddAddress("alexandrob@outlook.com");		// Para adicionar mais um destinatario repita a linha e ajuste o endereço
	$mail->AddAddress("alexandrob77@gmail.com");

	//Content
	$mail->IsHtml(true);
	$mail->Subject = "Nova senha da rede WIFI-GUEST";
	$mail->Body = "
	<html>
	   <head><title>Senha WIFI-GUEST</title></head>
           <body>
	      <p>A senha da rede  <span style=\"color: rgb(23, 78, 134);\"><b><font size=\"4\">WIFI-GUEST</font></b></span> foi atualizada para:</p>
	      <p style=\"font-family:'Courier New'; font-size:50px\"><span style=\"color: rgb(23, 78, 134);\"><b>$password</b></span></p>
	   </body>
	</html>";

	if(!$mail->Send()) {
		$error = 'Mail error: '.$mail->ErrorInfo;
		return false;
	} else {
		$error = 'Mensagem enviada!';
		return true;
	}
}
if (!empty($error)) echo $error;



/**
 * provide feedback in json format
 */
echo json_encode($results, JSON_PRETTY_PRINT);
