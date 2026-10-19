<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

const currentScreen = ref('menu')
const profileMenuOpen = ref(false)
const currentSceneIndex = ref(0)
const scenarios = ref([])
const items = ref([])
const currentUser = ref(null)
const currentScenarioId = ref(null)
const gameSessionId = ref(null)
const savedSessionsFromApi = ref([])
const apiError = ref('')
const adminNotice = ref('')
const saveError = ref('')
const busy = ref(false)
const csrfToken = ref('')
const authForm = ref({ username: '', email: '', password: '', password_confirmation: '' })
const adminScenarios = ref([])
const adminItems = ref([])
const editingScenarioId = ref(null)
const editingItemId = ref(null)
const scenarioDraft = ref(null)
const itemDraft = ref({ name: '', description: '' })
const aftermathText = ref('')
const aftermathReward = ref(null)
const inventory = ref([])
const selectedInventoryItem = ref(null)
const authMode = ref('signup')
const selectedLanguage = ref('EN')
const elapsedSeconds = ref(0)
const gameStartedAt = ref(null)
const inventoryReturnScreen = ref('game')
const pendingEnding = ref(null)
const pendingNextScene = ref(null)
const ending = ref(null)
let gameTimer = null
let lastAutosavedSecond = 0

const menuButtons = computed(() => [
  { id: 'login', label: currentUser.value ? uiText('account') : uiText('auth') },
  { id: 'game', label: uiText('start') },
  { id: 'continue', label: uiText('continue') },
  { id: 'settings', label: uiText('exit'), accent: 'danger' },
])
const normalScenarios = computed(() =>
  scenarios.value
    .filter((scenario) => scenario.replacement_for_scenario_id === null || scenario.replacement_for_scenario_id === undefined)
    .sort((left, right) => left.sort_order - right.sort_order || left.id - right.id),
)
const reachableScenarioIds = computed(() => {
  const startScenarioId = normalScenarios.value[0]?.id
  if (startScenarioId === undefined) return new Set()

  const reachable = new Set([startScenarioId])
  const pending = [startScenarioId]
  while (pending.length) {
    const currentScenarioId = pending.shift()
    const scenario = adminScenarios.value.find((entry) => entry.id === currentScenarioId)
    for (const choice of scenario?.choices ?? []) {
      const nextScenarioId = choice.next_scenario_id
      if (nextScenarioId !== null && nextScenarioId !== undefined && !reachable.has(nextScenarioId)) {
        reachable.add(nextScenarioId)
        pending.push(nextScenarioId)
      }
    }
    for (const replacement of adminScenarios.value) {
      if (
        replacement.replacement_for_scenario_id === currentScenarioId
        && replacement.spawn_chance > 0
        && !reachable.has(replacement.id)
      ) {
        reachable.add(replacement.id)
        pending.push(replacement.id)
      }
    }
  }
  return reachable
})
const userInitial = computed(() => {
  const displayName = currentUser.value?.name || currentUser.value?.username
  return displayName ? Array.from(displayName.trim())[0]?.toLocaleUpperCase() || 'M' : 'M'
})

const itemDescriptions = {
  'Ticība pasakām': 'Stūrgalvīga ticība, ka siena varētu atvērties, ja tajā ieskriesies. Varbūt ne pārāk noderīga, bet noteikti neaizmirstama.',
  'Metāla gabals': 'Smags metāla gabals no vecajiem tuneļiem. Ar to varētu paplašināt šauru izeju.',
  'Auduma strēmele': 'No veca pārklāja noplēsta auduma strēmele. Tā varētu noderēt brūces apsiešanai.',
  'Mājas atslēga': 'Atslēga, kuru domāji pazaudējis. Tā ļauj tikt mājās, nelienot pa logu.',
  'Veiksmes monēta': 'Putekļos atrasta monēta. Tā izskatās pēc laba veiksmes talismana.',
}

function setReplacementTarget(draft, scenarioId) {
  draft.replacement_for_scenario_id = scenarioId
  if (!scenarioId) draft.spawn_chance = ''
  else if (draft.spawn_chance === '') draft.spawn_chance = '0.25'
  clearAdminFeedback()
}

