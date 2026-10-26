# Spēles publicēšana internetā

Spēli var publicēt Render platformā. Tīmekļa vietne, Laravel API un datubāze darbosies kopā: spēlētāju konti un saglabātās spēles tiks glabātas PostgreSQL datubāzē, nevis dzēšamā servera failā.

## Pirms publicēšanas

1. Ievieto jaunāko projektu GitHub repozitorijā. Render publicē kodu no repozitorija, tāpēc lokāli veiktās izmaiņas vispirms ir jānosūta uz GitHub.
2. Izveido Render kontu un piesaisti GitHub.
3. Render izvēlies **New → Blueprint**, atlasot šo repozitoriju un tā `render.yaml` failu.
4. Ievadi administratora e-pastu un drošu paroli. Paroli ievadi tikai Render iestatījumos — nekad nepievieno to repozitorijam vai kopīgotai ziņai.
5. Apstiprini izveidošanu. Render sagatavos tīmekļa serveri un PostgreSQL datubāzi, uzbūvēs vietni un automātiski lietos datubāzes migrācijas.

Render izveidos tīmekļa adresi ar `onrender.com` domēnu. Savu domēnu vari pieslēgt vēlāk Render iestatījumos.

## Sākotnējā spēles satura ievietošana

Pēc pirmās veiksmīgās publicēšanas Render informācijas panelī atver spēles tīmekļa pakalpojuma **Shell** un izpildi šo komandu vienu reizi:

```sh
php artisan db:seed --force
```

Tā ievieto sākuma scenārijus un izveido administratora kontu, izmantojot Render iestatīto administratora e-pastu un paroli. Neizpildi šo komandu atkārtoti pēc tam, kad administratora panelī ir veiktas izmaiņas, jo sākuma scenāriju sējējs atjauno iebūvēto stāsta saturu.

Pēc tam atver Render piešķirto adresi un pārbaudi reģistrēšanos, pieslēgšanos, spēles saglabāšanu un administratora paneli.

## Uzturēšana

- Jauno kodu nosūti uz GitHub; Render pēc tam izveidos un publicēs jaunu versiju.
- Datubāzes migrācijas tiek palaistas automātiski katras jaunās versijas startā.
- Spēlētāju konti un saglabātās spēles atrodas PostgreSQL datubāzē; `SQLite` fails no izstrādes datora netiek publicēts.
