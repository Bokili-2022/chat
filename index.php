<?php
session_start();

// Ako je zahtev stigao preko Beacona
if (isset($_GET['logoutBeacon'])) {
    // Prvo upisujemo u log dok podaci u sesiji još postoje
    if (isset($_SESSION['name'])) {
        $logout_message = "<div class='msgln'><span class='left-info'><span class='chat-time'>".date("H:i")."</span><b class='user-name-left'>". $_SESSION['name'] ."</b>left the chat.</span><br></div>";
        file_put_contents("log.html", $logout_message, FILE_APPEND | LOCK_EX);
    }
    //session_destroy();
    exit(); // Beacon zahtev se ovde završava i ne ide dalje u kod
}

// Regularan logout na dugme
if (isset($_GET['logout'])) {    
    // Prvo log, pa onda destroy
    if (isset($_SESSION['name'])) {
        $logout_message = "<div class='msgln'><span class='left-info'><span class='chat-time'>".date("H:i")."</span><b class='user-name-left'>". $_SESSION['name'] ."</b>logged out.</span><br></div>";
        file_put_contents("log.html", $logout_message, FILE_APPEND | LOCK_EX);
    }
    session_destroy();
    header("Location: ./"); 
    exit();
}

$errMsg="";
if(!isset($_SESSION['name']) && isset($_POST['enter']) && isset($_POST['name'])){
    if($_POST['name'] != ""){
        $_SESSION['name'] = stripslashes(htmlspecialchars($_POST['name']));
		$_SESSION['info'] = "y";
		// Postavi kolačić za naziv korisničkog imena
		setcookie('last_chat_user', $_SESSION['name'], [
			'expires' => time() + 90*24*3600, 
			'path' => '/', 
			'httponly' => true,
			'samesite' => 'Lax' // Dodatna moderna zaštita za kolačiće
		]);
        $login_message = "<div class='msgln'><span class='join-info'><span class='chat-time'>".date("H:i")."</span><b class='user-name'>". $_SESSION['name'] ."</b>joined the chat.</span><br></div>";
        $log_file = "log.html";
        // 1. Otvaramo fajl samo JEDNOM za čitanje i pisanje
        $fp = fopen($log_file, "c+"); // "c+" otvara fajl bez brisanja sadržaja i postavlja kursor na početak
        if ($fp) {
            // 2. Ekskluzivno zaključavamo fajl da drugi korisnici ne bi pisali u isto vreme
            if (flock($fp, LOCK_EX)) {
                // 3. Čitamo trenutni sadržaj fajla ako fajl nije prazan
                $content = "";
                if (filesize($log_file) > 0) {
                    $content = fread($fp, filesize($log_file));
                }
                // 4. Dodajemo novu poruku na stari sadržaj u memoriji
                $content .= $login_message;
                // 5. Proveravamo broj poruka pomoću vašeg regularnog izraza
                if (preg_match_all('/<div class=\'msgln\'>.*?<\/div>/s', $content, $matches)) {
                    $all_messages = $matches[0];
                    
                    if (count($all_messages) > 120) {
                        // Uzimamo samo poslednjih 100 poruka ako ih ima više od 120
                        $trimmed_messages = array_slice($all_messages, -100);
                        $content = implode("", $trimmed_messages);
                    }
                }
                // 6. Vraćamo kursor na početak fajla da bismo prepisali sadržaj
                rewind($fp);
                // 7. Upisujemo novi (ili skraćeni) sadržaj
                fwrite($fp, $content);
                // 8. Odsecamo ostatak starog fajla ako je novi sadržaj kraći od starog
                ftruncate($fp, strlen($content));
                // 9. Otključavamo fajl
                flock($fp, LOCK_UN);
            }
            // 10. Zatvaramo fajl
            fclose($fp);
			header("Location: ./"); //Redirect the user
			exit();
        }
    } else {
		$errMsg='<span class="error">Please type in a name</span>';
    }
} else if(isset($_SESSION['name']) && !isset($_SESSION['info'])){
	$reload_message = "<div class='msgln'><span class='join-info'><span class='chat-time'>".date("H:i")."</span><b class='user-name'>". $_SESSION['name'] ."</b>reconnected.</span><br></div>";
	file_put_contents("log.html", $reload_message, FILE_APPEND | LOCK_EX);
	//usleep(500000);
} else if (isset($_SESSION['name']) && isset($_SESSION['info'])){
	unset($_SESSION['info']);
}

 
?><!--
 * Application: Chat Application
 * Version: 2.1.1
 * Author: Bojan Radovic <bojan.ks@gmail.com>
 * Copyright (c) 2026 Bokili Production
 * License: MIT (or "All Rights Reserved" if it's private)
-->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
		<meta name="robots" content="noindex">
		<meta name="robots" content="noindex, nofollow">
		<title>Chat Application</title>
        <meta name="description" content="Chat Application" />
		
		<!-- 1. Prvo stavljate moderne PNG ikonice za nove pretraživače i mobilne -->
		<link rel="icon" type="image/png" sizes="16x16" href="./icons/favicon-16x16.png">
		<link rel="icon" type="image/png" sizes="32x32" href="./icons/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="48x48" href="./icons/favicon-48x48.png">
		<link rel="icon" type="image/png" sizes="192x192" href="./icons/icon-192x192.png">
		<link rel="icon" type="image/png" sizes="512x512" href="./icons/icon-512x512.png">
		<!-- 2. Mobilni sistemi (iOS i Android manifest) -->
		<link rel="apple-touch-icon" sizes="180x180" href="./icons/apple-touch-icon.png">
		<link rel="manifest" href="./manifest.json">
		<!-- 3. Na samom kraju ostavljate vaš stari .ico kao "back-up" (fallback) -->
		<link rel="shortcut icon" href="./favicon.ico">
		
		<meta property="og:image" itemprop="image" content="<? echo "http".(isset($_SERVER["HTTPS"]) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . "/chat/_og_image.jpg"; ?>"/>
		<meta property="og:title" content="Chat Application - Login page"/>
		<meta property="og:url" content="<? echo "http".(isset($_SERVER["HTTPS"]) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>"/>
		<meta property="og:description" content="Free mini chat app for fast connection on any platform. Just type in your username."/>
		<meta property="og:type" content="website"/>
		
        <link rel="stylesheet" href="./style.css" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    </head>
    <body>
    <?php
	// Postavi korisničko ime sačuvano u kolačićima
	$prefillUser = $_COOKIE['last_chat_user'] ?? '';
    if(!isset($_SESSION['name'])){
        echo
    '<div id="loginform">
	<img src="./chat app main.png" alt="Chat App Main" class="main-banner">
    <p>Please enter your name to continue!</p>
	'.$errMsg.'
    <form action="" method="post">
      <label for="name">Name:</label>
      <input type="text" name="name" id="name" value="'. htmlspecialchars($prefillUser, ENT_QUOTES) .'" autofocus />
      <input type="submit" name="enter" id="enter" value="Enter" />
    </form>
  </div>';
    }else{
    ?>
        <div id="wrapper">
			<img src="./chat app main.png" alt="Chat App Main" class="main-banner" id="main_banner">
            <div id="menu">
                <p class="welcome">Welcome, <b><?php echo $_SESSION['name']; ?></b></p>
                <a id="fs-button" href="javascript:toggleFullScreen();" alt="Set fullscreen" title="Set fullscreen">¤</a>
				&emsp;<a id="exit" href="javascript:void(0);" alt="Exit" title="Exit">Exit Chat</a>
            </div>
 
            <div id="chatbox">
            
            </div>
 
            <form name="message" action="">
                <input name="usermsg" type="text" id="usermsg" />
                <input name="submitmsg" type="submit" id="submitmsg" value="Send" />
            </form>
        </div>
		<div style="display:none;">
			<audio preload="auto" id="player">
				<source src="./message.mp3" type="audio/mpeg">
			</audio>
		</div>
        <script src="./script.js" defer></script>
    </body>
</html>
<?php
}
?>