const uiTranslations = {
  EN: {
    auth: 'SIGN UP / LOG IN',
    start: 'START',
    continue: 'CONTINUE',
    exit: 'EXIT',
    admin: 'ADMIN PANEL',
    account: 'ACCOUNT',
    returnMenu: 'RETURN TO MENU',
    back: 'BACK',
    game: 'Game',
    gameEnd: 'Game over',
    adminScreen: 'Administrator',
    username: 'USERNAME OR EMAIL',
    email: 'EMAIL',
    password: 'PASSWORD',
    confirmPassword: 'CONFIRM PASSWORD',
    wait: 'PLEASE WAIT…',
    createAccount: 'CREATE ACCOUNT',
    signUp: 'SIGN UP',
    logIn: 'LOG IN',
    language: 'LANGUAGE',
    return: 'RETURN',
    logOut: 'LOG OUT',
    noSaves: 'NO SAVED GAMES AVAILABLE.',
    saveLimit: 'Only the three most recently updated saves are kept; older saves are removed automatically.',
    inventory: 'INVENTORY',
    backToGame: 'BACK TO GAME',
    emptyInventory: 'NO ITEMS COLLECTED',
    item: 'ITEM',
    required: 'Required:',
    retrySave: 'RETRY SAVE',
    next: 'NEXT',
    playtime: 'PLAYTIME',
    totalPlayed: 'TOTAL PLAYED:',
    end: 'END',
    loggedIn: 'Logged in:',
    adminHeading: 'Game content',
    scenarios: 'Scenarios',
    items: 'Items',
    new: 'NEW',
    edit: 'EDIT',
    delete: 'DELETE',
    scenarioName: 'Title',
    description: 'Description',
    riskZone: 'Risk zone',
    safe: 'Safe',
    caution: 'Caution',
    danger: 'Danger',
    replacesScenario: 'Replace normal scenario (optional)',
    standardScenario: 'Standard story scenario',
    spawnChance: 'Spawn chance (0–1)',
    spawnChanceHelp: 'The chance this scene replaces the selected normal scene. Replacement chances for the same scene must total no more than 1.',
    replacementDetailsRequired: 'Select a normal scenario and enter a spawn chance for a random replacement.',
    spawnChanceInvalid: 'Spawn chance must be between 0 and 1.',
    spawnChancePrecision: 'Spawn chance must use increments of 0.01 (up to 2 decimal places).',
    randomReplacement: 'Random replacement',
    replaces: 'Replaces:',
    scenarioSaved: 'Scenario saved.',
    scenarioDeleted: 'Scenario deleted.',
    deleteScenarioConfirm: 'Delete this scenario? Any completed saves at this scene will also be removed. Existing endings in incoming choices will be kept.',
    itemSaved: 'Item saved.',
    itemDeleted: 'Item deleted.',
    order: 'Normal scenario order (lower starts earlier)',
    choice: 'Choice',
    choiceText: 'Choice text',
    consequence: 'Consequence',
    nextScenario: 'Next scenario',
    noValue: 'None',
    endingType: 'Ending type',
    goodEnding: 'Good ending',
    badEnding: 'Bad ending',
    requiredItem: 'Required item',
    rewardItem: 'Reward item',
    rewardChance: 'Reward chance (0–1)',
    rewardChanceHelp: 'Probability of receiving this item: 0.25 means 25%. Use increments of 0.01. Leave both reward fields empty for no reward.',
    choiceRule: 'All three choices need text and a consequence. For each, select exactly one next scenario or ending. Random replacement scenes use the target and chance settings above; they do not need a direct link.',
    endingTitle: 'Ending title',
    endingText: 'Ending text',
    reachable: 'Playable from start',
    unreachable: 'Not linked from start',
    scenarioDetailsRequired: 'Enter a scenario title and description.',
    scenarioOrderInvalid: 'Enter a non-negative whole number for the scenario order.',
    choiceDetailsRequired: 'Choice : fill in both the choice text and consequence.',
    choiceRouteRequired: 'Choice : select exactly one next scenario or ending.',
    choiceEndingRequired: 'Choice : add both an ending title and ending text.',
    choiceRewardRequired: 'Choice : select both a reward item and chance, or leave both empty.',
    choiceRewardChanceInvalid: 'Choice : reward chance must be between 0 and 1.',
    choiceRewardPrecision: 'Choice : reward chance must use increments of 0.01 (up to 2 decimal places).',
    save: 'SAVE',
    saveScenario: 'SAVE SCENARIO',
    saveItem: 'SAVE ITEM',
    cancel: 'CANCEL',
    newScenario: 'New scenario',
    editScenario: 'Edit scenario',
    newItem: 'New item',
    editItem: 'Edit item',
    itemName: 'Name',
    adminOnly: 'This page is only available to administrators.',
    signInToStart: 'Sign in or create an account to start and save a game.',
    signInToContinue: 'Sign in to view your saved games.',
    noScenarios: 'No scenarios are available. Run the database seeder.',
    saveFailed: 'Game save failed.',
  },
  LV: {
    auth: 'REĢISTRĒTIES / PIESLĒGTIES',
    start: 'SĀKT',
    continue: 'TURPINĀT',
    exit: 'IZIET',
    admin: 'ADMIN PANELIS',
    account: 'KONTS',
    returnMenu: 'ATGRIEZTIES UZ IZVĒLNI',
    back: 'ATGRIEZTIES',
    game: 'Spēle',
    gameEnd: 'Spēles beigas',
    adminScreen: 'Administrators',
    username: 'LIETOTĀJVĀRDS VAI E-PASTS',
    email: 'E-PASTS',
    password: 'PAROLE',
    confirmPassword: 'APSTIPRINI PAROLI',
    wait: 'UZGAIDI…',
    createAccount: 'IZVEIDOT KONTU',
    signUp: 'REĢISTRĒTIES',
    logIn: 'PIESLĒGTIES',
    language: 'VALODA',
    return: 'ATGRIEZTIES',
    logOut: 'ATSLĒGTIES',
    noSaves: 'NAV SAGLABĀTU SPĒĻU.',
    saveLimit: 'Tiek paturētas tikai trīs nesenāk atjauninātās spēles; vecākās tiek automātiski dzēstas.',
    inventory: 'INVENTĀRS',
    backToGame: 'ATGRIEZTIES SPĒLĒ',
    emptyInventory: 'NAV ATRASTU PRIEKŠMETU',
    item: 'PRIEKŠMETS',
    required: 'Nepieciešams:',
    retrySave: 'MĒĢINĀT SAGLABĀT VĒLREIZ',
    next: 'TĀLĀK',
    playtime: 'SPĒLES LAIKS',
    totalPlayed: 'KOPĀ SPĒLĒTS:',
    end: 'BEIGT',
    loggedIn: 'Pieslēdzies:',
    adminHeading: 'Spēles saturs',
    scenarios: 'Scenāriji',
    items: 'Priekšmeti',
    new: 'JAUNS',
    edit: 'REDIĢĒT',
    delete: 'DZĒST',
    scenarioName: 'Nosaukums',
    description: 'Apraksts',
    riskZone: 'Riska zona',
    safe: 'Droša',
    caution: 'Uzmanību',
    danger: 'Bīstama',
    replacesScenario: 'Aizstāt parasto scenāriju (neobligāti)',
    standardScenario: 'Parasts stāsta scenārijs',
    spawnChance: 'Parādīšanās iespēja (0–1)',
    spawnChanceHelp: 'Iespēja, ka šis scenārijs aizstās izvēlēto parasto scenāriju. Vairāku aizvietotāju kopējā iespēja nedrīkst pārsniegt 1.',
    replacementDetailsRequired: 'Nejaušam aizvietotājam izvēlies parasto scenāriju un norādi parādīšanās iespēju.',
    spawnChanceInvalid: 'Parādīšanās iespējai jābūt no 0 līdz 1.',
    spawnChancePrecision: 'Parādīšanās iespēja jānorāda ar soli 0,01 (ne vairāk kā 2 zīmes aiz komata).',
    randomReplacement: 'Nejaušs aizvietotājs',
    replaces: 'Aizstāj:',
    scenarioSaved: 'Scenārijs saglabāts.',
    scenarioDeleted: 'Scenārijs izdzēsts.',
    deleteScenarioConfirm: 'Dzēst šo scenāriju? Tiks dzēstas arī pabeigtās spēles šajā scenārijā. Esošās beigas ienākošajās izvēlēs tiks saglabātas.',
    itemSaved: 'Priekšmets saglabāts.',
    itemDeleted: 'Priekšmets izdzēsts.',
    order: 'Parastā scenārija kārtība (mazākais sākas agrāk)',
    choice: 'Izvēle',
    choiceText: 'Izvēles teksts',
    consequence: 'Sekas',
    nextScenario: 'Nākamais scenārijs',
    noValue: 'Nav',
    endingType: 'Beigu veids',
    goodEnding: 'Labas beigas',
    badEnding: 'Sliktas beigas',
    requiredItem: 'Nepieciešamais priekšmets',
    rewardItem: 'Atlīdzības priekšmets',
    rewardChance: 'Atlīdzības iespēja (0–1)',
    rewardChanceHelp: 'Varbūtība saņemt šo priekšmetu: 0.25 nozīmē 25%. Izmanto soli 0,01. Ja atlīdzības nav, atstāj abus laukus tukšus.',
    choiceRule: 'Visām trim izvēlēm jābūt tekstam un sekām. Katrai izvēlei norādi tieši vienu — nākamo scenāriju vai beigas. Nejaušie aizvietotāji izmanto augstāk norādīto mērķi un iespēju; tie nav tieši jāsasaista ar izvēli.',
    endingTitle: 'Beigu virsraksts',
    endingText: 'Beigu teksts',
    reachable: 'Spēlējams no sākuma',
    unreachable: 'Nav sasniedzams no sākuma',
    scenarioDetailsRequired: 'Ievadi scenārija nosaukumu un aprakstu.',
    scenarioOrderInvalid: 'Ievadi kārtas skaitli kā veselu, nenegatīvu skaitli.',
    choiceDetailsRequired: 'Izvēle : ievadi gan izvēles tekstu, gan sekas.',
    choiceRouteRequired: 'Izvēle : norādi tieši vienu — nākamo scenāriju vai beigas.',
    choiceEndingRequired: 'Izvēle : ievadi gan beigu virsrakstu, gan beigu tekstu.',
    choiceRewardRequired: 'Izvēle : norādi gan atlīdzības priekšmetu, gan iespēju vai atstāj abus tukšus.',
    choiceRewardChanceInvalid: 'Izvēle : atlīdzības iespējai jābūt no 0 līdz 1.',
    choiceRewardPrecision: 'Izvēle : atlīdzības iespēja jānorāda ar soli 0,01 (ne vairāk kā 2 zīmes aiz komata).',
    save: 'SAGLABĀT',
    saveScenario: 'SAGLABĀT SCENĀRIJU',
    saveItem: 'SAGLABĀT PRIEKŠMETU',
    cancel: 'ATCELT',
    newScenario: 'Jauns scenārijs',
    editScenario: 'Rediģēt scenāriju',
    newItem: 'Jauns priekšmets',
    editItem: 'Rediģēt priekšmetu',
    itemName: 'Nosaukums',
    adminOnly: 'Šī lapa pieejama tikai administratoram.',
    signInToStart: 'Pieslēdzies vai reģistrējies, lai sāktu un saglabātu spēli.',
    signInToContinue: 'Pieslēdzies, lai skatītu saglabātās spēles.',
    noScenarios: 'Nav pieejamu scenāriju. Palaid datubāzes sēklu komandu.',
    saveFailed: 'Neizdevās saglabāt spēli.',
  },
}

