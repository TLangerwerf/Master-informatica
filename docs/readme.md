# MasterPiece



## Projectomschrijving
Dit project betreft een educatieve webapplicatie waarin leerlingen websites van klasgenoten kunnen bekijken en gestructureerde feedback kunnen geven. De applicatie is bedoeld als leerinstrument binnen het ICT-onderwijs en wordt ingezet om zowel technische ontwikkeling als feedbackvaardigheden te ondersteunen.

Het project is opgezet als een voorbeeld van een software-engineeringproces, waarbij niet alleen het eindproduct, maar ook het ontwikkelproces centraal staat.

---

## Doel
Het doel van dit project is het ontwikkelen van een eenvoudige, maar complete webapplicatie waarin:
- Leerlingen feedback geven op elkaars websites.
- Feedback inzichtelijk wordt voor groepen en docent.
- Het overzichtelijk maken van feedbackmomenten van leerlingen.

## MVP
De applicatie bevat de volgende kernfunctionaliteiten:
- Docent kan websites toevoegen per klas
- Leerlingen loggen in met vooraf bepaalde accounts
- Leerlingen kunnen websites van klasgenoten bekijken
- Leerlingen kunnen reviews geven (score + toelichting)
- Reviews zijn anoniem zichtbaar voor leerlingen
- Docent heeft inzicht in gegeven feedback per leerling


## Gebruikersrollen

**Docent**
- Voegt websites toe
- Heeft volledig inzicht in feedback binnen de klas

**Leerling**
- Kan websites bekijken van klasgenoten
- Kan feedback geven op websites
- Ziet feedback anoniem

---

## Werkwijze
Het project wordt uitgevoerd volgens een Scrum-light aanpak:
- Werk wordt opgeknipt in user stories
- Taken worden beheerd via GitHub Issues
- Ontwikkeling verloopt in drie sprints
- Voortgang en leerpunten worden vastgelegd in `SPRINT_LOG.md`

---

## Sprintindeling
- **Sprint 1 – Basis & Toegang**  
  Project setup, login en autorisatie

- **Sprint 2 – Websites & Bekijken**  
  Websites toevoegen en bekijken

- **Sprint 3 – Reviews & Inzicht**  
  Feedbackfunctionaliteit en docentoverzicht

---

## Reflectie
Na iedere sprint wordt gereflecteerd op:
- voortgang;
- gemaakte keuzes;
- verbeterpunten voor volgende sprints.

Deze reflecties zijn vastgelegd in `SPRINT_LOG.md`.

---

## Techniek (globaal)
- Front-end: React
- Back-end: API (bijv. Node/PHP)
- Database: relationeel (bijv. MySQL)
- Authenticatie: vooraf gedefinieerde accounts

---

## Status
Dit project bevindt zich in actieve ontwikkeling.

## Gebruikers:
leerlingen
docenten

User story’s:
Om een duidelijk pakket van eisen op te kunnen stellen zijn onderstaande userstory’s opgesteld. Deze userstory’s worden gebruikt om alle functionaliteiten voor het product te kunnen bepalen.
1.	Een docent kan een website uploaden zodat deze zichtbaar is voor een leerling.
    o	Alleen docent-account kan websites toevoegen.
    o	Website bevat minimaal: titel + URL + eigenaar/groep.
    o	Website verschijnt in het overzicht voor leerlingen van dezelfde klas.
2.	Een leerling kan inloggen met een vooraf bepaald account, zodat berichten niet anoniem geplaatst kunnen worden.
    o	Inloggen met gebruikersnaam + wachtwoord. (vooraf bepaald)
    o	Zonder login geen reviews plaatsen. Wel sites zichtbaar voor andere ’niet beoordelende’ klassen 
    o	Gebruiker kan uitloggen
3.	Een leerling kan een website van een klasgenoot openen zodat hij deze kan bekijken.
    o	Leerling ziet alleen websites uit eigen klas
    o	Detailpagina toont: titel, URL, korte beschrijving + knop “open website”
    o	URL is valide (basischeck) en opent in nieuw tabblad
4.	Een leerling kan een review geven over de website zodat alle klasgenoten in de groep feedback kunnen ontvangen.
    o	Review bestaat uit: score (bijv. 1–5) + toelichting.
    o	Max. 1 review per leerling per website.
    o	Review is zichtbaar voor eigenaar/groep en klasgenoten (anoniem)
5.	Een groep kan overzichtelijk de feedback over hun website inzien zodat ze de feedback kunnen verwerken.
    o	Per website: lijst reviews. (Anoniem)
    o	Gemiddelde score.
6.	Een docent kan per leerling zien, welke feedback is gegeven zodat hij kan beoordelen hoe een leerling feedback geeft.
    o	Docentoverzicht: per leerling lijst van gegeven reviews
    o	Docent kan doorklikken naar reviewdetails
 
## SPRINT 1 MVP

Wireframe leerlingen en Docenten.
Userflows zijn geïnventariseerd.SQL_WRPd
ERD is gemaakt op basis van de uit te voeren taken.

## CSV importeren
users_admin.php
username,display_name,class_id,group_id
j.jansen,Jan Jansen,1,3
p.pieters,Piet Pieters,1,3
