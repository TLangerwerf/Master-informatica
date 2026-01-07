## 29-12-2025 Sprint 1

# Inhoud
-Repo en basisstructuur opzetten zodat we volgens een vaste werkwijze


# wireframews gemaakt in UX-UI map.
# UX-UI analyse gemaakt en gedocumenteerd in DOC bestand i.v.m. grafische inhoud.
# Taken in Github ogpesplits i.v.m. de complexe aard van het ontwerp.
# rollen gebruikers omschreven in readme.md

# Sprint 2 technische aanpassingen
1. Homepage herontwerp (grid + previews)
We hebben de homepage aangepast zodat ingezonden websites in een overzichtelijk raster worden weergegeven. Elke site staat in een kaart met een vaste preview van 200×200 pixels (iframe), titel, beschrijving en metadata. De layout is consistent gemaakt met de bestaande UI-stijl.

2. Opschonen van SQL en datamodel-afstemming
We hebben SQL-queries afgestemd op jouw daadwerkelijke database (wrp) en tabellen (sites, reviews, users, etc.). Fouten door verkeerde tabelnamen (wrp_sites) zijn opgelost en joins zijn aangepast aan de echte kolommen zoals class_id en group_id.

3. Login toevoegen aan de homepage
Rechtsboven op de homepage is een loginblok toegevoegd dat past bij de bestaande opmaak. Dit blok toont een loginformulier voor niet-ingelogde gebruikers en een status + logoutknop voor ingelogde gebruikers, gebruikmakend van jouw bestaande auth.php.

4. Reviewknop per website met toegangscontrole
Bij elke websitekaart is naast de knop “Bekijk” een knop “Review” toegevoegd. Deze is alleen actief voor ingelogde studenten en docenten. Voor niet-ingelogde bezoekers is de knop grijs en toont deze de tekst “Log eerst in”.

5. Review overlay (modal) bouwen
De reviewknop opent een overlay waarin een gebruiker een score (1–5) en tekst kan invoeren. De modal is volledig geïntegreerd in de UI, sluitbaar via backdrop, knop of ESC, en gebruikt een apart JavaScript-bestand voor gedrag.

6. JavaScript gesplitst naar assets/scripts/review.js
Alle JavaScript voor het openen, sluiten en vullen van de review-modal is losgetrokken uit index.php en geplaatst in assets/scripts/review.js. De PHP-pagina geeft alleen een eenvoudige data-open flag door voor state-herstel.

7. Reviews opslaan en bijwerken (insert/update)
De review-logica is zo aangepast dat een gebruiker per website maximaal één review heeft. Bestaat er al een review, dan wordt deze geüpdatet; anders wordt er een nieuwe aangemaakt in de tabel reviews met site_id, student_id, score en comment.

8. Bestaande review vooraf invullen bij heropenen
Wanneer een gebruiker opnieuw op “Review” klikt, worden de bestaande score en commentaar automatisch teruggezet in het formulier. Dit gebeurt via extra data-* attributen op de knop en wordt door JavaScript gebruikt om de modal te prefillen.

9. Verbeterde UX van het feedbackscherm
Het feedbackscherm is aangepast met meer schrijfruimte: een bredere modal en een textarea van ongeveer 200px hoog. De knoppen zijn groter gemaakt en de spacing is verbeterd, zodat het schrijven van feedback comfortabeler en duidelijker is.

10. Correct omgaan met POST-state en modal gedrag
We hebben het probleem opgelost waarbij de modal na submit leeg bleef. De oplossing was om form-state (site_id, score, comment) in PHP vast te leggen bij POST en deze opnieuw in het formulier te renderen, zodat de inhoud zichtbaar blijft na opslaan of foutmelding.

# Sprint 3 begin
1. Homepage met website-raster en previews

We hebben de homepage aangepast naar een overzichtelijk grid waarin websites als kaarten worden getoond. Elke kaart bevat een titel, metadata (klas/groep), statistieken en een iframe-preview. De previews zijn compact en lazy-loaded, zodat de pagina snel blijft laden en visueel aantrekkelijk is.

2. Login en rolgebaseerde zichtbaarheid

Op de homepage is een loginblok toegevoegd. Afhankelijk van de rol (leerling of docent) worden functies zichtbaar of verborgen. Docenten zien extra beheerknoppen, terwijl niet-ingelogde gebruikers beperkte interactie hebben, zoals een uitgeschakelde reviewknop met duidelijke melding.

3. Reviewsysteem met overlay (modal)

Er is een reviewsysteem gebouwd waarbij leerlingen en docenten feedback kunnen geven via een overlay. De overlay onthoudt invoer bij fouten, valideert score en commentaar, en ondersteunt zowel nieuwe reviews als het aanpassen van bestaande feedback.

4. Knoppen en UI-stijl (donkerblauw accent)

De volledige UI is gestroomlijnd met een consistente stijl in ui.css. Primaire knoppen kregen een duidelijke donkerblauwe accentkleur, secundaire knoppen een lichtere stijl. Hover- en active-states zorgen voor duidelijke visuele feedback en een professionele uitstraling.

5. Topbar met CSS Grid

De topbar is opnieuw opgebouwd met CSS Grid. Links staat de branding, in het midden een menu met knoppen, en rechts het loginblok. Dankzij grid-template-columns: auto 1fr auto blijft de layout stabiel en logisch, ook bij verschillende schermbreedtes.

6. Gebruikersbeheerpagina (docenten)

Er is een aparte pagina gemaakt waarop docenten gebruikers kunnen aanmaken en bewerken. Alle relevante velden uit de users-tabel zijn beschikbaar. Create- en edit-modus zijn duidelijk visueel onderscheiden en formulierdata blijft behouden bij refresh via sessions.

7. Bewerken van gebruikers met duidelijke status

Wanneer een docent een gebruiker bewerkt, is dit visueel herkenbaar door een andere kleur en duidelijke melding. De knop “Velden leegmaken” is alleen zichtbaar bij het aanmaken van nieuwe gebruikers, om dataverlies bij bewerken te voorkomen.

8. Klikbare gebruikerslijst met sortering

De gebruikerslijst is volledig klikbaar per rij, niet alleen op de naam. Docenten kunnen eenvoudig sorteren op naam, klas of rol. De tabelbreedte en kolomverdeling zijn afgestemd op de breedte van de invoervelden voor visuele consistentie.

9. Feedback per groep (docentenoverzicht)

Er is een pagina gebouwd waarop docenten per groep de ingezonden website kunnen bekijken. Inclusief een grote preview, een knop om de site extern te openen, en een overzicht van alle gegeven feedback met scores, opmerkingen en namen van feedbackgevers.

10. Instelbare website-preview (400/600/800)

De website-preview op de feedbackpagina is instelbaar naar drie vaste formaten: 400×400, 600×600 en 800×800. Daarnaast is er een optie om de site in een nieuw venster te openen. De bijbehorende styling staat in een apart CSS-bestand.

11. Feedback per leerling (docentenpagina)

We hebben een overzichtspagina gemaakt waar docenten per leerling zien hoeveel feedback is gegeven en welke reviews dat zijn. De docent kan feedback direct aanpassen. De layout toont links de leerlingenlijst en rechts de feedbackdetails.

12. Consistente layout en leesbaarheid

Op alle pagina’s is aandacht besteed aan leesbaarheid: bredere commentaarvelden, betere grid-verhoudingen en duidelijke hiërarchie. Preview en feedback zijn naast elkaar uitgelijnd en schalen netjes op kleinere schermen, zodat de applicatie professioneel en prettig blijft werken.