const storyTranslations = {
  '1. SCENĀRIJS — BRUKUMS': {
    title: 'SCENARIO 1 — COLLAPSE',
    description: 'On a boring Friday night, you wander deep into old underground tunnels. There are so many passages that you cannot tell how many there are or where you are. You are lost. It is dark and cold; the only light comes from your phone. Ancient medieval walls surround you. Suddenly, something rumbles in the distance. The ground shakes. A cloud of dust rushes toward you, and three passages lie ahead.',
    choices: [
      ['Run into the blocked passage wall.', 'You believe in fairytales and sprint straight into the wall. It does not move. You lose consciousness and lie there hoping someone will save you.', 'THE WALL WINS', 'The wall remains undefeated. Your underground adventure ends before it has really begun.'],
      ['Hide beneath the arch in the second passage.', 'You duck beneath the arch. The collapse thunders past, but the stone holds. When the dust settles, you are still alive.'],
      ['Run down the long passage away from the dust.', 'You sprint into the darkness. The tunnel shakes, but you outrun the collapse and reach a safer room.'],
    ],
  },
  '2. NOTIKUMS — VĒJA VIRZIENS': {
    title: 'EVENT 2 — FOLLOW THE WIND',
    description: 'You have made it to a safer place. Nothing is collapsing here. Wait… there is a breeze. It flows through the tunnels from outside. Maybe it can lead you out.',
    choices: [
      ['Follow the breeze toward the fresh air.', 'You follow the wind through a quiet, narrow passage. The air grows warmer and smells of rain.'],
      ['Go back toward the collapse.', 'The tunnel cracks again. This time, the ceiling caves in before you can turn around.', 'BURIED UNDERGROUND', 'You went back toward the collapse. The old tunnels sealed your way out forever.'],
      ['Squeeze through a low maintenance passage.', 'You crawl through a narrow gap and get beyond the damaged tunnels. A faint green light glows ahead.'],
    ],
  },
  '3. NOTIKUMS — IZEJA': {
    title: 'EVENT 3 — THE EXIT',
    description: 'There is light ahead! Wait… it is nighttime outside. The wind has led you to a small exit, but it is too narrow to squeeze through. A piece of metal lies nearby, and the tunnel continues farther on.',
    choices: [
      ['Widen the exit with the piece of metal.', 'You wedge the metal into the gap and make the opening wider. Stones shift overhead, but you manage to get outside.'],
      ['Go farther and look for another exit.', 'You keep going instead of risking the fragile opening. A wider passage leads you outside beneath the trees.'],
      ['Follow the narrow ventilation shaft toward the traffic sounds.', 'The shaft is tight, but it leads to a maintenance hatch by the road. You open it and step out into the night.'],
    ],
  },
  '4. NOTIKUMS — PAZAUDĒTĀ ATSLĒGA': {
    title: 'EVENT 4 — THE MISSING KEY',
    description: 'At last, you are outside and heading home. At the door, you check your pockets. Your house key is missing! The lights are off, but you remember that one window was left unlocked.',
    choices: [
      ['Go back into the tunnels to look for the keys.', 'You head back underground to search for your keys. The weakened tunnels begin collapsing around you.', 'LOST AGAIN', 'The tunnels collapse before you find the keys. Going back underground was the wrong way home.'],
      ['Climb through the open window.', 'You carefully climb through the unlocked window and make it safely inside.'],
      ['Use the spare key hidden outside.', 'You remember the spare key under the flowerpot. It is still there, and you get inside without climbing.'],
    ],
  },
  '5. NOTIKUMS — BEIDZOT MĀJĀS': {
    title: 'EVENT 5 — FINALLY HOME',
    description: 'You are home. As the adrenaline fades, you notice your leg hurts. There is blood on your trousers, and the scrape looks worse than you first thought.',
    choices: [
      ['Wash the wound and bandage it with the strip of fabric.', 'You clean the wound and bandage it carefully. It stings, but the bleeding stops. You survived the tunnels and took care of yourself.', 'THAT WAS A GOOD FRIDAY', 'You made it home, treated your wound, and survived to tell the story. That was a good Friday. It was fun.'],
      ['Ignore the wound and go straight to bed.', 'You ignore the bleeding wound and lie down. The pain gets worse, and the room begins to spin.', 'YOU SHOULD HAVE CLEANED THE WOUND', 'The wound becomes infected while you sleep. The adventure ends because of something that could have been avoided.'],
      ['Call a friend to help clean and bandage the wound.', 'A friend arrives with a first-aid kit and helps clean and bandage your wound. You are safe, your leg hurts, and you are already planning a less dangerous Friday.', 'THAT WAS A GOOD FRIDAY', 'You made it home, got help, and survived to tell the story. That was a good Friday. It was fun.'],
    ],
  },
}

const itemNameTranslations = {
  'Ticība pasakām': 'Belief in fairytales',
  'Metāla gabals': 'Piece of metal',
  'Auduma strēmele': 'Strip of fabric',
  'Mājas atslēga': 'House key',
  'Veiksmes monēta': 'Lucky coin',
}

function uiText(key) {
  return uiTranslations[selectedLanguage.value][key] ?? key
}

function translateStoryText(value) {
  if (selectedLanguage.value !== 'EN' || !value) return value
  for (const [title, translation] of Object.entries(storyTranslations)) {
    if (value === title) return translation.title
    if (value === scenarios.value.find((scenario) => scenario.title === title)?.description) return translation.description
    const choiceIndex = scenarios.value.find((scenario) => scenario.title === title)
      ?.choices.findIndex((choice) => choice.label === value)
    if (choiceIndex !== undefined && choiceIndex >= 0) {
      const [label, aftermath, endingTitle, endingText] = translation.choices[choiceIndex]
      if (value === scenarios.value.find((scenario) => scenario.title === title)?.choices[choiceIndex]?.label) return label
      if (value === scenarios.value.find((scenario) => scenario.title === title)?.choices[choiceIndex]?.aftermath) return aftermath
      if (value === scenarios.value.find((scenario) => scenario.title === title)?.choices[choiceIndex]?.ending_title) return endingTitle
      if (value === scenarios.value.find((scenario) => scenario.title === title)?.choices[choiceIndex]?.ending_text) return endingText
    }
  }
  const item = items.value.find((entry) => entry.name === value)
  if (item) return itemNameTranslations[value] ?? value
  const itemDescription = items.value.find((entry) => entry.description === value)
  if (itemDescription) {
    const descriptions = {
      'Ticība pasakām': 'A stubborn belief that a wall might open if you run into it.',
      'Metāla gabals': 'A heavy piece of metal from the old tunnels. It could help widen a narrow exit.',
      'Auduma strēmele': 'A strip of fabric torn from an old covering. It could be useful for bandaging a wound.',
      'Mājas atslēga': 'A key you thought you had lost. It lets you get home without climbing through a window.',
      'Veiksmes monēta': 'A coin found in the dust. It looks like a lucky charm.',
    }
    return descriptions[itemDescription.name] ?? value
  }
  return value
}

