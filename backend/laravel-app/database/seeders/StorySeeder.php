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
            ['name' => 'Ticība pasakām', 'name_en' => 'Belief in fairytales', 'description' => 'Stūrgalvīga ticība, ka siena varētu atvērties, ja tajā ieskriesies.', 'description_en' => 'A stubborn belief that a wall might open if you run into it.'],
            ['name' => 'Metāla gabals', 'name_en' => 'Piece of metal', 'description' => 'Smags metāla gabals no vecajiem tuneļiem. Ar to varētu paplašināt šauru izeju.', 'description_en' => 'A heavy piece of metal from the old tunnels. It could help widen a narrow exit.'],
            ['name' => 'Auduma strēmele', 'name_en' => 'Strip of fabric', 'description' => 'No veca pārklāja noplēsta auduma strēmele. Tā varētu noderēt brūces apsiešanai.', 'description_en' => 'A strip of fabric torn from an old covering. It could be useful for bandaging a wound.'],
            ['name' => 'Mājas atslēga', 'name_en' => 'House key', 'description' => 'Atslēga, kuru domāji pazaudējis. Tā ļauj tikt mājās, nelienot pa logu.', 'description_en' => 'A key you thought you had lost. It lets you get home without climbing through a window.'],
            ['name' => 'Veiksmes monēta', 'name_en' => 'Lucky coin', 'description' => 'Putekļos atrasta monēta. Tā izskatās pēc laba veiksmes talismana.', 'description_en' => 'A coin found in the dust. It looks like a lucky charm.'],
        ])->mapWithKeys(fn (array $item) => [
            $item['name'] => Item::updateOrCreate(['name' => $item['name']], collect($item)->except('name')->all()),
        ]);

        $scenarios = [];

        foreach ($this->story() as $index => $data) {
            $scenario = Scenario::updateOrCreate(
                ['title' => $data['title']],
                [
                    'description' => $data['description'],
                    'title_en' => $data['title_en'],
                    'description_en' => $data['description_en'],
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
                    'label_en' => $choice['label_en'],
                    'aftermath' => $choice['aftermath'],
                    'aftermath_en' => $choice['aftermath_en'],
                    'next_scenario_id' => $nextScenarioId,
                    'required_item_id' => isset($choice['required'])
                        ? $items[$choice['required']]->id
                        : null,
                    'reward_item_id' => isset($choice['reward'])
                        ? $items[$choice['reward']]->id
                        : null,
                    'reward_chance' => $choice['chance'] ?? null,
                    'ending_title' => $choice['ending_title'] ?? null,
                    'ending_title_en' => $choice['ending_title_en'] ?? null,
                    'ending_text' => $choice['ending_text'] ?? null,
                    'ending_text_en' => $choice['ending_text_en'] ?? null,
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
                'title_en' => 'SCENARIO 1 — COLLAPSE',
                'risk_zone' => 'danger',
                'description' => 'Kādā garlaicīgā piektdienas vakarā tu ieklīsti dziļi vecos pazemes tuneļos. Eju ir tik daudz, ka nav iespējams saprast, cik to ir un kur tu atrodies. Tu esi apmaldījies. Apkārt ir tumšs un auksts; vienīgā gaisma ir telefona lukturis. Tevi ieskauj senas viduslaiku sienas. Pēkšņi tālumā kaut kas norīb. Grīda nodreb. Putekļu mākonis strauji tuvojas, un tev priekšā ir trīs ejas.',
                'description_en' => 'On a boring Friday night, you wander deep into old underground tunnels. There are so many passages that you cannot tell how many there are or where you are. You are lost. It is dark and cold; the only light comes from your phone. Ancient medieval walls surround you. Suddenly, something rumbles in the distance. The ground shakes. A cloud of dust rushes toward you, and three passages lie ahead.',
                'choices' => [
                    [
                        'label' => 'Ieskrien sienā nobloķētajā ejā.',
                        'label_en' => 'Run into the blocked passage wall.',
                        'aftermath' => 'Tu notici pasakām un pilnā sparā ieskrien sienā. Siena nepakustas. Tu zaudē samaņu un guli, cerot, ka kāds tevi izglābs.',
                        'aftermath_en' => 'You believe in fairytales and sprint straight into the wall. It does not move. You lose consciousness and lie there hoping someone will save you.',
                        'required' => 'Ticība pasakām',
                        'ending_title' => 'SIENA UZVAR',
                        'ending_title_en' => 'THE WALL WINS',
                        'ending_text' => 'Siena paliek neuzveikta. Tavs pazemes piedzīvojums beidzas, vēl īsti nesācies.',
                        'ending_text_en' => 'The wall remains undefeated. Your underground adventure ends before it has really begun.',
                        'ending_kind' => 'dead',
                    ],
                    [
                        'label' => 'Paslēpies zem arkas otrajā ejā.',
                        'label_en' => 'Hide beneath the arch in the second passage.',
                        'aftermath' => 'Tu palien zem arkas. Brukums nodārd tev garām, bet akmens iztur. Kad putekļi nosēžas, tu joprojām esi dzīvs.',
                        'aftermath_en' => 'You duck beneath the arch. The collapse thunders past, but the stone holds. When the dust settles, you are still alive.',
                        'next' => 1,
                    ],
                    [
                        'label' => 'Skrien pa garo eju prom no putekļiem.',
                        'label_en' => 'Run down the long passage away from the dust.',
                        'aftermath' => 'Tu meties skriet tumsā. Eja dreb, bet tu apsteidz brukumu un ieskrien drošākā telpā.',
                        'aftermath_en' => 'You sprint into the darkness. The tunnel shakes, but you outrun the collapse and reach a safer room.',
                        'reward' => 'Metāla gabals',
                        'chance' => 0.65,
                        'next' => 1,
                    ],
                ],
            ],
            [
                'title' => '2. NOTIKUMS — VĒJA VIRZIENS',
                'title_en' => 'EVENT 2 — FOLLOW THE WIND',
                'risk_zone' => 'safe',
                'description' => 'Tu veiksmīgi nokļuvi drošākā vietā. Te nekas nebrūk. Pagaidi… te pūš vējš. Tas plūst cauri tuneļiem no ārpuses. Varbūt vējš parādīs ceļu ārā.',
                'description_en' => 'You have made it to a safer place. Nothing is collapsing here. Wait… there is a breeze. It flows through the tunnels from outside. Maybe it can lead you out.',
                'choices' => [
                    [
                        'label' => 'Seko vējam svaiga gaisa virzienā.',
                        'label_en' => 'Follow the breeze toward the fresh air.',
                        'aftermath' => 'Tu seko vējam pa klusu, šauru eju. Gaiss kļūst siltāks un smaržo pēc lietus.',
                        'aftermath_en' => 'You follow the wind through a quiet, narrow passage. The air grows warmer and smells of rain.',
                        'reward' => 'Veiksmes monēta',
                        'chance' => 0.35,
                        'next' => 2,
                    ],
                    [
                        'label' => 'Ej atpakaļ brukuma virzienā.',
                        'label_en' => 'Go back toward the collapse.',
                        'aftermath' => 'Tunelis atkal iekrakšķas. Šoreiz griesti iebrūk, pirms tu paspēj apgriezties.',
                        'aftermath_en' => 'The tunnel cracks again. This time, the ceiling caves in before you can turn around.',
                        'ending_title' => 'APRAKTS PAZEMĒ',
                        'ending_title_en' => 'BURIED UNDERGROUND',
                        'ending_text' => 'Tu devies atpakaļ brukuma virzienā. Vecie tuneļi aizvēra ceļu uz visiem laikiem.',
                        'ending_text_en' => 'You went back toward the collapse. The old tunnels sealed your way out forever.',
                        'ending_kind' => 'dead',
                    ],
                    [
                        'label' => 'Izlien caur zemu apkopes eju.',
                        'label_en' => 'Squeeze through a low maintenance passage.',
                        'aftermath' => 'Tu izlien cauri šaurai spraugai un nokļūsti aiz bojātajiem tuneļiem. Priekšā vāji spīd zaļgana gaisma.',
                        'aftermath_en' => 'You crawl through a narrow gap and get beyond the damaged tunnels. A faint green light glows ahead.',
                        'reward' => 'Auduma strēmele',
                        'chance' => 0.5,
                        'next' => 2,
                    ],
                ],
            ],
            [
                'title' => '3. NOTIKUMS — IZEJA',
                'title_en' => 'EVENT 3 — THE EXIT',
                'risk_zone' => 'caution',
                'description' => 'Tur priekšā ir gaisma! Paga… ārā taču ir nakts. Vējš tevi atveda līdz nelielai izejai, taču tā ir pārāk šaura, lai izlīstu. Blakus mētājas metāla gabals, un tunelis turpinās tālāk.',
                'description_en' => 'There is light ahead! Wait… it is nighttime outside. The wind has led you to a small exit, but it is too narrow to squeeze through. A piece of metal lies nearby, and the tunnel continues farther on.',
                'choices' => [
                    [
                        'label' => 'Ar metāla gabalu atlauz izeju platāku.',
                        'label_en' => 'Widen the exit with the piece of metal.',
                        'aftermath' => 'Tu iebāz metāla gabalu spraugā un paplašini izeju. Akmeņi virs galvas sakustas, bet tev izdodas izkļūt ārā.',
                        'aftermath_en' => 'You wedge the metal into the gap and make the opening wider. Stones shift overhead, but you manage to get outside.',
                        'required' => 'Metāla gabals',
                        'next' => 3,
                    ],
                    [
                        'label' => 'Ej tālāk un meklē citu izeju.',
                        'label_en' => 'Go farther and look for another exit.',
                        'aftermath' => 'Tu turpini ceļu, nevis riskē ar trauslo atveri. Platāka eja tevi izved ārā zem kokiem.',
                        'aftermath_en' => 'You keep going instead of risking the fragile opening. A wider passage leads you outside beneath the trees.',
                        'reward' => 'Mājas atslēga',
                        'chance' => 0.25,
                        'next' => 3,
                    ],
                    [
                        'label' => 'Seko šaurajai ventilācijas ejai satiksmes skaņas virzienā.',
                        'label_en' => 'Follow the narrow ventilation shaft toward the traffic sounds.',
                        'aftermath' => 'Eja ir šaura, bet tā ved līdz apkopes lūkai pie ceļa. Tu to atver un iznāc naktī.',
                        'aftermath_en' => 'The shaft is tight, but it leads to a maintenance hatch by the road. You open it and step out into the night.',
                        'next' => 3,
                    ],
                ],
            ],
            [
                'title' => '4. NOTIKUMS — PAZAUDĒTĀ ATSLĒGA',
                'title_en' => 'EVENT 4 — THE MISSING KEY',
                'risk_zone' => 'caution',
                'description' => 'Beidzot esi ārā un dodies mājās. Pie durvīm pārmeklē kabatas. Mājas atslēgu nav! Gaismas ir izslēgtas, bet tu atceries, ka viens logs palika neaizslēgts.',
                'description_en' => 'At last, you are outside and heading home. At the door, you check your pockets. Your house key is missing! The lights are off, but you remember that one window was left unlocked.',
                'choices' => [
                    [
                        'label' => 'Atgriezies tuneļos un meklē atslēgas.',
                        'label_en' => 'Go back into the tunnels to look for the keys.',
                        'aftermath' => 'Tu dodies atpakaļ pazemē meklēt atslēgas. Jau novājinātie tuneļi sāk brukt tev apkārt.',
                        'aftermath_en' => 'You head back underground to search for your keys. The weakened tunnels begin collapsing around you.',
                        'ending_title' => 'ATKAL PAZUDIS',
                        'ending_title_en' => 'LOST AGAIN',
                        'ending_text' => 'Tuneļi iebrūk, pirms atrodi atslēgas. Atgriezties pazemē bija nepareizais ceļš uz mājām.',
                        'ending_text_en' => 'The tunnels collapse before you find the keys. Going back underground was the wrong way home.',
                        'ending_kind' => 'dead',
                    ],
                    [
                        'label' => 'Iekāp pa atvērto logu.',
                        'label_en' => 'Climb through the open window.',
                        'aftermath' => 'Tu uzmanīgi ierāpies pa neaizslēgto logu un droši nokļūsti mājās.',
                        'aftermath_en' => 'You carefully climb through the unlocked window and make it safely inside.',
                        'next' => 4,
                    ],
                    [
                        'label' => 'Izmanto ārā paslēpto rezerves atslēgu.',
                        'label_en' => 'Use the spare key hidden outside.',
                        'aftermath' => 'Tu atceries rezerves atslēgu zem puķupoda. Tā joprojām ir tur, un durvis atveras bez kāpšanas.',
                        'aftermath_en' => 'You remember the spare key under the flowerpot. It is still there, and you get inside without climbing.',
                        'required' => 'Mājas atslēga',
                        'next' => 4,
                    ],
                ],
            ],
            [
                'title' => '5. NOTIKUMS — BEIDZOT MĀJĀS',
                'title_en' => 'EVENT 5 — FINALLY HOME',
                'risk_zone' => 'safe',
                'description' => 'Tu esi mājās. Adrenalīnam rimstoties, pamani, ka sāp kāja. Uz biksēm ir asinis, un nobrāzums izskatās sliktāk, nekā sākumā šķita.',
                'description_en' => 'You are home. As the adrenaline fades, you notice your leg hurts. There is blood on your trousers, and the scrape looks worse than you first thought.',
                'choices' => [
                    [
                        'label' => 'Nomazgā brūci un apsien to ar auduma strēmeli.',
                        'label_en' => 'Wash the wound and bandage it with the strip of fabric.',
                        'aftermath' => 'Tu iztīri brūci un rūpīgi to apsien. Tā sūrst, bet asiņošana apstājas. Tu izdzīvoji tuneļos un parūpējies par sevi.',
                        'aftermath_en' => 'You clean the wound and bandage it carefully. It stings, but the bleeding stops. You survived the tunnels and took care of yourself.',
                        'required' => 'Auduma strēmele',
                        'ending_title' => 'TĀ BIJA LABA PIEKTDIENA',
                        'ending_title_en' => 'THAT WAS A GOOD FRIDAY',
                        'ending_text' => 'Tu tiki mājās, aprūpēji brūci un paliki dzīvs, lai izstāstītu šo piedzīvojumu. Tā bija laba piektdiena. Bija jautri.',
                        'ending_text_en' => 'You made it home, treated your wound, and survived to tell the story. That was a good Friday. It was fun.',
                        'ending_kind' => 'good',
                    ],
                    [
                        'label' => 'Ignorē brūci un uzreiz ej gulēt.',
                        'label_en' => 'Ignore the wound and go straight to bed.',
                        'aftermath' => 'Tu ignorē asiņojošo brūci un apgulies. Sāpes pastiprinās, un istaba sāk griezties.',
                        'aftermath_en' => 'You ignore the bleeding wound and lie down. The pain gets worse, and the room begins to spin.',
                        'ending_title' => 'VAJADZĒJA BRŪCI IZTĪRĪT',
                        'ending_title_en' => 'YOU SHOULD HAVE CLEANED THE WOUND',
                        'ending_text' => 'Miegā brūce iekaisa. Piedzīvojums beidzas no sekām, no kurām būtu bijis viegli izvairīties.',
                        'ending_text_en' => 'The wound becomes infected while you sleep. The adventure ends because of something that could have been avoided.',
                        'ending_kind' => 'dead',
                    ],
                    [
                        'label' => 'Piezvani draugam, lai palīdz iztīrīt un apsiet brūci.',
                        'label_en' => 'Call a friend to help clean and bandage the wound.',
                        'aftermath' => 'Draugs atbrauc ar aptieciņu un palīdz iztīrīt brūci. Tu esi drošībā, kāja sāp, un jau plāno mazāk bīstamu piektdienu.',
                        'aftermath_en' => 'A friend arrives with a first-aid kit and helps clean and bandage your wound. You are safe, your leg hurts, and you are already planning a less dangerous Friday.',
                        'ending_title' => 'TĀ BIJA LABA PIEKTDIENA',
                        'ending_title_en' => 'THAT WAS A GOOD FRIDAY',
                        'ending_text' => 'Tu tiki mājās, saņēmi palīdzību un paliki dzīvs, lai izstāstītu šo piedzīvojumu. Tā bija laba piektdiena. Bija jautri.',
                        'ending_text_en' => 'You made it home, got help, and survived to tell the story. That was a good Friday. It was fun.',
                        'ending_kind' => 'good',
                    ],
                ],
            ],
        ];
    }
}
