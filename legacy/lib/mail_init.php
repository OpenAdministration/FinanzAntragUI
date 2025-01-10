<?php

use framework\auth\AuthHandler;
use framework\MailHandler;

?>
    <!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title><?php echo URIBASE; ?> - Mail Test</title>
		<style type="text/css">
			html, body {
				height: 100%;
				width: 100%;
				margin: 0;
				padding: 0;
				overflow: auto;
			}
			.monospace {
				font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace;
			}
			.box {
				border: 1px solid #ddd;
				border-radius: 5px;
				margin: 5px;
				width: 95%;
				width: calc(100% - 10px);
				box-sizing: border-box;
				padding: 5px;
			}
			.wrap {
				white-space: pre-wrap;
				word-break: break-all;
				word-wrap: break-word;
			}
			.red {
				color: red;
			}
			.logging p {
				margin-top: 1px;
			}
		</style>
		<link rel="stylesheet" type="text/css" href="<?php echo URIBASE.'css/logging.css'; ?>" media="screen,projection">
	</head>
	<body>
<?php
// install - secret key
if (! file_exists(SYSBASE.'/secret.php') || filesize(SYSBASE.'/secret.php') === 0) {
    CryptoHandler::new_protected_key_to_file(SYSBASE.'/secret.php', URIBASE);
}

// show encrypted password =============================================
echo '<div class="box monospace wrap">'."\n";
echo '<strong>Mail Passwort encrypted:</strong><br>';
echo '<span><i class="red">'.((MailHandler::encryptPassword()) ?: 'Empty').'</i></span>';
echo "\n</div>\n";

// test connection  ===============================================
echo '<h3>SMTP Debugging</h3>';

function htmlLogLine($text, $extra_empty = false, $bold = false, $extra_tab_space = 0)
{
    if ($bold) { // add tab space before text
        $text = '<strong>'.$text.'</strong>';
    }
    if ($extra_tab_space > 0) { // add tab space before text
        $text = str_repeat('<span class="tab"></span>', $extra_tab_space).$text;
    }
    echo '<p class="logline"><i>'.$text.'</i></p>';
    if ($extra_empty) {
        echo '<p class="logline"><i></i></p>';
    }
}

$mh = MailHandler::getInstance();
echo '<div class="box wrap">'."\n";
echo '<div class="logging">'."\n";
// run smtp test ----------------------------------
MailHandler::smtpdebug(function ($t, $e = false, $b = false, $s = 0) {
    htmlLogLine($t, $e, $b, $s);
});
echo "</div>\n";
echo "</div>\n";

// test email ===============================================
$auth = (AUTH_HANDLER);
/* @var $auth AuthHandler */
$auth = $auth::getInstance();
if ($auth->getUserMail() !== '') {
    $mail_address = $auth->getUserMail();
} else {
    $mail_address = 'ref-if@tu-ilmenau.de';
}

$tMail = [];
$tMail['to'][] = $mail_address;
$tMail['param']['msg'][] = 'Dies ist eine Testmail<br>Link zum %Finanztool%';
$tMail['param']['link']['Finanztool'] = BASE_URL.URIBASE;
$tMail['param']['headline'] = 'Testmail';
$tMail['subject'] = 'Stura-Finanzen: Testnachricht';
$tMail['template'] = 'projekt_default';

echo '<div class="box monospace wrap">'."\n";
echo '<strong>Send Testmail to "'.$mail_address.'" ...:</strong><br>';
$mail_result = $mh->easyMail($tMail);
echo "\n<br><span class='red'>".($mail_result ? 'OK' : 'ERROR')."</span>\n";
echo "</div>\n";
?>
	</body>
</html>
<?php exit(); ?>