const storyScenes = [
  {
    title: '1. SCENĀRIJS — BRUKUMS',
    risk: 'danger',
    text: 'Kādā garlaicīgā piektdienas vakarā tu ieklīsti dziļi vecos pazemes tuneļos. Eju ir tik daudz, ka nav iespējams saprast, cik to ir un kur tu atrodies. Tu esi apmaldījies. Apkārt ir tumšs un auksts; vienīgā gaisma ir telefona lukturis. Tevi ieskauj senas viduslaiku sienas. Pēkšņi tālumā kaut kas norīb. Grīda nodreb. Putekļu mākonis strauji tuvojas, un tev priekšā ir trīs ejas.',
    choices: [
      {
        label: 'Ieskrien sienā nobloķētajā ejā.',
        aftermath: 'Tu notici pasakām un pilnā sparā ieskrien sienā. Siena nepakustas. Tu zaudē samaņu un guli, cerot, ka kāds tevi izglābs.',
        requiredItem: 'Ticība pasakām',
        ending: {
          title: 'SIENA UZVAR',
          text: 'Siena paliek neuzveikta. Tavs pazemes piedzīvojums beidzas, vēl īsti nesācies.',
          kind: 'dead',
        },
      },
      {
        label: 'Paslēpies zem arkas otrajā ejā.',
        aftermath: 'Tu palien zem arkas. Brukums nodārd tev garām, bet akmens iztur. Kad putekļi nosēžas, tu joprojām esi dzīvs.',
        next: 1,
      },
      {
        label: 'Skrien pa garo eju prom no putekļiem.',
        aftermath: 'Tu meties skriet tumsā. Eja dreb, bet tu apsteidz brukumu un ieskrien drošākā telpā.',
        reward: { name: 'Metāla gabals', chance: 0.65 },
        next: 1,
      },
    ],
  },
  {
    title: '2. NOTIKUMS — VĒJA VIRZIENS',
    risk: 'safe',
    text: 'Tu veiksmīgi nokļuvi drošākā vietā. Te nekas nebrūk. Pagaidi… te pūš vējš. Tas plūst cauri tuneļiem no ārpuses. Varbūt vējš parādīs ceļu ārā.',
    choices: [
      {
        label: 'Seko vējam svaiga gaisa virzienā.',
        aftermath: 'Tu seko vējam pa klusu, šauru eju. Gaiss kļūst siltāks un smaržo pēc lietus.',
        reward: { name: 'Veiksmes monēta', chance: 0.35 },
        next: 2,
      },
      {
        label: 'Ej atpakaļ brukuma virzienā.',
        aftermath: 'Tunelis atkal iekrakšķas. Šoreiz griesti iebrūk, pirms tu paspēj apgriezties.',
        ending: {
          title: 'APRAKTS PAZEMĒ',
          text: 'Tu devies atpakaļ brukuma virzienā. Vecie tuneļi aizvēra ceļu uz visiem laikiem.',
          kind: 'dead',
        },
      },
      {
        label: 'Izlien caur zemu apkopes eju.',
        aftermath: 'Tu izlien cauri šaurai spraugai un nokļūsti aiz bojātajiem tuneļiem. Priekšā vāji spīd zaļgana gaisma.',
        reward: { name: 'Auduma strēmele', chance: 0.5 },
        next: 2,
      },
    ],
  },
  {
    title: '3. NOTIKUMS — IZEJA',
    risk: 'caution',
    text: 'Tur priekšā ir gaisma! Paga… ārā taču ir nakts. Vējš tevi atveda līdz nelielai izejai, taču tā ir pārāk šaura, lai izlīstu. Blakus mētājas metāla gabals, un tunelis turpinās tālāk.',
    choices: [
      {
        label: 'Ar metāla gabalu atlauz izeju platāku.',
        aftermath: 'Tu iebāz metāla gabalu spraugā un paplašini izeju. Akmeņi virs galvas sakustas, bet tev izdodas izkļūt ārā.',
        requiredItem: 'Metāla gabals',
        next: 3,
      },
      {
        label: 'Ej tālāk un meklē citu izeju.',
        aftermath: 'Tu turpini ceļu, nevis riskē ar trauslo atveri. Platāka eja tevi izved ārā zem kokiem.',
        reward: { name: 'Mājas atslēga', chance: 0.25 },
        next: 3,
      },
      {
        label: 'Seko šaurajai ventilācijas ejai satiksmes skaņas virzienā.',
        aftermath: 'Eja ir šaura, bet tā ved līdz apkopes lūkai pie ceļa. Tu to atver un iznāc naktī.',
        next: 3,
      },
    ],
  },
  {
    title: '4. NOTIKUMS — PAZAUDĒTĀ ATSLĒGA',
    risk: 'caution',
    text: 'Beidzot esi ārā un dodies mājās. Pie durvīm pārmeklē kabatas. Mājas atslēgu nav! Gaismas ir izslēgtas, bet tu atceries, ka viens logs palika neaizslēgts.',
    choices: [
      {
        label: 'Atgriezies tuneļos un meklē atslēgas.',
        aftermath: 'Tu dodies atpakaļ pazemē meklēt atslēgas. Jau novājinātie tuneļi sāk brukt tev apkārt.',
        ending: {
          title: 'ATKAL PAZUDIS',
          text: 'Tuneļi iebrūk, pirms atrodi atslēgas. Atgriezties pazemē bija nepareizais ceļš uz mājām.',
          kind: 'dead',
        },
      },
      {
        label: 'Iekāp pa atvērto logu.',
        aftermath: 'Tu uzmanīgi ierāpies pa neaizslēgto logu un droši nokļūsti mājās.',
        next: 4,
      },
      {
        label: 'Izmanto ārā paslēpto rezerves atslēgu.',
        aftermath: 'Tu atceries rezerves atslēgu zem puķupoda. Tā joprojām ir tur, un durvis atveras bez kāpšanas.',
        requiredItem: 'Mājas atslēga',
        next: 4,
      },
    ],
  },
  {
    title: '5. NOTIKUMS — BEIDZOT MĀJĀS',
    risk: 'safe',
    text: 'Tu esi mājās. Adrenalīnam rimstoties, pamani, ka sāp kāja. Uz biksēm ir asinis, un nobrāzums izskatās sliktāk, nekā sākumā šķita.',
    choices: [
      {
        label: 'Nomazgā brūci un apsien to ar auduma strēmeli.',
        aftermath: 'Tu iztīri brūci un rūpīgi to apsien. Tā sūrst, bet asiņošana apstājas. Tu izdzīvoji tuneļos un parūpējies par sevi.',
        requiredItem: 'Auduma strēmele',
        ending: {
          title: 'TĀ BIJA LABA PIEKTDIENA',
          text: 'Tu tiki mājās, aprūpēji brūci un paliki dzīvs, lai izstāstītu šo piedzīvojumu. Tā bija laba piektdiena. Bija jautri.',
          kind: 'good',
        },
      },
      {
        label: 'Ignorē brūci un uzreiz ej gulēt.',
        aftermath: 'Tu ignorē asiņojošo brūci un apgulies. Sāpes pastiprinās, un istaba sāk griezties.',
        ending: {
          title: 'VAJADZĒJA BRŪCI IZTĪRĪT',
          text: 'Miegā brūce iekaisa. Piedzīvojums beidzas no sekām, no kurām būtu bijis viegli izvairīties.',
          kind: 'dead',
        },
      },
      {
        label: 'Piezvani draugam, lai palīdz iztīrīt un apsiet brūci.',
        aftermath: 'Draugs atbrauc ar aptieciņu un palīdz iztīrīt brūci. Tu esi drošībā, kāja sāp, un jau plāno mazāk bīstamu piektdienu.',
        ending: {
          title: 'TĀ BIJA LABA PIEKTDIENA',
          text: 'Tu tiki mājās, saņēmi palīdzību un paliki dzīvs, lai izstāstītu šo piedzīvojumu. Tā bija laba piektdiena. Bija jautri.',
          kind: 'good',
        },
      },
    ],
  },
]

const currentScene = computed(() =>
  scenarios.value.find((scenario) => scenario.id === currentScenarioId.value)
  ?? normalScenarios.value[0]
  ?? { title: '', risk_zone: 'caution', description: '', choices: [] },
)

function chooseScenarioForPlay(baseScenarioId) {
  const replacements = scenarios.value
    .filter((scenario) => scenario.replacement_for_scenario_id === baseScenarioId && scenario.spawn_chance > 0)
    .sort((left, right) => left.id - right.id)
  const roll = Math.random()
  let cumulativeChance = 0

  for (const replacement of replacements) {
    cumulativeChance += replacement.spawn_chance
    if (roll < cumulativeChance) return replacement
  }

  return scenarios.value.find((scenario) => scenario.id === baseScenarioId)
}

function hasValidChancePrecision(value) {
  return /^(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/.test(String(value))
}

function formatChancePercent(chance) {
  return `${Number(chance * 100).toFixed(8).replace(/\.?0+$/, '')}%`
}
const inventorySlots = computed(() =>
  Array.from({ length: Math.max(6, inventory.value.length) }, (_, index) => inventory.value[index] ?? null),
)
const selectedItemDescription = computed(() =>
  selectedInventoryItem.value
    ? translateStoryText(selectedInventoryItem.value.description)
    : selectedLanguage.value === 'EN' ? 'Select an item to view its description.' : 'Izvēlies priekšmetu, lai apskatītu tā aprakstu.',
)
const aftermathDisplay = computed(() => {
  const translated = translateStoryText(aftermathText.value)
  if (!aftermathReward.value) return translated
  if (!aftermathReward.value.found) {
    return `${translated} ${selectedLanguage.value === 'EN' ? 'This time, you did not find an item.' : 'Šoreiz priekšmetu neizdevās atrast.'}`
  }
  const name = translateStoryText(aftermathReward.value.item.name)
  return `${translated} ${selectedLanguage.value === 'EN' ? `Found item: ${name}.` : `Atradi priekšmetu: ${name}.`}`
})
const formattedPlaytime = computed(() => {
  const seconds = Math.min(elapsedSeconds.value, 5999)
  return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`
})

function legacyStopGameTimer() {
  if (gameTimer !== null) {
    clearInterval(gameTimer)
    gameTimer = null
  }
}

function legacyStartGameTimer() {
  stopGameTimer()
  elapsedSeconds.value = 0
  gameStartedAt.value = Date.now()
  gameTimer = setInterval(() => {
    if (gameStartedAt.value !== null) {
      elapsedSeconds.value = Math.min(
        Math.floor((Date.now() - gameStartedAt.value) / 1000),
        99 * 3600 + 59 * 60 + 59,
      )
    }
  }, 250)
}

function legacyOpenScreen(screen) {
  currentScreen.value = screen
  if (screen === 'game') {
    currentSceneIndex.value = 0
    aftermathText.value = ''
    inventory.value = ['Ticība pasakām']
    pendingEnding.value = null
    pendingNextScene.value = null
    ending.value = null
    startGameTimer()
  }
}

function legacyGoBack() {
  stopGameTimer()
  gameStartedAt.value = null
  currentScreen.value = 'menu'
  currentSceneIndex.value = 0
  inventory.value = []
  pendingEnding.value = null
  pendingNextScene.value = null
  ending.value = null
}

function legacyToggleInventory() {
  currentScreen.value = currentScreen.value === 'inventory' ? 'game' : 'inventory'
  selectedInventoryItem.value = inventory.value[0] ?? null
}

function legacyToggleLanguage() {
  selectedLanguage.value = selectedLanguage.value === 'EN' ? 'LV' : 'EN'
}

function legacyChooseStoryOption(option) {
  if (option.requiredItem && !inventory.value.includes(option.requiredItem)) {
    return
  }

  aftermathText.value = option.aftermath
  aftermathReward.value = null
  pendingEnding.value = option.ending ?? null
  pendingNextScene.value = option.next ?? null
  if (option.reward) {
    if (Math.random() < option.reward.chance) {
      if (!inventory.value.includes(option.reward.name)) {
        inventory.value.push(option.reward.name)
      }
      aftermathText.value += ` Atradi priekšmetu: ${option.reward.name}.`
    } else {
      aftermathText.value += ' Šoreiz priekšmetu neizdevās atrast.'
    }
  }
  currentScreen.value = 'aftermath'
}

function legacyContinueAftermath() {
  if (pendingEnding.value) {
    ending.value = pendingEnding.value
    if (gameStartedAt.value !== null) {
      elapsedSeconds.value = Math.min(
        Math.floor((Date.now() - gameStartedAt.value) / 1000),
        99 * 3600 + 59 * 60 + 59,
      )
    }
    stopGameTimer()
    gameStartedAt.value = null
    currentScreen.value = 'end'
    return
  }

  currentSceneIndex.value = pendingNextScene.value ?? currentSceneIndex.value + 1
  pendingNextScene.value = null
  currentScreen.value = 'game'
}

async function apiRequest(path, options = {}) {
  const headers = { Accept: 'application/json', ...options.headers }
  if (options.body) headers['Content-Type'] = 'application/json'
  if (csrfToken.value) headers['X-CSRF-TOKEN'] = csrfToken.value
  const controller = new AbortController()
  const timeout = setTimeout(() => controller.abort(), 12000)

  try {
    const response = await fetch(`/game-api${path}`, {
      credentials: 'same-origin',
      ...options,
      headers,
      signal: controller.signal,
    })
    const responseText = response.status === 204 ? '' : await response.text()
    let payload = {}
    if (responseText) {
      try {
        payload = JSON.parse(responseText)
      } catch {
        throw new Error(`The game server returned an invalid response (${response.status}).`)
      }
    }

    if (!response.ok) {
      const errors = Object.values(payload.errors ?? {}).flat().join(' ')
      throw new Error(errors || payload.message || `Request failed (${response.status}).`)
    }

    if (payload.csrf_token) csrfToken.value = payload.csrf_token
    return payload
  } catch (error) {
    if (error.name === 'AbortError') {
      throw new Error('The game server did not respond. Check that the Laravel backend is running, then try again.')
    }
    if (error instanceof TypeError) {
      throw new Error('Could not connect to the game server. Check the backend address and try again.')
    }
    throw error
  } finally {
    clearTimeout(timeout)
  }
}

async function loadBootstrap() {
  const payload = await apiRequest('/bootstrap')
  scenarios.value = payload.scenarios
  items.value = payload.items
  currentUser.value = payload.user
}

function currentElapsedSeconds() {
  if (gameStartedAt.value !== null) {
    elapsedSeconds.value = Math.min(Math.floor((Date.now() - gameStartedAt.value) / 1000), 5999)
  }
  return elapsedSeconds.value
}

function stopGameTimer() {
  if (gameTimer !== null) clearInterval(gameTimer)
  gameTimer = null
  currentElapsedSeconds()
  gameStartedAt.value = null
}

function startGameTimer(initialSeconds = 0) {
  stopGameTimer()
  elapsedSeconds.value = Math.min(initialSeconds, 5999)
  gameStartedAt.value = Date.now() - elapsedSeconds.value * 1000
  lastAutosavedSecond = elapsedSeconds.value
  gameTimer = setInterval(() => {
    currentElapsedSeconds()
    if (
      gameSessionId.value
      && elapsedSeconds.value > 0
      && elapsedSeconds.value % 15 === 0
      && elapsedSeconds.value > lastAutosavedSecond
    ) {
      lastAutosavedSecond = elapsedSeconds.value
      void saveCurrentSession()
    }
  }, 1000)
}

async function saveCurrentSession(completed = false) {
  if (!gameSessionId.value || !currentScenarioId.value) return true
  const payload = {
    current_scenario_id: currentScenarioId.value,
    inventory: inventory.value.map((item) => item.id),
    elapsed_seconds: currentElapsedSeconds(),
    is_completed: completed,
  }
  saveError.value = ''

  try {
    await apiRequest(`/sessions/${gameSessionId.value}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    })
    lastAutosavedSecond = payload.elapsed_seconds
    return true
  } catch (error) {
    saveError.value = error.message
    return false
  }
}

