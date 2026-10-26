# Project README

Šajā projektā iekļauts:

- Aizmugursistēma (backend) ar Laravel
- Lietotāja saskarne ar Vue
- SQLite database setup

Šis projekts ir organizēts atsevišķos aizmugursistēmas, lietotāja saskarnes un datubāzes folderos priekš skaidrāku projekta struktūru.

## Palaist spēli lokāli

1. Iekšā `backend/laravel-app`, nokopē `.env.example` uz `.env`, un tad konfigurē `GAME_ADMIN_EMAIL`, `GAME_ADMIN_USERNAME`, un pievieno spēcīgu paroli `GAME_ADMIN_PASSWORD`. Konts tiks izveidots ar administrātora piekļuvi, parasta pierakstīšanās nekad nevarēs iegūt administratora piekļuvi.
2. No `backend/laravel-app`, palaid terminālī:

   ```
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. Atverot otru termināli, no `frontend`, palaid `npm install` un `npm run dev`.

Spēles lietotāja saskarne darbojās uz port `5173`; Laravel, spēles API, darbojas uz port `8000`. Tie ir atsevišķi serveri, un tie nevar koplietot portu. Vite starpniekserveri `/game-api` veic pieprasījumus Laravel 8000. portā. Atveriet spēlei `http://127.0.0.1:5173` un ļaujiet abiem servera termināļiem darboties. Pirms spēles sākšanas piesakieties vai izveidojiet kontu; Turpināt (Continue) uzskaita šī konta nepabeigtās sesijas ar pašreizējā scenārija aprakstu un pagājušo laiku formātā `MM:SS` (līdz `99:59`), kā arī saglabā trīs jaunākos atjauninātos saglabāšanas ierakstus. Konfigurētais administrators var atvērt administratora paneli no galvenās izvēlnes, lai pārvaldītu scenārijus, to trīs izvēles iespējas un vienumus. Scenārijs var pēc izvēles aizstāt atlasītu parasto scenāriju nejauši; aizstāšanas iespēju kopsumma tam pašam parastajam scenārijam nedrīkst pārsniegt `1` (100%). Nāves un atlīdzības iespēju skaits tiek palielināts pa `0,01` (piemēram, `0,25` ir 25%). Dzēšot scenāriju, esošās ienākošās izvēles saglabā savas beigas, un pabeigtās saglabāšanas ieraksti šajā ainā tiek dzēsti. Dzēšana tiek bloķēta, ja no tās joprojām ir atkarīga nepabeigta saglabāšana, ienākoša izvēle bez pilnīgas pabeigšanas vai nejauša aizstāšana.
