<?php
session_start();
if (isset($_SESSION['name'])) {
    $text = isset($_POST['text']) ? $_POST['text'] : '';
    
    // Ako je poruka prazna, odmah prekidamo izvršavanje
    if (strlen(trim($text)) < 1) { 
        return; 
    }

    $log_file = "log.html";
    
    // Kreiranje HTML strukture za novu poruku
    // Dodat razmak pre stripslashes da se tekst odvoji od imena korisnika
    $text_message = "<div class='msgln'><span class='chat-time'>".date("H:i")."</span><b class='user-name'>".$_SESSION['name']."</b>".stripslashes(htmlspecialchars($text))."</div>\n";

    // 1. Otvaramo fajl samo JEDNOM za čitanje i pisanje
    $fp = fopen($log_file, "c+"); // "c+" otvara fajl bez brisanja i stavlja kursor na početak

    if ($fp) {
        // 2. Ekskluzivno zaključavamo fajl (niko drugi ne može da piše u isto vreme)
        if (flock($fp, LOCK_EX)) {
            
            // 3. Čitamo trenutni sadržaj fajla ako fajl ima podatke
            $content = "";
            if (filesize($log_file) > 0) {
                $content = fread($fp, filesize($log_file));
            }

            // 4. Dodajemo novu tekstualnu poruku na kraj postojećeg sadržaja u memoriji
            $content .= $text_message;

            // 5. Brojimo i skraćujemo poruke pomoću regularnog izraza (isto kao pri logovanju)
            if (preg_match_all('/<div class=\'msgln\'>.*?<\/div>/s', $content, $matches)) {
                $all_messages = $matches[0];
                
                // Ako u memoriji imamo više od 120 poruka, zadržavamo samo poslednjih 100
                if (count($all_messages) > 120) {
                    $trimmed_messages = array_slice($all_messages, -100);
                    $content = implode("", $trimmed_messages);
                }
            }

            // 6. Vraćamo kursor na sam početak fajla da bismo prepisali sadržaj
            rewind($fp);

            // 7. Upisujemo novi ažurirani i skraćeni sadržaj čata
            fwrite($fp, $content);

            // 8. Brišemo eventualni višak karaktera sa kraja fajla na disku
            ftruncate($fp, strlen($content));

            // 9. Otključavamo fajl
            flock($fp, LOCK_UN);
        }
        
        // 10. Zatvaramo fajl
        fclose($fp);
    }
}
?>