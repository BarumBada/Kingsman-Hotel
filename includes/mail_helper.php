<?php
date_default_timezone_set('Asia/Manila');

/**
 * Send email using PHP's native mail() via XAMPP sendmail.
 * XAMPP sendmail.ini is configured with SMTP credentials.
 */
function send_kingsman_mail($to, $subject, $body_html)
{
    $mail_config = require dirname(__DIR__) . '/config/mail.php';

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $mail_config['from_name'] . " <" . $mail_config['from_email'] . ">" . "\r\n";

    $result = @mail($to, $subject, $body_html, $headers);

    if ($result) {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] EMAIL TO: $to | SUBJECT: $subject | STATUS: Sent via PHP mail()\n";
    } else {
        $log_entry = "[" . date('Y-m-d H:i:s') . "] EMAIL TO: $to | SUBJECT: $subject | STATUS: Failed via PHP mail()\n";
    }
    file_put_contents(dirname(__DIR__) . '/mail_ops.log', $log_entry, FILE_APPEND);
    return $result;
}

function get_branded_template($title, $content)
{
    ob_start();
    ?>
    <div
        style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #c5a021; padding: 40px; background-color: #050505; color: #ffffff;">
        <div style="text-align: center; border-bottom: 2px solid #c5a021; padding-bottom: 20px; margin-bottom: 30px;">
            <h1 style="color: #c5a021; letter-spacing: 5px; margin: 0;">KINGSMAN</h1>
            <p style="color: #d0d0d0; font-size: 10px; text-transform: uppercase; letter-spacing: 2px;">Bespoke Hospitality
                Group</p>
        </div>
        <h2 style="color: #c5a021;">
            <?php echo $title; ?>
        </h2>
        <div style="line-height: 1.6; color: #ffffff;">
            <?php echo $content; ?>
        </div>
        <div
            style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #333; font-size: 12px; color: #d0d0d0; text-align: center;">
            &copy;
            <?php echo date('Y'); ?> Kingsman Hotel. All rights reserved.<br>
            A professional guest communication.
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>