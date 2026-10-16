<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Scenario;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    public function run(): void
    {
        $items = collect([
            ['name' => 'Ticība pasakām', 'description' => 'Stūrgalvīga ticība, ka siena varētu atvērties, ja tajā ieskriesies.'],
            ['name' => 'Metāla gabals', 'description' => 'Smags metāla gabals no vecajiem tuneļiem. Ar to varētu paplašināt šauru izeju.'],
            ['name' => 'Auduma strēmele', 'description' => 'No veca pārklāja noplēsta auduma strēmele. Tā varētu noderēt brūces apsiešanai.'],
            ['name' => 'Mājas atslēga', 'description' => 'Atslēga, kuru domāji pazaudējis. Tā ļauj tikt mājās, nelienot pa logu.'],
            ['name' => 'Veiksmes monēta', 'description' => 'Putekļos atrasta monēta. Tā izskatās pēc laba veiksmes talismana.'],
        ])->mapWithKeys(fn (array $item) => [
            $item['name'] => Item::updateOrCreate(['name' => $item['name']], ['description' => $item['description']]),
        ]);

        $scenarios = [];

        foreach ($this->story() as $index => $data) {
            $scenario = Scenario::updateOrCreate(
                ['title' => $data['title']],
                [
                    'description' => $data['description'],
                    'risk_zone' => $data['risk_zone'],
                    'sort_order' => $index + 1,
                ],
            );
            $scenario->choices()->delete();
            $scenarios[$index] = $scenario;
        }

        foreach ($this->story() as $scenarioIndex => $data) {
            foreach ($data['choices'] as $choiceIndex => $choice) {
                $nextScenarioId = isset($choice['next'])
                    ? $scenarios[$choice['next']]->id
                    : null;

                $scenarios[$scenarioIndex]->choices()->create([
                    'label' => $choice['label'],
                    'aftermath' => $choice['aftermath'],
                    'next_scenario_id' => $nextScenarioId,
                    'required_item_id' => isset($choice['required'])
                        ? $items[$choice['required']]->id
                        : null,
                    'reward_item_id' => isset($choice['reward'])
                        ? $items[$choice['reward']]->id
                        : null,
                    'reward_chance' => $choice['chance'] ?? null,
                    'ending_title' => $choice['ending_title'] ?? null,
                    'ending_text' => $choice['ending_text'] ?? null,
                    'ending_kind' => $choice['ending_kind'] ?? null,
                    'sort_order' => $choiceIndex,
                ]);
            }
        }
    }

    private function story(): array
    {
        return [
            [
                'title' => '1. SCENĀRIJS — BRUKUMS',
                'risk_zone' => 'danger',
                'description' => 'Kādā garlaicīgā piektdienas vakarā tu ieklīsti dziļi vecos pazemes tuneļos. Eju ir tik daudz, ka nav iespējams saprast, cik to ir un kur tu atrodies. Tu esi apmaldījies. Apkārt ir tumšs un auksts; vienīgā gaisma ir telefona lukturis. Tevi ieskauj senas viduslaiku sienas. Pēkšņi tālumā kaut kas norīb. Grīda nodreb. Putekļu mākonis strauji tuvojas, un tev priekšā ir trīs ejas.',
                'choices' => [
                    [
                        'label' => 'Ieskrien sienā nobloķētajā ejā.',
                        'aftermath' => 'Tu notici pasakām un pilnā sparā ieskrien sienā. Siena nepakustas. Tu zaudē samaņu un guli, cerot, ka kāds tevi izglābs.',
                        'required' => 'Ticība pasakām',
                        'ending_title' => 'SIENA UZVAR',
                        'ending_text' => 'Siena paliek neuzveikta. Tavs pazemes piedzīvojums beidzas, vēl īsti nesācies.',
                        'ending_kind' => 'dead',
                    ],
                    [
                        'label' => 'Paslēpies zem arkas otrajā ejā.',
                        'aftermath' => 'Tu palien zem arkas. Brukums nodārd tev garām, bet akmens iztur. Kad putekļi nosēžas, tu joprojām esi dzīvs.',
                        'next' => 1,
                    ],
                    [
                        'label' => 'Skrien pa garo eju prom no putekļiem.',
                        'aftermath' => 'Tu meties skriet tumsā. Eja dreb, bet tu apsteidz brukumu un ieskrien drošākā telpā.',
                        'reward' => 'Metāla gabals',
                        'chance' => 0.65,
                        'next' => 1,
                    ],
                ],
            ],
            [
                'title' => '2. NOTIKUMS — VĒJA VIRZIENS',
                'risk_zone' => 'safe',
                'description' => 'Tu veiksmīgi nokļuvi drošākā vietā. Te nekas nebrūk. Pagaidi… te pūš vējš. Tas plūst cauri tuneļiem no ārpuses. Varbūt vējš parādīs ceļu ārā.',
                'choices' => [
                    [
                        'label' => 'Seko vējam svaiga gaisa virzienā.',
                        'aftermath' => 'Tu seko vējam pa klusu, šauru eju. Gaiss kļūst siltāks un smaržo pēc lietus.',
                        'reward' => 'Veiksmes monēta',
                        'chance' => 0.35,
                        'next' => 2,
                    ],
                    [
                        'label' => 'Ej atpakaļ brukuma virzienā.',
                        'aftermath' => 'Tunelis atkal iekrakšķas. Šoreiz griesti iebrūk, pirms tu paspēj apgriezties.',
                        'ending_title' => 'APRAKTS PAZEMĒ',
                        'ending_text' => 'Tu devies atpakaļ brukuma virzienā. Vecie tuneļi aizvēra ceļu uz visiem laikiem.',
                        'ending_kind' => 'dead',
                    ],
                    [
                        'label' => 'Izlien caur zemu apkopes eju.',
                        'aftermath' => 'Tu izlien cauri šaurai spraugai un nokļūsti aiz bojātajiem tuneļiem. Priekšā vāji spīd zaļgana gaisma.',
                        'reward' => 'Auduma strēmele',
                        'chance' => 0.5,
                        'next' => 2,
                    ],
                ],
            ],
            [
                'title' => '3. NOTIKUMS — IZEJA',
                'risk_zone' => 'caution',
                'description' => 'Tur priekšā ir gaisma! Paga… ārā taču ir nakts. Vējš tevi atveda līdz nelielai izejai, taču tā ir pārāk šaura, lai izlīstu. Blakus mētājas metāla gabals, un tunelis turpinās tālāk.',
                'choices' => [
                    [
                        'label' => 'Ar metāla gabalu atlauz izeju platāku.',
                        'aftermath' => 'Tu iebāz metāla gabalu spraugā un paplašini izeju. Akmeņi virs galvas sakustas, bet tev izdodas izkļūt ārā.',
                        'required' => 'Metāla gabals',
                        'next' => 3,
                    ],
                    [
                        'label' => 'Ej tālāk un meklē citu izeju.',
                        'aftermath' => 'Tu turpini ceļu, nevis riskē ar trauslo atveri. Platāka eja tevi izved ārā zem kokiem.',
                        'reward' => 'Mājas atslēga',
                        'chance' => 0.25,
                        'next' => 3,
                    ],
                    [
                        'label' => 'Seko šaurajai ventilācijas ejai satiksmes skaņas virzienā.',
                        'aftermath' => 'Eja ir šaura, bet tā ved līdz apkopes lūkai pie ceļa. Tu to atver un iznāc naktī.',
                        'next' => 3,
                    ],
                ],
            ],
            [
                'title' => '4. NOTIKUMS — PAZAUDĒTĀ ATSLĒGA',
                'risk_zone' => 'caution',
                'description' => 'Beidzot esi ārā un dodies mājās. Pie durvīm pārmeklē kabatas. Mājas atslēgu nav! Gaismas ir izslēgtas, bet tu atceries, ka viens logs palika neaizslēgts.',
                'choices' => [
                    [
                        'label' => 'Atgriezies tuneļos un meklē atslēgas.',
                        'aftermath' => 'Tu dodies atpakaļ pazemē meklēt atslēgas. Jau novājinātie tuneļi sāk brukt tev apkārt.',
                        'ending_title' => 'ATKAL PAZUDIS',
                        'ending_text' => 'Tuneļi iebrūk, pirms atrodi atslēgas. Atgriezties pazemē bija nepareizais ceļš uz mājām.',
                        'ending_kind' => 'dead',
                    ],
                    [
                        'label' => 'Iekāp pa atvērto logu.',
                        'aftermath' => 'Tu uzmanīgi ierāpies pa neaizslēgto logu un droši nokļūsti mājās.',
                        'next' => 4,
                    ],
                    [
                        'label' => 'Izmanto ārā paslēpto rezerves atslēgu.',
                        'aftermath' => 'Tu atceries rezerves atslēgu zem puķupoda. Tā joprojām ir tur, un durvis atveras bez kāpšanas.',
                        'required' => 'Mājas atslēga',
                        'next' => 4,
                    ],
                ],
            ],
            [
                'title' => '5. NOTIKUMS — BEIDZOT MĀJĀS',
                'risk_zone' => 'safe',
                'description' => 'Tu esi mājās. Adrenalīnam rimstoties, pamani, ka sāp kāja. Uz biksēm ir asinis, un nobrāzums izskatās sliktāk, nekā sākumā šķita.',
                'choices' => [
                    [
                        'label' => 'Nomazgā brūci un apsien to ar auduma strēmeli.',
                        'aftermath' => 'Tu iztīri brūci un rūpīgi to apsien. Tā sūrst, bet asiņošana apstājas. Tu izdzīvoji tuneļos un parūpējies par sevi.',
                        'required' => 'Auduma strēmele',
                        'ending_title' => 'TĀ BIJA LABA PIEKTDIENA',
                        'ending_text' => 'Tu tiki mājās, aprūpēji brūci un paliki dzīvs, lai izstāstītu šo piedzīvojumu. Tā bija laba piektdiena. Bija jautri.',
                        'ending_kind' => 'good',
                    ],
                    [
                        'label' => 'Ignorē brūci un uzreiz ej gulēt.',
                        'aftermath' => 'Tu ignorē asiņojošo brūci un apgulies. Sāpes pastiprinās, un istaba sāk griezties.',
                        'ending_title' => 'VAJADZĒJA BRŪCI IZTĪRĪT',
                        'ending_text' => 'Miegā brūce iekaisa. Piedzīvojums beidzas no sekām, no kurām būtu bijis viegli izvairīties.',
                        'ending_kind' => 'dead',
                    ],
                    [
                        'label' => 'Piezvani draugam, lai palīdz iztīrīt un apsiet brūci.',
                        'aftermath' => 'Draugs atbrauc ar aptieciņu un palīdz iztīrīt brūci. Tu esi drošībā, kāja sāp, un jau plāno mazāk bīstamu piektdienu.',
                        'ending_title' => 'TĀ BIJA LABA PIEKTDIENA',
                        'ending_text' => 'Tu tiki mājās, saņēmi palīdzību un paliki dzīvs, lai izstāstītu šo piedzīvojumu. Tā bija laba piektdiena. Bija jautri.',
                        'ending_kind' => 'good',
                    ],
                ],
            ],
        ];
    }
}
