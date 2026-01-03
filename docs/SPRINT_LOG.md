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

