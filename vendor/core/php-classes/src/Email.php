<?php 

namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Email {
	public function send($subject,$msg,$file,$address)
	{
		//Create a new PHPMailer instance
		$mail = new PHPMailer;

		//Tell PHPMailer to use SMTP
		$mail->isSMTP();

		//Enable SMTP debugging
		// SMTP::DEBUG_OFF = off (for production use)
		// SMTP::DEBUG_CLIENT = client messages
		// SMTP::DEBUG_SERVER = client and server messages
		$mail->SMTPDebug = SMTP::DEBUG_OFF;

		//Set the hostname of the mail server
		$mail->Host = 'mail.bazeiangola.com';
		// use
		// $mail->Host = gethostbyname('smtp.gmail.com');
		// if your network does not support SMTP over IPv6

		//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
		$mail->Port = 587;

		//Set the encryption mechanism to use - STARTTLS or SMTPS
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        //Whether to use SMTP authentication
		$mail->SMTPAuth = true;

		//Username to use for SMTP authentication - use full email address for gmail
		$mail->Username = 'user name';

		//Password to use for SMTP authentication
		$mail->Password = 'senha';

		//Set who the message is to be sent from
		$mail->setFrom('user name', 'FastMoto');

		//Set an alternative reply-to address
		//$mail->addReplyTo('replyto@example.com', 'First Last');

		//Set who the message is to be sent to
		if(is_array($address)){
			foreach($address as $value){
				$mail->addAddress($value);
			}
		}else{ $mail->addAddress($address); }
		//Set the subject line
		$mail->Subject = $subject;

        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
		$mail->msgHTML($msg, __DIR__);

        //Replace the plain text body with one created manually
        $mail->AltBody = 'This is a plain-text message body';

        //Attach an image file
        $mail->addAttachment($file);

        //send the message, check for errors
        if (!$mail->send()){
            // echo 'Mailer Error: '. $mail->ErrorInfo;
        } else {
                 echo 'Messagem enviada!'; 
    //Section 2: IMAP
    //Uncomment these to save your message in the 'Sent Mail' folder.
    #if (save_mail($mail)) {
    #    echo "Message saved!";
    #}
         }
	}
	

}
 ?>