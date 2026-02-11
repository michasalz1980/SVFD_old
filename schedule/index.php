<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Slim\Factory\AppFactory;
    use \RedBeanPHP\R as R;

    require 'vendor/autoload.php';
    $config = include 'config.php';

    // Database configuration
    $dbConfig = $config['database'];        
    R::setup(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}",
        $dbConfig['username'],
        $dbConfig['password']
    );

    $app = AppFactory::create();

    // Set base path if your application is in a subdirectory
    $app->setBasePath('/schedule');

    // Add Error Middleware
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);

    $app->post('/api/register', function (Request $request, Response $response, $args) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $user = json_decode($request->getBody()->getContents(), true);
        $id = registerUser($user);
        
        if ($id != null) {
            $_SESSION['id'] = $id;
            $aJSON = ['timestamp' => time(), 'status' => 'SUCCESS'];
            $response->getBody()->write(json_encode($aJSON));
        } else {
            $aJSON = ['status' => 'FAILURE'];
            $response->getBody()->write(json_encode($aJSON));
            return $response->withStatus(400);
        }
    
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $app->post('/api/login', function (Request $request, Response $response, $args) {
        $user = json_decode($request->getBody()->getContents(), true);
        $aoResult = ['status' => 'FAILURE', 'id' => '0'];
    
        if (isset($user["username"]) && isset($user["password"])) {
            $aoUser = R::getAll('SELECT * FROM user WHERE username = ? AND password = ? LIMIT 1', [$user["username"], $user["password"]]);
    
            if (!empty($aoUser)) {
                $oUser = $aoUser[0];
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['id'] = $oUser['id'];
                $aoResult = ['status' => 'Success', 'id' => $oUser['id'], 'type' => $oUser['type']];
                $date = new DateTime();
                $sDate = $date->format('Y-m-d H:i:s');
                R::exec('UPDATE user SET lastlogin = ? WHERE id = ?', [$sDate, $aoResult['id']]);
            }
        }
    
        $response->getBody()->write(json_encode($aoResult));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(empty($aoUser) ? 400 : 200);
    });
    
    $app->get('/api/resetPassword', function (Request $request, Response $response, $args) {
        $user = json_decode($request->getBody()->getContents(), true);
        $aoUser = R::getAll('SELECT * FROM user WHERE username = ? LIMIT 1', [$user["username"]]);
        $username = $aoUser[0]['username'] ?? null;
    
        if ($username) {
            // $link = 'http://personal.freibad-dabringhausen.de/schedule/resetPassword.php?hash=' . md5($username);
            $link = $GLOBALS['config']['baseUrl'] . 'resetPassword.php?hash=' . md5($username);
            var_dump($link);
            $text = 'Klicken Sie bitte auf den folgenden Link, um das Passwort zurückzusetzen: ' . $link;
            sendMail($username, $text);
            $response->getBody()->write('Success');
        } else {
            $response->getBody()->write('User not found');
            return $response->withStatus(404);
        }
        return $response;
    });
    
    $app->get('/api/logout', function (Request $request, Response $response, $args) {
        $config = $GLOBALS['config'];
        $url =  $config['base_url'] . 'content.php';
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Sitzung beenden
        unset($_SESSION['id']);
        session_destroy();
        
        // Weiterleitung zu der gewünschten URL
        return $response
            ->withHeader('Location', $url)
            ->withStatus(302); // 302 Found Statuscode für temporäre Weiterleitung
    });
    
    
    $app->get('/api/load', function (Request $request, Response $response, $args) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $id = $_SESSION['id'];
        if ($id) {
            $workSchedule = R::getAll('SELECT * FROM schedule WHERE user_id = ?', [$id]);
    
            $response->getBody()->write(json_encode($workSchedule));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['status' => 'FAILURE']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    });
    
    $app->post('/api/save', function (Request $request, Response $response, $args) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $id = $_SESSION['id'];
        $body = json_decode($request->getBody()->getContents(), true);
    
        if (!empty($body)) {
            saveSchedule($body, $id);
            $response->getBody()->write(json_encode('Success'));
        } else {
            $response->getBody()->write(json_encode('Failure'));
            return $response->withStatus(400);
        }
    
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $app->post('/api/admin/save', function (Request $request, Response $response, $args) {
        $body = json_decode($request->getBody()->getContents(), true);
    
        if (!empty($body)) {
            saveAdminSchedule($body);
            $response->getBody()->write(json_encode('Success'));
        } else {
            $response->getBody()->write(json_encode('Failure'));
            return $response->withStatus(400);
        }
    
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $app->get('/api/admin/loadAll/{type}', function (Request $request, Response $response, $args) {
        $type = $args['type'];
        $workSchedule = R::getAll('SELECT u.id, u.firstname, u.surname, u.type, s.start_date, s.end_date, s.approved, s.standby 
                                   FROM schedule s 
                                   JOIN user u ON s.user_id = u.id 
                                   WHERE u.type = ? 
                                   ORDER BY s.start_date, u.surname', [$type]);
    
        $response->getBody()->write(json_encode($workSchedule));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $app->post('/api/report', function (Request $request, Response $response, $args) {
        $body = json_decode($request->getBody()->getContents(), true);
        
        if (!empty($body)) {
            createReport($body);
            $response->getBody()->write(json_encode('Success'));
        } else {
            $response->getBody()->write(json_encode('Failure'));
            return $response->withStatus(400);
        }
    
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $app->post('/api/resetPassword', function (Request $request, Response $response, $args) {
        $status = false;
        $body = json_decode($request->getBody()->getContents(), true);
        $user = R::getAll('SELECT * FROM user WHERE username = ? LIMIT 1', [$body['email']]);
    
        if (!empty($user)) {
            $newPassword = "Freibad" . rand(1000, 9999);
            R::exec('UPDATE user SET password = ? WHERE id = ?', [md5($newPassword), $user[0]['id']]);
            sendMail($newPassword, $user);
            $status = true;
        }
    
        if ($status) {
            $response->getBody()->write(json_encode('Success'));
        } else {
            $response->getBody()->write(json_encode('Failure'));
            return $response->withStatus(400);
        }
    
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $app->run();
    
    /* Helpers */
    function sendMail($newPassword, $user) {
        $betreff = "Freibad: Neues Password";
        $from = "From: Freibad Dabringhausen <info@freibad-dabringhausen.de>\n";
        $from .= "Reply-To: info@freibad-dabringhausen.de\n";
        $from .= "Content-Type: text/html\n";
        $text = "Sie können folgendes neues Passwort zum Login benutzen: " . $newPassword;
        return @mail($user[0]['username'], $betreff, $text, $from);
    }
    

    function createReport($to) {
        $aTo = [];
        $config = $GLOBALS['config'];
    
        if ($to["to"] == 'all') {
            $aoUser = R::getAll('SELECT username FROM user WHERE type = ?', [$to["type"]]);
            foreach ($aoUser as $aElement) {
                $aTo[] = $aElement["username"];
            }
        } else if (isset($config['emails'][$to['type']][$to['to']])) {
            $aTo = $config['emails'][$to['type']][$to['to']];
        }

        sendReport($aTo, $to["type"]);
    }

    function sendReport($to, $type) {
        try {
            if (!is_array($to)) {
                throw new Exception("Expected 'to' to be an array, got " . gettype($to));
            }
    
            $date = new DateTime();
            $sDate = $date->format('d-m-Y');
            $file_to_save = dirname(__FILE__) . '/tmp/Dienstplan-' . $sDate . '.pdf';
            
            savePDF($file_to_save, $type);
    
            $attachments = [
                [
                    "path" => $file_to_save,
                    "name" => basename($file_to_save),
                    "type" => mime_content_type($file_to_save) ?: "application/octet-stream"
                ]
            ];
    
            $subject = "[Freibad] Dienstplan vom " . $sDate;
            
            mail_att($to, $subject, "Hallo zusammen, im Anhang befindet sich der Dienstplan. Beste Grüße, das Freibad ORGA Team", $attachments);
        } catch (Exception $e) {
            error_log('Error in sendReport: ' . $e->getMessage());
        }
    }
    
      
    function savePDF($file_to_save, $type) {
        $config = $GLOBALS['config'];
        $url = $config['base_url'] . 'report.php?type=' . $type;
        /*
        $html = file_get_contents($url);
        */

        // Verwenden von cURL zum Abrufen der URL-Inhalte mit Akzeptanz selbstsignierter Zertifikate
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $html = curl_exec($ch);
        
        if ($html === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Fehler beim Laden des Inhalts von ' . $url . ': ' . $error);
        }
        
        curl_close($ch);

        // Überprüfung, ob HTML-Inhalt leer ist
        if (empty($html)) {
            throw new Exception('Die abgerufenen Inhalte von ' . $url . ' sind leer.');
        }

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Datei speichern mit Fehlerbehandlung
        $pdfOutput = $dompdf->output();
        if (file_put_contents($file_to_save, $pdfOutput) === false) {
            throw new Exception('Fehler beim Speichern der PDF-Datei ' . $file_to_save);
        }
    }
    function mail_att($to, $subject, $message, $attachments) {
        $config = $GLOBALS['config'];
        $smtpConfig = $config['smtp'];
        $mail = new PHPMailer(true);
    
        try {
            // SMTP-Konfiguration
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpConfig['username'];
            $mail->Password = $smtpConfig['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // oder $smtpConfig['encryption'] falls variabel
            $mail->Port = $smtpConfig['port'];
    
            // Absender
            $mail->setFrom('info@freibad-dabringhausen.de', 'Freibad Dabringhausen');
            $mail->addReplyTo('info@freibad-dabringhausen.de');
    
            // Empfänger
            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $mail->addAddress($recipient);
                }
            } else {
                $mail->addAddress($to);
            }
    
            // E-Mail-Format
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = '<p>Hallo zusammen,</p><p>anbei findet ihr den aktuellen Dienstplan im PDF-Format.</p><p>Beste Grüße,<br>das Freibad ORGA Team</p>';
            $mail->AltBody = "Hallo zusammen,\n\nanbei findet ihr den aktuellen Dienstplan im PDF-Format.\n\nBeste Grüße,\ndas Freibad ORGA Team";
    
            // Anhänge hinzufügen
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $mail->addAttachment($attachment['path'], $attachment['name']);
                    }
                }
            }
    
            // E-Mail senden
            $mail->send();
            return true;
        } catch (Exception $e) {
            echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
            return false;
        }
    }
    /*
    function mail_att($to, $subject, $message, $anhang) {
        $absender = "Freibad Dabringhausen";
        $absender_mail = "info@freibad-dabringhausen.de";
        $reply = $absender_mail;
        $mime_boundary = "-----=" . md5(uniqid(mt_rand(), 1));
    
        $header  = "From:" . $absender . "<" . $absender_mail . ">\n";
        $header .= "Reply-To: " . $reply . "\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: multipart/mixed;\r\n";
        $header .= " boundary=\"" . $mime_boundary . "\"\r\n";
    
        $content = "This is a multi-part message in MIME format.\r\n\r\n";
        $content .= "--" . $mime_boundary . "\r\n";
        $content .= "Content-Type: text/text charset=\"iso-8859-1\"\r\n";
        $content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $content .= $message . "\r\n";
    
        if (is_array($anhang) && is_array(current($anhang))) {
            foreach ($anhang as $dat) {
                $data = chunk_split(base64_encode($dat['data']));
                $content .= "--" . $mime_boundary . "\r\n";
                $content .= "Content-Disposition: attachment;\r\n";
                $content .= "\tfilename=\"" . $dat['name'] . "\";\r\n";
                $content .= "Content-Length: " . $dat['size'] . ";\r\n";
                $content .= "Content-Type: " . $dat['type'] . "; name=\"" . $dat['name'] . "\"\r\n";
                $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $content .= $data . "\r\n";
            }
            $content .= "--" . $mime_boundary . "--";
        } else {
            $data = chunk_split(base64_encode($anhang['data']));
            $content .= "--" . $mime_boundary . "\r\n";
            $content .= "Content-Disposition: attachment;\r\n";
            $content .= "\tfilename=\"" . $anhang['name'] . "\";\r\n";
            $content .= "Content-Length: " . $anhang['size'] . ";\r\n";
            $content .= "Content-Type: " . $anhang['type'] . "; name=\"" . $anhang['name'] . "\"\r\n";
            $content .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $content .= $data . "\r\n";
        }
    
        $sTo = implode(", ", $to);
 
        return @mail($sTo, $subject, $content, $header);
    }
    */
    function registerUser($oUser) {
        $aoUser = R::getAll('SELECT * FROM user WHERE username = ? LIMIT 1', [$oUser["username"]]);
        
        if (count($aoUser) == 0) {
            $user = R::dispense('user');
            $user->username = $oUser['username'];
            $user->password = $oUser['password'];
            $user->firstname = $oUser['firstname'];
            $user->surname = $oUser['surname'];
            $user->type = $oUser['type'];
            $date = new DateTime();
            $sDate = $date->format('Y-m-d H:i:s');
            $user->lastlogin = $sDate;
            $user->created = $sDate;
            $id = R::store($user);
            return $id;
        } else {
            return null;
        }
    }
    
    function saveSchedule($aSchedule, $id) {
        $aEntries = [];
        $config = $GLOBALS['config'];
        $mailStr = "<ol>";
        foreach ($aSchedule as $aElement) {
            $dbEntry = R::getAll('SELECT * FROM schedule WHERE user_id = ? AND start_date = ? AND end_date = ? LIMIT 1', [$id, $aElement[0], $aElement[1]]);
            $dbId = null;
            if (empty($dbEntry)) {
                $schedule = R::dispense('schedule');
                $schedule->userId = $id;
                $schedule->startDate = $aElement[0];
                $schedule->endDate = $aElement[1];
                $schedule->approved = 'false';
                $schedule->standby = 'false';
                $dbId = R::store($schedule);
                $mailStr .= "<li>" . $aElement[0] . " - " . $aElement[1] . " (Neu)</li>";
            } else {
                $dbId = $dbEntry[0]['id'];
            }
            $aEntries[] = $dbId;
        }
        $mailStr .= "</ol>";
        $idString = implode(", ", $aEntries);
        $dbString = 'DELETE FROM schedule WHERE standby = "false" AND user_id = ? AND id NOT IN (' . $idString . ')';
        R::exec($dbString, [$id]);
    
        $user = R::getAll('SELECT surname, firstname, type FROM user WHERE id = ? LIMIT 1', [$id]);
        $type = $user[0]['type'];
        $name = $user[0]['firstname'] . " " . $user[0]['surname'];
        $aTo = null;
        if (isset($config['emails'][$type])) {
            $aTo = $config['emails'][$type]['admin'] ?? [$config['default_email']];
        } else {
            $aTo = [$config['default_email']];
        }
        
        $betreff = "Freibad: Dienstplanänderung";
        $from = "From: Freibad Dabringhausen <info@freibad-dabringhausen.de>\n";
        $from .= "Reply-To: info@freibad-dabringhausen.de\n";
        $from .= "Content-Type: text/html\n";
        $text = "Die Angaben im Dienstplan haben sich für " . $name . " geändert" . $mailStr;
        $sTo = implode(", ", $aTo);
        return @mail($sTo, $betreff, $text, $from);
    }
    
    function saveAdminSchedule($aSchedule) {
        $type = $aSchedule['type'];
        R::exec('UPDATE schedule s, user u SET s.approved = "false" WHERE s.user_id = u.id AND u.type = ?', [$type]);
        foreach ($aSchedule['active'] as $aElement) {
            R::exec('UPDATE schedule SET approved = "true" WHERE user_id = ? AND start_date = ? AND end_date = ?', [$aElement[2], $aElement[0], $aElement[1]]);
        }
    
        R::exec('DELETE FROM schedule WHERE standby = "true" AND user_id IN (SELECT id FROM user WHERE type = ?)', [$type]);
        foreach ($aSchedule['standby'] as $aElement) {
            R::exec('REPLACE INTO schedule SET user_id = ?, start_date = ?, end_date = ?, approved = ?, standby = ?',
                [$aElement[2], $aElement[0], $aElement[1], "false", "true"]);
        }
    }
    R::close();