async function openScreen(screen) {
  profileMenuOpen.value = false
  apiError.value = ''
  adminNotice.value = ''
  if (screen === 'login' && currentUser.value) {
    currentScreen.value = 'settings'
    return
  }
  if (screen === 'game') {
    if (!currentUser.value) {
      apiError.value = uiText('signInToStart')
      currentScreen.value = 'login'
      authMode.value = 'signup'
      return
    }
    await startNewGame()
    return
  }
  if (screen === 'continue') {
    if (!currentUser.value) {
      apiError.value = uiText('signInToContinue')
      currentScreen.value = 'login'
      authMode.value = 'login'
      return
    }
    try {
      const payload = await apiRequest('/sessions')
      savedSessionsFromApi.value = payload.sessions
      currentScreen.value = 'continue'
    } catch (error) {
      apiError.value = error.message
    }
    return
  }
  if (screen === 'admin') {
    if (!currentUser.value?.is_admin) {
      apiError.value = uiText('adminOnly')
      return
    }
    await loadAdminData()
    currentScreen.value = 'admin'
    return
  }
  currentScreen.value = screen
}

function toggleProfileMenu() {
  profileMenuOpen.value = !profileMenuOpen.value
}

async function startNewGame() {
  const startingScenario = normalScenarios.value[0]
  if (!startingScenario) {
    apiError.value = uiText('noScenarios')
    return
  }

  busy.value = true
  apiError.value = ''
  const scenarioToPlay = chooseScenarioForPlay(startingScenario.id)
  if (!scenarioToPlay) {
    busy.value = false
    apiError.value = uiText('noScenarios')
    return
  }
  const startingItem = scenarioToPlay.choices.find((choice) => choice.required_item)?.required_item ?? null
  try {
    const payload = await apiRequest('/sessions', {
      method: 'POST',
      body: JSON.stringify({
        current_scenario_id: scenarioToPlay.id,
        inventory: startingItem ? [startingItem.id] : [],
        elapsed_seconds: 0,
        is_completed: false,
      }),
    })
    gameSessionId.value = payload.session.id
    currentScenarioId.value = payload.session.scenario_id
    inventory.value = startingItem ? [startingItem] : []
    aftermathText.value = ''
    pendingEnding.value = null
    pendingNextScene.value = null
    ending.value = null
    currentScreen.value = 'game'
    startGameTimer()
  } catch (error) {
    apiError.value = error.message
  } finally {
    busy.value = false
  }
}

async function resumeSession(session) {
  busy.value = true
  apiError.value = ''
  try {
    const payload = await apiRequest(`/sessions/${session.id}`)
    const saved = payload.session
    gameSessionId.value = saved.id
    currentScenarioId.value = saved.scenario_id
    inventory.value = items.value.filter((item) => saved.inventory.includes(item.id))
    selectedInventoryItem.value = inventory.value[0] ?? null
    aftermathText.value = ''
    pendingEnding.value = null
    pendingNextScene.value = null
    ending.value = null
    currentScreen.value = 'game'
    startGameTimer(saved.elapsed_seconds)
  } catch (error) {
    apiError.value = error.message
  } finally {
    busy.value = false
  }
}

async function goBack() {
  if (currentScreen.value === 'game') await saveCurrentSession()
  stopGameTimer()
  currentScreen.value = 'menu'
}

function toggleInventory() {
  if (currentScreen.value !== 'inventory') {
    inventoryReturnScreen.value = currentScreen.value
    currentScreen.value = 'inventory'
  } else {
    currentScreen.value = inventoryReturnScreen.value
  }
  selectedInventoryItem.value = inventory.value[0] ?? null
}

function toggleLanguage() {
  selectedLanguage.value = selectedLanguage.value === 'EN' ? 'LV' : 'EN'
  localStorage.setItem('game-language', selectedLanguage.value)
}

