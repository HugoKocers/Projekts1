# Spēles publicēšana internetā

Šis iestatījums ir paredzēts skolas projekta demonstrēšanai bez maksas. Tīmekļa vietne, Laravel API un datubāze darbosies kopā; spēlētāju konti un saglabātās spēles tiks glabātas PostgreSQL datubāzē.

## Pirms publicēšanas

1. Ievieto jaunāko projektu GitHub repozitorijā. Render publicē kodu no repozitorija, tāpēc lokāli veiktās izmaiņas vispirms ir jānosūta uz GitHub.
2. Izveido Render kontu un piesaisti GitHub.
3. Render izvēlies **New → Blueprint**, atlasot šo repozitoriju un tā `render.yaml` failu.
4. Pārliecinies, ka gan tīmekļa pakalpojumam, gan PostgreSQL datubāzei norādīts **Free** plāns. Ja Render piedāvā maksas plānu, to neizvēlies.
5. Ievadi administratora e-pastu un drošu paroli. Paroli ievadi tikai Render iestatījumos — nekad nepievieno to repozitorijam vai kopīgotai ziņai.
6. Apstiprini izveidošanu. Render uzbūvēs vietni, lietos datubāzes migrācijas un pirmajā reizē ievietos sākuma stāstu un administratora kontu.

Render izveidos tīmekļa adresi ar `onrender.com` domēnu. Šis bezmaksas risinājums ir pietiekams demonstrācijai, taču nav paredzēts pastāvīgai vietnei: neaktīvs serveris pēc 15 minūtēm aizmieg un pēc apmeklētāja atgriešanās var mosties aptuveni minūti. Bezmaksas PostgreSQL datubāze tiek dzēsta pēc 30 dienām.

## Pirms demonstrācijas

Atver tīmekļa adresi dažas minūtes pirms demonstrācijas, lai serveris pamostos. Pirmoreiz atverot vietni, pārbaudi reģistrēšanos, spēles sākšanu un saglabāšanu, kā arī administratora paneli. Sākuma stāsts un administratora konts tiek izveidoti automātiski, kad datubāze ir tukša.

## Uzturēšana

- Jauno kodu nosūti uz GitHub; Render pēc tam izveidos un publicēs jaunu versiju.
- Datubāzes migrācijas tiek palaistas automātiski katras jaunās versijas startā.
- Spēlētāju konti un saglabātās spēles atrodas PostgreSQL datubāzē.
- Lai nepieļautu negaidītus maksājumus, izvēlies tikai **Free** plānus un nepievieno maksājumu karti. Render var apturēt bezmaksas pakalpojumu, ja tiek sasniegti tā lietošanas ierobežojumi.
