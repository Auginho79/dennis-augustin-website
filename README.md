# Website Dennis Augustin

Statische Website (reines HTML/CSS/JS, kein Build-Schritt) – bereit für GitHub Pages oder jedes andere Hosting.

## Struktur

```
dennis-augustin-website/
├── index.html                  Startseite (zwei Säulen: PM + Marketing)
├── projektmanagement.html      Projektmanagement / PMO / Prozesse
├── marketing.html              Marketing-Übersicht (verlinkt die 4 Unterseiten)
├── webdesign.html              Marketing-Unterseite
├── seo.html                    Marketing-Unterseite
├── suchmaschinenwerbung.html   Marketing-Unterseite (Google & Meta Ads)
├── contenterstellung.html      Marketing-Unterseite
├── impressum.html              Rechtstext-Vorlage (noindex)
├── datenschutz.html            Rechtstext-Vorlage (noindex)
└── assets/
    ├── styles.css              Zentrales Stylesheet (eine Quelle für die ganze CI)
    ├── main.js                 Header-Scroll, Menü, Animationen, aktiver Menüpunkt
    └── favicon.svg             Favicon (Logo-Punkt)
```

Das CSS und JS liegen **einmal** zentral in `assets/`. Änderungen am Design wirken
damit automatisch auf allen Seiten – nichts muss neunmal angepasst werden.

## Lokal ansehen

Einfach `index.html` im Browser öffnen. Da CSS/JS relativ verlinkt sind,
funktioniert die Seite direkt vom Dateisystem.

## Auf GitHub Pages veröffentlichen

1. Neues Repository anlegen (z. B. `dennis-augustin-website`).
2. Den **Inhalt** dieses Ordners ins Repository-Wurzelverzeichnis legen
   (also `index.html` direkt in der Wurzel, nicht in einem Unterordner).
3. In den Repository-Einstellungen: **Settings → Pages → Source: Deploy from a branch**,
   Branch `main`, Ordner `/ (root)`.
4. Nach kurzer Wartezeit ist die Seite unter der Pages-URL erreichbar.
5. Für die eigene Domain `dennis-augustin.com`: unter **Pages → Custom domain**
   die Domain eintragen und den DNS-Eintrag beim Domain-Anbieter setzen.

Die Datei `.nojekyll` ist enthalten, damit GitHub Pages den `assets`-Ordner
unverändert ausliefert.

## Noch zu erledigen (vor dem Livegang)

- [ ] **Platzhalter ersetzen:** Alle `[ ... ]`-Texte (Portrait-Hinweise, Referenzen, Impressum, Datenschutz).
- [ ] **Echte Fotos** einsetzen (Portrait in den `about`-Abschnitten).
- [ ] **Kontaktformular anbinden:** Die Formulare sind aktuell nur Optik. Für echten
      Versand wird ein Endpunkt benötigt (z. B. Formspree, Netlify Forms, eigenes
      Skript). Auf GitHub Pages gibt es keine Server-Logik.
- [ ] **Rechtstexte finalisieren:** Impressum und Datenschutz sind Vorlagen mit
      Platzhaltern – bitte mit seriösem Generator/Anwalt vervollständigen und prüfen.
- [ ] **Google Fonts self-hosten:** Aktuell werden die Schriften (Fraunces, Hanken
      Grotesk) vom Google-CDN geladen. Für DSGVO-Konformität die Schriften lokal
      einbinden und den Datenschutz-Abschnitt anpassen.
- [ ] **Echte Referenzen** statt der markierten Testimonial-Platzhalter.
- [ ] Optional: eigenes `favicon` (z. B. PNG/ICO zusätzlich zur SVG).

## Technische Hinweise

- Eine `<h1>` pro Seite, saubere Hierarchie `h1 → h2 → h3`.
- Durchgängige „Sie"-Ansprache.
- Skip-Link, `aria`-Attribute am Menü und `prefers-reduced-motion` sind berücksichtigt.
- Rechtsseiten sind auf `noindex` gesetzt.
