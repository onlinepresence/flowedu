<?php
    use PHPMailer\PHPMailer\PHPMailer;

    /**
     * This gets the system default mail users
     * @param string $name The name of the sender
     * @return string
     */
    function get_default_email(string $name) :string{
        // if its the local development server, use local email
        if(env("APP_ENV") == "local")
            return "successinnovativehub@gmail.com";

        switch(strtolower($name)){
            case "shsdesk":
                $response = "sysadmin@shsdesk.com"; break;
            case "customer care":
                $response = "customercare@shsdesk.com"; break;
            default:
                $response = "sysadmin@shsdesk.com";
        }

        return $response;
    }

    /**
     * This sends an email to someone
     * @param string $message The message body
     * @param string $subject The message subject
     * @param string $receipients The receipient email
     * @param ?string $sender Default is from the default account
     * @param ?string $name The name of the sender
     * @param string|false $reply Provide an email for replies, or set to true if replies should be sent to sender email
     * @return bool|string
     */
    function send_email(string $message, string $subject, string|array $receipients, 
        ?string $sender = null, ?string $name = null, string|bool $reply = false
    ) :bool|string {
        global $rootPath, $mailserver_email, $mailserver_password, $mailserver;

        // require the phpmailer
        require_once "$rootPath/phpmailer/src/Exception.php";
        require_once "$rootPath/phpmailer/src/PHPMailer.php";
        require_once "$rootPath/phpmailer/src/SMTP.php";

        $mail = new PHPMailer(true);

        try {
            if(is_null($sender)){
                $name = "SHSDesk";
                $sender = get_default_email($name);
            }elseif(is_null($name) && validate_email($sender)){
                $name = explode("@", $sender)[0] ?? "No Name";
            }elseif(!validate_email($sender)){
                throw new Exception("Sender mail is invalid");
            }

            // turn recipients to array
            if(!is_array($receipients)){
                $receipients = [$receipients];
            }

            //Server settings
            $mail->isSMTP();
            $mail->Host       = $mailserver;
            $mail->SMTPAuth   = true;
            $mail->Username   = $mailserver_email;
            $mail->Password   = $mailserver_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
            $mail->Port       = 465;
            
            if($reply){
                $reply = $reply === true ? $sender : $reply;
                $mail->AddReplyTo($reply, $name ? $name : "");
            }
            
            $mail->setFrom($sender, $name ?? "");

            // add recipient(s)
            foreach($receipients as $recipient){
                if(is_array($recipient)){
                    if(!isset($recipient["email"]) && !isset($receipient["name"])){
                        throw new Exception("Recipient array should have 'name' and 'email' only");
                    }

                    $mail->addAddress($recipient["email"], $recipient["name"]);
                }else{
                    $mail->addAddress($recipient);
                }
            }
    
            $mail->isHTML();                                  // Set email format to HTML
    
            $mail->Subject = $subject;
            $mail->Body = htmlwrap($message);
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients. '.$message;
            
            $response = $mail->send();
        } catch (\Throwable $th) {
            // $response = 'Message could not be sent. Mailer Error: '. $mail->ErrorInfo;
            $response = $mail->ErrorInfo ? "Mailer Error: ".$mail->ErrorInfo : throwableMessage($th);
        }

        return $response;
    }

    /**
     * Used to validate if a group of recipients are email addresses. Used for emailing
     * @param string|array $receipients The recipient data
     * @param null|mixed $message The message to be sent if there is an error
     * @return bool true if everything is fine or false if otherwise
     */
    function validate_email(string|array $receipients, &$message = null){
        $isValid = true;

        if(!is_array($receipients)){
            $receipients = [$receipients];
        }

        foreach($receipients as $recipient){
            if(!filter_var(trim($recipient), FILTER_VALIDATE_EMAIL)){
                $isValid = false;
                $message = "'$recipient' is not a valid email";
                break;
            }
        }

        return $isValid;
    }

    /**
     * Wraps a message in html for emails
     * @param string $message The message text
     * @return string
     */
    function htmlwrap(string $message) :string{
        if(str_contains($message, "<style>")){
            $response = $message;
        }else{
            $response = <<<EOD
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.5; }
                            h1 { font-family: 'Times New Roman', serif; font-size: 24px; color: #333; }
                            p { font-family: Georgia, serif; font-size: 16px; color: #555; }
                            .button {
                                display: inline-block;
                                padding: 10px 20px;
                                font-size: 16px;
                                font-weight: bold;
                                color: #ffffff;
                                background-color: #28a745;
                                text-decoration: none;
                                border-radius: 5px;
                            }
                            .container {
                                font-family: Arial, sans-serif;
                                padding: 20px;
                                background: #f9f9f9;
                                border: 1px solid #ddd;
                                border-radius: 10px;
                                text-align: center;
                                width: 80%;
                                margin: auto;
                            }
                        </style>
                    </head>
                    <body>
                        $message
                    </body>
                    </html>
                    EOD;
        }
        
        return $response;
    }

    /**
     * This creates a verification token for the verification link
     * @param array The user data
     * @return string
     */
    function create_verification_signature($user){
        list("id" => $user_id) = $user;
        $secret_key = get_user_secret($user_id);

        // link should be valid for 1hr
        $expires = time() + 3600;
        $data = "$user_id|$expires";

        return hash_hmac("sha256", $data, $secret_key)."|".base64_encode($data);
    }

    /**
     * Creates some mail templates that can be used 
     * @return void|false
     */
    function send_verification_email($user = null) {
        $user = $user ?? user(); // Get user details
        if (!$user || empty($user['email'])) {
            if(!($user = user(true))){
                return false;
            }
        }
    
        // Generate verification link
        $verification_link = url("verify-email/".create_verification_signature($user));
        
        // Email subject & headers
        $subject = "Verify Your Email - Account Created";
        $company = "SHSDesk";

        if(!empty($logo = school()["logo"])){
            $header = "
                <div class=\"header\">
                    <img src=\"".url($logo)."\" alt=\"Company Logo\">
                </div>
            ";
        }else{
            $header = "";
        }
    
        // Email body with a verification button
        $message = "
            <html lang=\"en\">
                <head>
                    <title>Email Verification</title>
                    <style>
                        body {font-family: Arial, sans-serif;background-color: #f4f4f4;margin: 0;padding: 0;}
                        .container {max-width: 600px;margin: 0 auto;background-color: #ffffff;padding: 20px;border-radius: 8px;box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);}
                        .header {text-align: center;padding: 10px 0;}
                        .header img {width: 100px;}
                        .content {text-align: center;padding: 20px;}
                        .content h1 {color: #333333;}
                        .content p {color: #666666;line-height: 1.6;}
                        .button {display: inline-block;padding: 10px 20px;margin: 20px 0;background-color: #28a745;color: #ffffff;text-decoration: none;border-radius: 5px;}
                        .footer {text-align: center;padding: 10px 0;color: #999999;font-size: 12px;}
                    </style>
                </head>
                <body>
                    <div class=\"container\">
                        $header
                        <div class=\"content\">
                            <h1>Verify Your Email Address</h1>
                            <p>Hi there!</p>
                            <p>Thank you for signing up. Please click the button below to verify your email address and complete your registration.</p>
                            <a href=\"$verification_link\" class=\"button\" target=\"_blank\">Verify Email</a>
                            <p>If you did not sign up for this account, you can ignore this email.</p>
                        </div>
                        <div class=\"footer\">
                            <p>&copy; ".date("Y")." $company. All rights reserved.</p>
                        </div>
                    </div>
                </body>
            </html>
        ";
    
        // Send email
        add_job("email", create_payload("send_email", [
            "message" => $message, "subject" => "Verify your email",
            "receipients" => $user["email"]
        ]));
    }

    /**
     * Sends an account creation confirmation email to the user
     * @param string $email The recipient's email address
     * @param ?array $details Other details to send with the mail
     * @return void|false
     */
    function send_account_created_email(string $email, ?array $details = null) {
        if (empty($email)) {
            return false;
        }

        // Email subject
        $subject = "Welcome to CollegeSchool - Account Created Successfully";
        $company = "SHSDesk";
        $login_url = url("/");

        // Get school logo if available
        if (!empty($logo = school()["logo"])) {
            $header = "
                <div class=\"header\">
                    <img src=\"" . url($logo) . "\" alt=\"Company Logo\">
                </div>
            ";
        } else {
            $header = "";
        }

        // Build extra details if provided
        $extra_details_html = "";
        if (!empty($details)) {
            $details_html = "";
            foreach ($details as $key => $value) {
                // Clean up key (replace underscores/dashes with spaces, capitalize words)
                $label = ucwords(str_replace(['_', '-'], ' ', $key));
                $details_html .= "<tr><td style='padding:5px 10px; font-weight:bold; text-align:left;'>$label:</td><td style='padding:5px 10px; text-align:left;'>$value</td></tr>";
            }

            $extra_details_html = "
                <div style='margin-top:20px; text-align:left;'>
                    <h3 style='color:#333;'>Account Details</h3>
                    <table style='width:100%; border-collapse:collapse;'>
                        $details_html
                    </table>
                </div>
            ";
        }

        // Email body
        $message = "
            <html lang=\"en\">
                <head>
                    <title>Account Created</title>
                    <style>
                        body {font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;}
                        .container {max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);}
                        .header {text-align: center; padding: 10px 0;}
                        .header img {width: 100px;}
                        .content {text-align: center; padding: 20px;}
                        .content h1 {color: #333333;}
                        .content p {color: #666666; line-height: 1.6;}
                        .button {display: inline-block; padding: 10px 20px; margin: 20px 0; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 5px;}
                        .footer {text-align: center; padding: 10px 0; color: #999999; font-size: 12px;}
                    </style>
                </head>
                <body>
                    <div class=\"container\">
                        $header
                        <div class=\"content\">
                            <h1>Welcome to $company!</h1>
                            <p>Hello there!</p>
                            <p>Your account has been successfully created. You can now log in to complete your profile setup and explore all the features we offer.</p>
                            <a href=\"$login_url\" class=\"button\" target=\"_blank\">Login to Your Account</a>
                            $extra_details_html
                            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                        </div>
                        <div class=\"footer\">
                            <p>&copy; " . date("Y") . " $company. All rights reserved.</p>
                        </div>
                    </div>
                </body>
            </html>
        ";

        // Send email using job queue
        add_job("email", create_payload("send_email", [
            "message" => $message,
            "subject" => $subject,
            "receipients" => $email
        ]));
    }

    /**
     * This is used for email verification 
     * @return array
     */
    function verify_email() :array{
        global $params;
        
        if($params){
            list($token) = $params;
            $token = urldecode($token);
            if(str_contains($token, "|")){
                list($signature, $data) = explode("|", $token, 2);
                list($id, $expiry) = explode("|",base64_decode($data));

                if($secret_key = get_user_secret($id)){
                    $user = get_user($id, "id, type, username, email_verified_at");

                    if(time() > $expiry){
                        $icon = "fas fa-stopwatch text-yellow-500";
                        $title = "Expired Link";
                        $message = "The link has expired. Please take note that the link has a lifespan of one hour";
                        $state = "link_expired";
                    }elseif(!hash_equals(hash_hmac("sha256", "$id|$expiry", $secret_key), $signature)){
                        $icon = "fas fa-exclamation-triangle text-orange-500";
                        $message = "Invalid signature was provided. Please use the orignal link given you";
                        $title = "Invalid Signature";
                        $state = "invalid_signature";
                    }elseif(!empty($user["email_verified_at"])){
                        $icon = "fas fa-info-circle text-blue-500";
                        $title = "Link Used";
                        $message = "Email already verified! You can proceed to your account.";
                        $state = "aready_verified";
                    }else{
                        update($user, ["email_verified_at" => now()], "users", ["id"]);
                        $icon = "fas fa-check-circle text-green-500";
                        $title = "Email Verified";
                        $message = "Your email has been verified successfully";
                        $state = "success";
                    }
                }
            }
        }

        return [
            "icon" => $icon ?? "fas fa-user-slash text-red-500", 
            "message" => $message ?? "User information could not be found or link is invalid", 
            "status" => $state ?? "no_user",
            "title" => $title ?? "Invalid Link",
            "username" => $user["username"] ?? "there",
            "user_type" => $user["type"] ?? ""
        ];
    }