async function submitAuth() {
  if (busy.value) return
  busy.value = true
  apiError.value = ''
  try {
    const isSignup = authMode.value === 'signup'
    const payload = {
      username: authForm.value.username,
      password: authForm.value.password,
      ...(isSignup
        ? {
            email: authForm.value.email,
            password_confirmation: authForm.value.password_confirmation,
          }
        : {}),
    }
    const result = await apiRequest(isSignup ? '/register' : '/login', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    currentUser.value = result.user
    authForm.value = { username: '', email: '', password: '', password_confirmation: '' }
    currentScreen.value = 'menu'
  } catch (error) {
    apiError.value = error.message
  } finally {
    busy.value = false
  }
}

async function logoutUser() {
  busy.value = true
  apiError.value = ''
  try {
    if (gameSessionId.value && currentScreen.value === 'game') await saveCurrentSession()
    await apiRequest('/logout', { method: 'POST' })
    await loadBootstrap()
    gameSessionId.value = null
    stopGameTimer()
    currentScreen.value = 'menu'
  } catch (error) {
    apiError.value = error.message
  } finally {
    busy.value = false
  }
}

function chooseStoryOption(option) {
  if (option.required_item && !inventory.value.some((item) => item.id === option.required_item.id)) return

  aftermathText.value = option.aftermath
  pendingEnding.value = option.ending_kind
    ? { title: option.ending_title, text: option.ending_text, kind: option.ending_kind }
    : null
  pendingNextScene.value = option.next_scenario_id

  if (option.reward_item && Math.random() < option.reward_chance) {
    if (!inventory.value.some((item) => item.id === option.reward_item.id)) {
      inventory.value.push(option.reward_item)
    }
    aftermathReward.value = { found: true, item: option.reward_item }
  } else if (option.reward_item) {
    aftermathReward.value = { found: false, item: null }
  }
  currentScreen.value = 'aftermath'
  void saveCurrentSession()
}

async function continueAftermath() {
  if (saveError.value) return
  if (pendingEnding.value) {
    if (!await saveCurrentSession(true)) return
    ending.value = pendingEnding.value
    stopGameTimer()
    currentScreen.value = 'end'
    return
  }

  const currentBaseScenarioId = currentScene.value.replacement_for_scenario_id ?? currentScenarioId.value
  const currentIndex = normalScenarios.value.findIndex((scenario) => scenario.id === currentBaseScenarioId)
  const nextNormalScenarioId = pendingNextScene.value
    ?? normalScenarios.value[currentIndex + 1]?.id
    ?? currentScenarioId.value
  const scenarioToPlay = chooseScenarioForPlay(nextNormalScenarioId)
  if (!scenarioToPlay) {
    apiError.value = uiText('noScenarios')
    return
  }
  currentScenarioId.value = scenarioToPlay.id
  pendingNextScene.value = null
  if (!await saveCurrentSession()) return
  currentScreen.value = 'game'
}

async function quitGame() {
  await saveCurrentSession()
  stopGameTimer()
  currentScreen.value = 'menu'
}

function newScenarioDraft() {
  adminNotice.value = ''
  editingScenarioId.value = null
  const replacementTarget = normalScenarios.value[0]
  scenarioDraft.value = {
    title: '',
    description: '',
    risk_zone: 'caution',
    sort_order: normalScenarios.value.length + 1,
    replacement_for_scenario_id: replacementTarget?.id ?? '',
    spawn_chance: replacementTarget ? '0.25' : '',
    choices: Array.from({ length: 3 }, () => ({
      label: '',
      aftermath: '',
      next_scenario_id: '',
      required_item_id: '',
      reward_item_id: '',
      reward_chance: '',
      ending_title: '',
      ending_text: '',
      ending_kind: '',
    })),
  }
}

function clearAdminFeedback() {
  apiError.value = ''
  adminNotice.value = ''
}

function setChoiceNextScenario(choice, scenarioId) {
  choice.next_scenario_id = scenarioId
  if (scenarioId) choice.ending_kind = ''
  clearAdminFeedback()
}

function setChoiceEnding(choice, endingKind) {
  choice.ending_kind = endingKind
  if (endingKind) choice.next_scenario_id = ''
  clearAdminFeedback()
}

function editScenario(scenario) {
  adminNotice.value = ''
  editingScenarioId.value = scenario.id
  scenarioDraft.value = {
    title: scenario.title,
    description: scenario.description,
    risk_zone: scenario.risk_zone,
    sort_order: scenario.sort_order,
    replacement_for_scenario_id: scenario.replacement_for_scenario_id ?? '',
    spawn_chance: scenario.spawn_chance ?? '',
    choices: scenario.choices.map((choice) => ({
      label: choice.label,
      aftermath: choice.aftermath,
      next_scenario_id: choice.next_scenario_id ?? '',
      required_item_id: choice.required_item?.id ?? '',
      reward_item_id: choice.reward_item?.id ?? '',
      reward_chance: choice.reward_chance ?? '',
      ending_title: choice.next_scenario_id ? '' : choice.ending_title ?? '',
      ending_text: choice.next_scenario_id ? '' : choice.ending_text ?? '',
      ending_kind: choice.next_scenario_id ? '' : choice.ending_kind ?? '',
    })),
  }
}

async function loadAdminData() {
  try {
    const payload = await apiRequest('/admin')
    adminScenarios.value = payload.scenarios
    adminItems.value = payload.items
    newScenarioDraft()
  } catch (error) {
    apiError.value = error.message
  }
}

async function saveScenario() {
  apiError.value = ''
  adminNotice.value = ''
  const draft = scenarioDraft.value
  if (!draft.title.trim() || !draft.description.trim()) {
    apiError.value = uiText('scenarioDetailsRequired')
    return
  }
  if (draft.sort_order === '' || !Number.isInteger(Number(draft.sort_order)) || Number(draft.sort_order) < 0) {
    apiError.value = uiText('scenarioOrderInvalid')
    return
  }
  const hasReplacementTarget = Boolean(draft.replacement_for_scenario_id)
  const hasSpawnChance = draft.spawn_chance !== '' && draft.spawn_chance !== null
  if (hasReplacementTarget !== hasSpawnChance) {
    apiError.value = uiText('replacementDetailsRequired')
    return
  }
  if (
    hasSpawnChance
    && (!Number.isFinite(Number(draft.spawn_chance)) || Number(draft.spawn_chance) < 0 || Number(draft.spawn_chance) > 1)
  ) {
    apiError.value = uiText('spawnChanceInvalid')
    return
  }
  if (hasSpawnChance && !hasValidChancePrecision(draft.spawn_chance)) {
    apiError.value = uiText('spawnChancePrecision')
    return
  }
  for (const [index, choice] of draft.choices.entries()) {
    const choiceNumber = index + 1
    if (!choice.label.trim() || !choice.aftermath.trim()) {
      apiError.value = uiText('choiceDetailsRequired').replace(':', ` ${choiceNumber}:`)
      return
    }

    const hasNextScenario = Boolean(choice.next_scenario_id)
    const hasEnding = Boolean(choice.ending_kind)
    if (hasNextScenario === hasEnding) {
      apiError.value = uiText('choiceRouteRequired').replace(':', ` ${choiceNumber}:`)
      return
    }
    if (hasEnding && (!choice.ending_title.trim() || !choice.ending_text.trim())) {
      apiError.value = uiText('choiceEndingRequired').replace(':', ` ${choiceNumber}:`)
      return
    }

    const hasRewardItem = Boolean(choice.reward_item_id)
    const hasRewardChance = choice.reward_chance !== '' && choice.reward_chance !== null
    if (hasRewardItem !== hasRewardChance) {
      apiError.value = uiText('choiceRewardRequired').replace(':', ` ${choiceNumber}:`)
      return
    }
    if (hasRewardChance && (!Number.isFinite(Number(choice.reward_chance)) || Number(choice.reward_chance) < 0 || Number(choice.reward_chance) > 1)) {
      apiError.value = uiText('choiceRewardChanceInvalid').replace(':', ` ${choiceNumber}:`)
      return
    }
    if (hasRewardChance && !hasValidChancePrecision(choice.reward_chance)) {
      apiError.value = uiText('choiceRewardPrecision').replace(':', ` ${choiceNumber}:`)
      return
    }
  }

  busy.value = true
  const data = {
    title: draft.title,
    description: draft.description,
    risk_zone: draft.risk_zone,
    sort_order: Number(draft.sort_order),
    replacement_for_scenario_id: draft.replacement_for_scenario_id ? Number(draft.replacement_for_scenario_id) : null,
    spawn_chance: draft.spawn_chance === '' ? null : Number(draft.spawn_chance),
    choices: draft.choices.map((choice) => ({
      label: choice.label,
      aftermath: choice.aftermath,
      next_scenario_id: choice.next_scenario_id ? Number(choice.next_scenario_id) : null,
      required_item_id: choice.required_item_id ? Number(choice.required_item_id) : null,
      reward_item_id: choice.reward_item_id ? Number(choice.reward_item_id) : null,
      reward_chance: choice.reward_chance === '' ? null : Number(choice.reward_chance),
      ending_title: choice.ending_title || null,
      ending_text: choice.ending_text || null,
      ending_kind: choice.ending_kind || null,
    })),
  }
  try {
    const path = editingScenarioId.value
      ? `/admin/scenarios/${editingScenarioId.value}`
      : '/admin/scenarios'
    await apiRequest(path, {
      method: editingScenarioId.value ? 'PUT' : 'POST',
      body: JSON.stringify(data),
    })
    await loadAdminData()
    adminNotice.value = uiText('scenarioSaved')
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  } finally {
    busy.value = false
  }
}

async function deleteScenario(scenario) {
  if (!window.confirm(`${uiText('deleteScenarioConfirm')} “${scenario.title}”?`)) return
  adminNotice.value = ''
  try {
    await apiRequest(`/admin/scenarios/${scenario.id}`, { method: 'DELETE' })
    await loadAdminData()
    adminNotice.value = uiText('scenarioDeleted')
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  }
}

function editItem(item) {
  adminNotice.value = ''
  editingItemId.value = item.id
  itemDraft.value = { name: item.name, description: item.description }
}

async function saveItem() {
  busy.value = true
  apiError.value = ''
  adminNotice.value = ''
  try {
    const path = editingItemId.value ? `/admin/items/${editingItemId.value}` : '/admin/items'
    await apiRequest(path, {
      method: editingItemId.value ? 'PUT' : 'POST',
      body: JSON.stringify(itemDraft.value),
    })
    editingItemId.value = null
    itemDraft.value = { name: '', description: '' }
    await loadAdminData()
    adminNotice.value = uiText('itemSaved')
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  } finally {
    busy.value = false
  }
}

async function deleteItem(item) {
  if (!window.confirm(`Dzēst priekšmetu “${item.name}”?`)) return
  adminNotice.value = ''
  try {
    await apiRequest(`/admin/items/${item.id}`, { method: 'DELETE' })
    await loadAdminData()
    adminNotice.value = uiText('itemDeleted')
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  }
}

onMounted(async () => {
  const savedLanguage = localStorage.getItem('game-language')
  if (savedLanguage === 'EN' || savedLanguage === 'LV') selectedLanguage.value = savedLanguage
  try {
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  }
})

onUnmounted(stopGameTimer)
</script>

<template>
  <div class="app-shell">
    <div class="screen-frame">
      <header v-if="!['menu', 'continue', 'aftermath', 'end', 'login', 'settings', 'inventory'].includes(currentScreen)" class="topbar topbar-compact">
        <button class="ghost-button" @click="goBack">{{ uiText('back') }}</button>
        <span class="brand subtle">{{ currentScreen === 'game' ? uiText('game') : currentScreen === 'end' ? uiText('gameEnd') : uiText('adminScreen') }}</span>
      </header>

      <main :class="['screen-body', { 'screen-body-flush': ['login', 'settings', 'inventory'].includes(currentScreen) }]">
        <section v-if="currentScreen === 'menu'" class="menu-view">
          <div class="menu-panel">
            <div class="profile-menu">
              <button
                class="menu-mode"
                type="button"
                :aria-label="currentUser ? `${currentUser.name} account menu` : 'Account menu'"
                :aria-expanded="profileMenuOpen"
                aria-haspopup="true"
                @click="toggleProfileMenu"
              >
                {{ userInitial }}
              </button>
              <div v-if="profileMenuOpen" class="profile-menu-options">
                <span v-if="currentUser" class="profile-menu-name">{{ currentUser.name }}</span>
                <button type="button" @click="openScreen(currentUser ? 'settings' : 'login')">
                  {{ currentUser ? uiText('account') : uiText('logIn') }}
                </button>
                <button v-if="currentUser?.is_admin" type="button" @click="openScreen('admin')">
                  {{ uiText('admin') }}
                </button>
              </div>
            </div>
            <div class="menu-layout">
              <div class="menu-left">
                <div class="game-word">GAME</div>
                <div class="menu-grid">
                  <button
                    v-for="button in menuButtons"
                    :key="button.id"
                    :class="['menu-button', button.accent === 'danger' ? 'danger' : '']"
                    @click="openScreen(button.id)"
                  >
                    {{ button.label }}
                  </button>
                </div>
                <p v-if="currentUser" class="account-status">{{ uiText('loggedIn') }} {{ currentUser.username }}</p>
                <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
              </div>

              <div class="menu-right">
                <p class="menu-copy">
                  {{ selectedLanguage === 'EN'
                    ? 'Lost beneath the city. Find your way through the tunnels and make it home.'
                    : 'Pazudis pazemē. Atrodi ceļu cauri tuneļiem un tiec mājās.' }}
                </p>
                <p class="menu-copy accent">
                  {{ selectedLanguage === 'EN'
                    ? 'Every choice matters. Some paths are safe, some are dangerous, and not every story ends well.'
                    : 'Katrai izvēlei ir nozīme. Daži ceļi ir droši, citi bīstami, un ne katrs stāsts beidzas labi.' }}
                </p>
              </div>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'login'" class="panel-view login-view">
          <div class="login-card">
            <button class="auth-return" type="button" @click="goBack">{{ uiText('returnMenu') }}</button>
            <form class="login-form" @submit.prevent="submitAuth">
              <input v-model="authForm.username" autocomplete="username" name="username" :placeholder="uiText('username')" required />
              <input
                v-if="authMode === 'signup'"
                v-model="authForm.email"
                autocomplete="email"
                name="email"
                :placeholder="uiText('email')"
                required
                type="email"
              />
              <input
                v-model="authForm.password"
                autocomplete="new-password"
                name="password"
                :placeholder="uiText('password')"
                required
                type="password"
              />
              <input
                v-if="authMode === 'signup'"
                v-model="authForm.password_confirmation"
                autocomplete="new-password"
                name="password_confirmation"
                :placeholder="uiText('confirmPassword')"
                required
                type="password"
              />
              <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
              <button class="auth-submit" type="submit" :disabled="busy">
                {{ busy ? uiText('wait') : authMode === 'signup' ? uiText('createAccount') : uiText('logIn') }}
              </button>
            </form>
            <div class="login-actions">
              <button
                :class="{ selected: authMode === 'signup' }"
                type="button"
                @click="authMode = 'signup'"
              >
                {{ uiText('signUp') }}
              </button>
              <button
                :class="{ selected: authMode === 'login' }"
                type="button"
                @click="authMode = 'login'"
              >
                {{ uiText('logIn') }}
              </button>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'settings'" class="panel-view settings-view">
          <div class="settings-actions">
            <button class="settings-button" @click="toggleLanguage">
              {{ uiText('language') }} <span class="language-code">{{ selectedLanguage }}</span>
            </button>
            <button class="settings-button" @click="goBack">{{ uiText('return') }}</button>
            <button v-if="currentUser" class="settings-button logout" :disabled="busy" @click="logoutUser">{{ uiText('logOut') }}</button>
            <button v-else class="settings-button logout" @click="openScreen('login')">{{ uiText('logIn') }}</button>
            <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
          </div>
        </section>

        <section v-else-if="currentScreen === 'continue'" class="panel-view continue-view">
          <div class="continue-panel">
            <button
              v-for="session in savedSessionsFromApi"
              :key="session.id"
              class="continue-row"
              :disabled="busy"
              @click="resumeSession(session)"
            >
              <span class="continue-text">{{ translateStoryText(session.scenario_description) }}</span>
              <span class="continue-time">{{ session.playtime }}</span>
            </button>

            <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
            <p v-else-if="!savedSessionsFromApi.length" class="continue-empty">{{ uiText('noSaves') }}</p>
            <p v-if="savedSessionsFromApi.length" class="continue-limit">{{ uiText('saveLimit') }}</p>
            <button class="return-button" @click="goBack">{{ uiText('return') }}</button>
          </div>
        </section>

        <section v-else-if="currentScreen === 'admin'" class="panel-view admin-view">
          <div class="panel-card admin-card">
            <div class="admin-heading">
              <div>
                <p class="eyebrow">Administratora panelis</p>
                <h2>{{ uiText('adminHeading') }}</h2>
              </div>
              <button type="button" @click="goBack">{{ uiText('return') }}</button>
            </div>
            <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
            <p v-if="adminNotice" class="admin-notice" role="status">{{ adminNotice }}</p>
            <div class="admin-layout">
              <section class="admin-list">
                <div class="admin-heading">
                  <h3>{{ uiText('scenarios') }}</h3>
                  <button type="button" @click="newScenarioDraft">{{ uiText('new') }}</button>
                </div>
                <article v-for="scenario in adminScenarios" :key="scenario.id" class="admin-entry">
                  <div>
                    <strong>{{ scenario.title }}</strong>
                    <span v-if="scenario.replacement_for_scenario_id" class="scenario-reachability is-unreachable">
                      {{ uiText('randomReplacement') }} — {{ scenario.replacement_for_scenario?.title ?? adminScenarios.find((entry) => entry.id === scenario.replacement_for_scenario_id)?.title }} ({{ formatChancePercent(scenario.spawn_chance) }})
                    </span>
                    <span :class="['scenario-reachability', reachableScenarioIds.has(scenario.id) ? 'is-reachable' : 'is-unreachable']">
                      {{ reachableScenarioIds.has(scenario.id) ? uiText('reachable') : uiText('unreachable') }}
                    </span>
                    <p>{{ scenario.description }}</p>
                  </div>
                  <div class="admin-entry-actions">
                    <button type="button" @click="editScenario(scenario)">{{ uiText('edit') }}</button>
                    <button type="button" @click="deleteScenario(scenario)">{{ uiText('delete') }}</button>
                  </div>
                </article>
              </section>

              <form v-if="scenarioDraft" class="admin-editor" novalidate @submit.prevent="saveScenario" @input="clearAdminFeedback">
                <h3>{{ editingScenarioId ? uiText('editScenario') : uiText('newScenario') }}</h3>
                <label>{{ uiText('scenarioName') }}<input v-model="scenarioDraft.title" required maxlength="255" /></label>
                <label>{{ uiText('description') }}<textarea v-model="scenarioDraft.description" required rows="4" /></label>
                <p class="admin-help">{{ uiText('choiceRule') }}</p>
                <div class="admin-inline-fields">
                  <label>{{ uiText('replacesScenario') }}
                    <select
                      :value="scenarioDraft.replacement_for_scenario_id"
                      @change="setReplacementTarget(scenarioDraft, $event.target.value)"
                    >
                      <option value="">{{ uiText('standardScenario') }}</option>
                      <option v-for="option in normalScenarios" :key="option.id" :value="option.id">
                        {{ option.title }}
                      </option>
                    </select>
                  </label>
                  <label>{{ uiText('riskZone') }}
                    <select v-model="scenarioDraft.risk_zone">
                      <option value="safe">{{ uiText('safe') }}</option>
                      <option value="caution">{{ uiText('caution') }}</option>
                      <option value="danger">{{ uiText('danger') }}</option>
                    </select>
                  </label>
                  <label v-if="!scenarioDraft.replacement_for_scenario_id">
                    {{ uiText('order') }}<input v-model.number="scenarioDraft.sort_order" type="number" min="0" required />
                  </label>
                </div>
                <label v-if="scenarioDraft.replacement_for_scenario_id">{{ uiText('spawnChance') }}
                  <input v-model="scenarioDraft.spawn_chance" type="number" min="0" max="1" step="0.01" required />
                </label>
                <p v-if="scenarioDraft.replacement_for_scenario_id" class="admin-help">{{ uiText('spawnChanceHelp') }}</p>
                <fieldset v-for="(choice, index) in scenarioDraft.choices" :key="index" class="choice-editor">
                  <legend>{{ uiText('choice') }} {{ index + 1 }}</legend>
                  <label>{{ uiText('choiceText') }}<input v-model="choice.label" required /></label>
                  <label>{{ uiText('consequence') }}<textarea v-model="choice.aftermath" required rows="2" /></label>
                  <div class="admin-inline-fields">
                    <label>{{ uiText('nextScenario') }}
                      <select
                        :value="choice.next_scenario_id"
                        @change="setChoiceNextScenario(choice, $event.target.value)"
                      >
                        <option value="">{{ uiText('noValue') }}</option>
                        <option v-for="option in normalScenarios" :key="option.id" :value="option.id">
                          {{ option.title }}
                        </option>
                      </select>
                    </label>
                    <label>{{ uiText('endingType') }}
                      <select
                        :value="choice.ending_kind"
                        @change="setChoiceEnding(choice, $event.target.value)"
                      >
                        <option value="">{{ uiText('noValue') }}</option>
                        <option value="good">{{ uiText('goodEnding') }}</option>
                        <option value="dead">{{ uiText('badEnding') }}</option>
                      </select>
                    </label>
                  </div>
                  <div class="admin-inline-fields">
                    <label>{{ uiText('requiredItem') }}
                      <select v-model="choice.required_item_id">
                        <option value="">{{ uiText('noValue') }}</option>
                        <option v-for="item in adminItems" :key="item.id" :value="item.id">{{ item.name }}</option>
                      </select>
                    </label>
                    <label>{{ uiText('rewardItem') }}
                      <select v-model="choice.reward_item_id">
                        <option value="">{{ uiText('noValue') }}</option>
                        <option v-for="item in adminItems" :key="item.id" :value="item.id">{{ item.name }}</option>
                      </select>
                    </label>
                  </div>
                  <label>{{ uiText('rewardChance') }}
                    <input v-model="choice.reward_chance" type="number" min="0" max="1" step="0.01" />
                  </label>
                  <p class="admin-help">{{ uiText('rewardChanceHelp') }}</p>
                  <label>{{ uiText('endingTitle') }}<input v-model="choice.ending_title" /></label>
                  <label>{{ uiText('endingText') }}<textarea v-model="choice.ending_text" rows="2" /></label>
                </fieldset>
                <button type="submit" :disabled="busy">{{ busy ? uiText('save') : uiText('saveScenario') }}</button>
              </form>

              <section class="admin-list item-admin">
                <h3>{{ uiText('items') }}</h3>
                <article v-for="item in adminItems" :key="item.id" class="admin-entry">
                  <div><strong>{{ item.name }}</strong><p>{{ item.description }}</p></div>
                  <div class="admin-entry-actions">
                    <button type="button" @click="editItem(item)">{{ uiText('edit') }}</button>
                    <button type="button" @click="deleteItem(item)">{{ uiText('delete') }}</button>
                  </div>
                </article>
                <form class="item-editor" @submit.prevent="saveItem">
                  <h3>{{ editingItemId ? uiText('editItem') : uiText('newItem') }}</h3>
                  <label>{{ uiText('itemName') }}<input v-model="itemDraft.name" required maxlength="255" /></label>
                  <label>{{ uiText('description') }}<textarea v-model="itemDraft.description" required rows="3" /></label>
                  <div class="admin-entry-actions">
                    <button type="submit" :disabled="busy">{{ busy ? uiText('save') : uiText('saveItem') }}</button>
                    <button v-if="editingItemId" type="button" @click="editingItemId = null; itemDraft = { name: '', description: '' }">{{ uiText('cancel') }}</button>
                  </div>
                </form>
              </section>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'game'" :class="['game-view', `zone-${currentScene.risk_zone}`]">
          <div class="story-card">
            <p class="scene-indicator">{{ translateStoryText(currentScene.title) }}</p>
            <p class="story-text">{{ translateStoryText(currentScene.description) }}</p>

            <div class="choice-list">
              <button
                v-for="(choice, index) in currentScene.choices"
                :key="`${choice.label}-${index}`"
                :class="['choice-button', { locked: choice.required_item && !inventory.some((item) => item.id === choice.required_item.id) }]"
                :disabled="choice.required_item && !inventory.some((item) => item.id === choice.required_item.id)"
                :title="choice.required_item && !inventory.some((item) => item.id === choice.required_item.id) ? `${uiText('required')} ${translateStoryText(choice.required_item.name)}` : ''"
                @click="chooseStoryOption(choice)"
              >
                {{ translateStoryText(choice.label) }}
                <span v-if="choice.required_item && !inventory.some((item) => item.id === choice.required_item.id)" class="choice-requirement">
                  {{ uiText('required') }} {{ translateStoryText(choice.required_item.name) }}
                </span>
              </button>
            </div>

            <p v-if="saveError" class="api-error" role="alert">
              {{ saveError }}
              <button type="button" @click="saveCurrentSession">{{ uiText('retrySave') }}</button>
            </p>
            <div class="game-controls">
              <button class="game-control-button" @click="toggleInventory">{{ uiText('inventory') }}</button>
              <button class="game-control-button" @click="quitGame">{{ uiText('exit') }}</button>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'inventory'" class="inventory-view">
          <div class="inventory-screen">
            <div class="inventory-header">
              <h2 class="inventory-title">{{ uiText('inventory') }}</h2>
              <button class="inventory-return" @click="toggleInventory">{{ uiText('backToGame') }}</button>
            </div>
            <div class="inventory-content">
              <div class="inventory-slots" aria-label="Collected items">
                <button
                  v-for="(item, index) in inventorySlots"
                  :key="index"
                  :class="['inventory-slot', { selected: selectedInventoryItem === item && item }]"
                  :disabled="!item"
                  :aria-label="item?.name ?? 'Empty inventory slot'"
                  @click="selectedInventoryItem = item"
                >
                  {{ item ? uiText('item') : '' }}
                </button>
              </div>
              <div class="inventory-details">
                <div class="inventory-item-preview">
                  {{ selectedInventoryItem ? translateStoryText(selectedInventoryItem.name) : uiText('item') }}
                </div>
                <p class="inventory-description">{{ selectedItemDescription }}</p>
              </div>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'aftermath'" class="aftermath-view">
          <div class="aftermath-panel" role="dialog" aria-modal="true" aria-label="Choice aftermath">
            <p>{{ aftermathDisplay }}</p>
            <p v-if="saveError" class="api-error" role="alert">{{ saveError }}</p>
            <button v-if="saveError" class="aftermath-next" @click="saveCurrentSession">{{ uiText('retrySave') }}</button>
            <button v-else class="aftermath-next" @click="continueAftermath">{{ uiText('next') }}</button>
          </div>
        </section>

        <section v-else-if="currentScreen === 'end'" class="panel-view end-view">
          <div :class="['end-screen', ending?.kind === 'dead' ? 'ending-dead' : 'ending-good']">
            <h2 class="end-title">{{ ending ? translateStoryText(ending.title) : 'GAME END' }}</h2>
            <p v-if="ending" class="ending-text">{{ translateStoryText(ending.text) }}</p>
            <div class="end-items" aria-label="Items collected during the story">
              <p v-if="inventory.length === 0" class="empty-inventory">{{ uiText('emptyInventory') }}</p>
              <p v-for="item in inventory" :key="item.id" class="end-item">{{ translateStoryText(item.name) }}</p>
            </div>
            <div class="playtime-section">
              <h3>{{ uiText('playtime') }}</h3>
              <div class="playtime-row">
                <span>{{ uiText('totalPlayed') }}</span>
                <time>{{ formattedPlaytime }}</time>
              </div>
              </div>
              <button class="end-button" @click="goBack">{{ uiText('end') }}</button>
          </div>
        </section>
      </main>
    </div>

  </div>
</template>
