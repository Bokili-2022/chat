/*!
 * Application: Chat Application - v2.1.1
 * Description: Real-time chat client interface
 *
 * Owned by: Bokili Production
 * Developed by: Bojan Radovic
 * 
 * Copyright (c) 2026. All rights reserved.
 * Licensed under the MIT License.
 */

document.addEventListener("DOMContentLoaded", function () {
	const submitBtn = document.getElementById("submitmsg");
	const userMsgInput = document.getElementById("usermsg");

	submitBtn.addEventListener("click", function (event) {
		event.preventDefault(); // Menja stari "return false;" i sprečava osvežavanje stranice
		const clientmsg = userMsgInput.value;
		// Provera da se ne šalje prazna poruka
		if (clientmsg.trim() === "") return;
		fetch("post.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded"
			},
			body: new URLSearchParams({ text: clientmsg })
		})
		.then(response => {
			if (!response.ok) {
				console.error("Greška pri slanju poruke.");
			} else {
				// Čitamo odgovor sa servera kao običan tekst
				return response.text(); 
			}
		}).then(data => {
			// Pražnjenje polja za unos, i eventualno fokusiranje na element
			userMsgInput.value = "";
			//userMsgInput.focus();
			//submitBtn.focus();
			
			// Prikazujemo dobijeni tekst u alert dijalogu iz post.php odgovora (ako postoji)
			if (data && data.trim() !== "") {
				alert(data);
				window.location.reload();
			}
		}).catch(error => {
			console.error("Mrežni problem:", error);
		});
	});
});

// Održavaj sesiju aktivnom
setInterval(function(){ navigator.sendBeacon("./post.php"); }, 5 * 60 * 1000);

// Audio obaveštenje za nove poruke
function playNewMess(){
	var player=document.getElementById("player");
	player.pause();
	player.currentTime = 0;
	player.play();
}

// Globalna promenljiva koja pamti datum poslednje izmene
let jsLastModified = "";
function checkLogUpdate() {
	const headers = {
		'cache-control': 'no-cache',
		'pragma': 'no-cache'
	};
	// Izvlačimo promenljivu na nivo funkcije da bi joj svi .then() blokovi pristupili
	const isSubsequentLoad = jsLastModified !== "";
	if (jsLastModified) {
		headers['If-Modified-Since'] = jsLastModified;
	}
	fetch(`log.html?_=${new Date().getTime()}`, { 
		method: 'GET',
		cache: 'no-store', 
		headers: headers
	}).then(response => {
		// Ako je status 304, bacamo prekid koji odmah ide u .catch, ali bez logovanja greške
		if (response.status === 304) {
			throw new Error("NOT_MODIFIED");
		}
		if (!response.ok) {
			throw new Error(`Mrežna greška: ${response.status}`);
		}
		const serverLastModified = response.headers.get('Last-Modified');
		if (serverLastModified) {
			jsLastModified = serverLastModified;
		}
		return response.text();
	}).then(html => {
		// Ovaj blok se izvršava samo ako je status bio 200 OK
		const chatbox = document.getElementById("chatbox");
		const urlRegex = /(https?:\/\/[^\s<]+)/g;
		const formattedHtml = html.replace(urlRegex, function(url) {
			return '<a href="' + url + '" target="_blank">' + url + '</a>';
		});
		chatbox.innerHTML = formattedHtml;
		chatbox.scrollTo({
			top: chatbox.scrollHeight,
			behavior: 'smooth'
		});
		// Proveravamo da li treba pustiti zvuk
		if (isSubsequentLoad) {
			playNewMess();
		}
	}).catch(error => {
		// Ako je prekid izazvan statusom 304, ignorišemo ga (to je normalno ponašanje)
		if (error.message === "NOT_MODIFIED") { return; }
		console.error("Problem sa ažuriranjem čat loga:", error);
	});
}

// Pokrećemo provere na svake 3 sekunde
setInterval(checkLogUpdate, 3000);
// Pokreni odmah jednom čim se stranica učita
checkLogUpdate();

// Funkcija koja prilagođava visinu chatbox-a prema visini ekrana, u zavisnosti od ostalih elemenata
function prilagodiVisinuCeta() {
	const banner = document.getElementById("main_banner");
	const chatbox = document.getElementById("chatbox");

	// Proveravamo da li oba elementa postoje na stranici
	if (banner && chatbox) {
		const visinaBanera = banner.offsetHeight;
		
		const rem = parseFloat(window.getComputedStyle(document.documentElement).fontSize);// veličina fonta (u px)
		const remOdbitak = 2.5 * rem; // Gornji tekst(DIV menu) + donji tekst u (usermsg)
		const pxOdbitak = 80; // razni padding + borders + ostalo

		const novaVisinaCeta = window.innerHeight - visinaBanera - remOdbitak - pxOdbitak;
		
		// Direktno upisujemo stil u chatbox element
		chatbox.style.height = novaVisinaCeta + "px";
	}
}
// ⚠️ KLJUČNI MOMENAT: Čekamo da se slika stvarno preuzme sa servera
// Ako merimo sliku pre nego što se učita, visina će joj biti 0px!
window.addEventListener('load', prilagodiVisinuCeta);
// Ako korisnik okrene telefon (iz landscape u portrait) ili promeni veličinu prozora
window.addEventListener('resize', prilagodiVisinuCeta);

// 1. Funkcija koja samo pokreće ili gasi fullscreen
function toggleFullScreen() {
	if (!document.fullscreenElement) {
		document.documentElement.requestFullscreen().catch(err => console.log(err));
	} else {
		document.exitFullscreen();
	}
}
// 2. Čuvar (Listener) koji brine o izgledu ikonice u svakom trenutku
const ikonaFullscreen = '<svg xmlns="http://w3.org" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>';
const ikonaIzlaz = '<svg xmlns="http://w3.org" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14h6v6m10-6h-6v6M4 10h6V4m10 6h-6V4"/></svg>';
document.addEventListener('fullscreenchange', () => {
	let btn = document.getElementById("fs-button");
	btn.innerHTML = document.fullscreenElement ? ikonaIzlaz : ikonaFullscreen;
});
window.addEventListener('keydown', (e) => {
	if (e.key === 'F11') {
		e.preventDefault(); // Sprečava fabrički F11 mod pretraživača
		toggleFullScreen(); // Pokreće tvoju funkciju i tvoj SVG se menja!
	}
});
document.getElementById("fs-button").innerHTML=ikonaFullscreen; // Postavi ikonicu sada

// Izlaz iz čata klikom na dugme
let isUserClickExit = false;
document.getElementById("exit").addEventListener("click", function () {
	var exit = confirm("Are you sure you want to end the session?");
	if (exit == true) {
		isUserClickExit = true;
		window.location = "./?logout=true";
	}
});

// Napuštanje stranice (tab close, F5, refresh button)
window.onbeforeunload = function (e) {
	if(isUserClickExit){ return; }
	navigator.sendBeacon("./?logoutBeacon=true");
    const end = performance.now() + 200;
	// namerno kratko blokiranje 200 ms.
    while (performance.now() < end) { }
};
