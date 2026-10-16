<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

const currentScreen = ref('menu')
const currentSceneIndex = ref(0)
const scenarios = ref([])
const items = ref([])
const currentUser = ref(null)
const currentScenarioId = ref(null)
const gameSessionId = ref(null)
const savedSessionsFromApi = ref([])
const apiError = ref('')
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
  { id: 'login', label: 'SIGN UP/LOG IN' },
  { id: 'game', label: 'START' },
  { id: 'continue', label: 'CONTINUE' },
  { id: 'settings', label: 'EXIT', accent: 'danger' },
  ...(currentUser.value?.is_admin ? [{ id: 'admin', label: 'ADMIN PANEL' }] : []),
])

const itemDescriptions = {
  'Believing in fairytales': 'Stubborn belief that the wall will open if you run into it hard enough. Not really useful, but it\'s memorable.',
  'Piece of fabric': 'It\'s a ripped piece of fabric from a rug. It can be used as a bandage...',
  'House keys': 'A key that you thought you lost. Now you dont have to climb through the window to get home.',
  'Coin of luck': 'A coin that was found in dust. It makes a great talisman .',
}

const savedSessions = [
  {
    description: 'IT HAS SURVIVED NOT ONLY MANY DECADES, BUT ALSO THE LEAP INTO ELECTRONIC TYPESSETTING, REMAINING ESSENTIALLY UNCHANGED.',
    playtime: 'XX:XX',
  },
  {
    description: 'IT HAS SURVIVED NOT ONLY MANY DECADES, BUT ALSO THE LEAP INTO ELECTRONIC TYPESSETTING, REMAINING ESSENTIALLY UNCHANGED.',
    playtime: 'XX:XX',
  },
  {
    description: 'IT HAS SURVIVED NOT ONLY MANY DECADES, BUT ALSO THE LEAP INTO ELECTRONIC TYPESSETTING, REMAINING ESSENTIALLY UNCHANGED.',
    playtime: 'XX:XX',
  },
]

const adminCards = [
  { title: 'Active players', value: '184' },
  { title: 'Missions finished', value: '53' },
  { title: 'Scenar', value: '12' },
  { title: 'System status', value: 'Normal' },
]

const storyScenes = [
  {
    title: 'Scenario 1 - the collapse',
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
  ?? scenarios.value[0]
  ?? { title: '', risk_zone: 'caution', description: '', choices: [] },
)
const inventorySlots = computed(() =>
  Array.from({ length: Math.max(6, inventory.value.length) }, (_, index) => inventory.value[index] ?? null),
)
const selectedItemDescription = computed(() =>
  selectedInventoryItem.value?.description ?? 'Select an item to view its description.',
)
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
  const response = await fetch(`/game-api${path}`, {
    credentials: 'same-origin',
    ...options,
    headers,
  })
  const payload = response.status === 204 ? {} : await response.json()

  if (!response.ok) {
    const errors = Object.values(payload.errors ?? {}).flat().join(' ')
    throw new Error(errors || payload.message || `Request failed (${response.status}).`)
  }

  if (payload.csrf_token) csrfToken.value = payload.csrf_token
  return payload
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
  apiError.value = ''
  if (screen === 'game') {
    if (!currentUser.value) {
      apiError.value = 'Pieslēdzies vai reģistrējies, lai sāktu un saglabātu spēli.'
      currentScreen.value = 'login'
      authMode.value = 'signup'
      return
    }
    await startNewGame()
    return
  }
  if (screen === 'continue') {
    if (!currentUser.value) {
      apiError.value = 'Pieslēdzies, lai skatītu saglabātās spēles.'
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
      apiError.value = 'Šī lapa pieejama tikai administratoram.'
      return
    }
    await loadAdminData()
    currentScreen.value = 'admin'
    return
  }
  currentScreen.value = screen
}

async function startNewGame() {
  if (!scenarios.value.length) {
    apiError.value = 'Nav pieejamu scenāriju. Palaid datubāzes sēklu komandu.'
    return
  }

  busy.value = true
  apiError.value = ''
  try {
    const payload = await apiRequest('/sessions', {
      method: 'POST',
      body: JSON.stringify({
        current_scenario_id: scenarios.value[0].id,
        inventory: [],
        elapsed_seconds: 0,
        is_completed: false,
      }),
    })
    gameSessionId.value = payload.session.id
    currentScenarioId.value = payload.session.scenario_id
    inventory.value = []
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
}

async function submitAuth() {
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
    await apiRequest(isSignup ? '/register' : '/login', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    await loadBootstrap()
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
    aftermathText.value += ` Atradi priekšmetu: ${option.reward_item.name}.`
  } else if (option.reward_item) {
    aftermathText.value += ' Šoreiz priekšmetu neizdevās atrast.'
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

  const currentIndex = scenarios.value.findIndex((scenario) => scenario.id === currentScenarioId.value)
  currentScenarioId.value = pendingNextScene.value
    ?? scenarios.value[currentIndex + 1]?.id
    ?? currentScenarioId.value
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
  editingScenarioId.value = null
  scenarioDraft.value = {
    title: '',
    description: '',
    risk_zone: 'caution',
    sort_order: adminScenarios.value.length + 1,
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

function editScenario(scenario) {
  editingScenarioId.value = scenario.id
  scenarioDraft.value = {
    title: scenario.title,
    description: scenario.description,
    risk_zone: scenario.risk_zone,
    sort_order: scenario.sort_order,
    choices: scenario.choices.map((choice) => ({
      label: choice.label,
      aftermath: choice.aftermath,
      next_scenario_id: choice.next_scenario_id ?? '',
      required_item_id: choice.required_item?.id ?? '',
      reward_item_id: choice.reward_item?.id ?? '',
      reward_chance: choice.reward_chance ?? '',
      ending_title: choice.ending_title ?? '',
      ending_text: choice.ending_text ?? '',
      ending_kind: choice.ending_kind ?? '',
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
  busy.value = true
  apiError.value = ''
  const draft = scenarioDraft.value
  const data = {
    title: draft.title,
    description: draft.description,
    risk_zone: draft.risk_zone,
    sort_order: Number(draft.sort_order),
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
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  } finally {
    busy.value = false
  }
}

async function deleteScenario(scenario) {
  if (!window.confirm(`Dzēst scenāriju “${scenario.title}”?`)) return
  try {
    await apiRequest(`/admin/scenarios/${scenario.id}`, { method: 'DELETE' })
    await loadAdminData()
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  }
}

function editItem(item) {
  editingItemId.value = item.id
  itemDraft.value = { name: item.name, description: item.description }
}

async function saveItem() {
  busy.value = true
  apiError.value = ''
  try {
    const path = editingItemId.value ? `/admin/items/${editingItemId.value}` : '/admin/items'
    await apiRequest(path, {
      method: editingItemId.value ? 'PUT' : 'POST',
      body: JSON.stringify(itemDraft.value),
    })
    editingItemId.value = null
    itemDraft.value = { name: '', description: '' }
    await loadAdminData()
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  } finally {
    busy.value = false
  }
}

async function deleteItem(item) {
  if (!window.confirm(`Dzēst priekšmetu “${item.name}”?`)) return
  try {
    await apiRequest(`/admin/items/${item.id}`, { method: 'DELETE' })
    await loadAdminData()
    await loadBootstrap()
  } catch (error) {
    apiError.value = error.message
  }
}

onMounted(async () => {
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
        <button class="ghost-button" @click="goBack">Atgriezties</button>
        <span class="brand subtle">{{ currentScreen === 'game' ? 'Spēle' : currentScreen === 'end' ? 'Spēles beigas' : currentScreen === 'settings' ? 'Iestatījumi' : currentScreen === 'login' ? 'Pieslēgties' : 'Administrators' }}</span>
      </header>

      <main :class="['screen-body', { 'screen-body-flush': ['login', 'settings', 'inventory'].includes(currentScreen) }]">
        <section v-if="currentScreen === 'menu'" class="menu-view">
          <div class="menu-panel">
            <div class="menu-mode">M</div>
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
                <p v-if="currentUser" class="account-status">Pieslēdzies: {{ currentUser.username }}</p>
                <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
              </div>

              <div class="menu-right">
                <p class="menu-copy">
                  LOREM IPSUM IS SIMPLY DUMMY TEXT OF THE<br />
                  PRINTING AND TYPESETTING INDUSTRY.
                </p>
                <p class="menu-copy accent">
                  LOREM IPSUM HAS BEEN THE INDUSTRY'S<br />
                  STANDARD DUMMY TEXT EVER SINCE 1966,<br />
                  WHEN DESIGNERS AT LETRASET AND JAMES<br />
                  MOSLEY, THE LIBRARIAN AT ST BRIDE<br />
                  PRINTING LIBRARY IN LONDON
                </p>
              </div>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'login'" class="panel-view login-view">
          <div class="login-card">
            <button class="auth-return" type="button" @click="goBack">RETURN TO MENU</button>
            <form class="login-form" @submit.prevent="submitAuth">
              <input v-model="authForm.username" autocomplete="username" name="username" placeholder="USERNAME OR EMAIL" required />
              <input
                v-if="authMode === 'signup'"
                v-model="authForm.email"
                autocomplete="email"
                name="email"
                placeholder="EMAIL"
                required
                type="email"
              />
              <input
                v-model="authForm.password"
                autocomplete="new-password"
                name="password"
                placeholder="PASSWORD"
                required
                type="password"
              />
              <input
                v-if="authMode === 'signup'"
                v-model="authForm.password_confirmation"
                autocomplete="new-password"
                name="password_confirmation"
                placeholder="CONFIRM PASSWORD"
                required
                type="password"
              />
              <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
              <button class="auth-submit" type="submit" :disabled="busy">
                {{ busy ? 'PLEASE WAIT…' : authMode === 'signup' ? 'CREATE ACCOUNT' : 'LOG IN' }}
              </button>
            </form>
            <div class="login-actions">
              <button
                :class="{ selected: authMode === 'signup' }"
                type="button"
                @click="authMode = 'signup'"
              >
                SIGN UP
              </button>
              <button
                :class="{ selected: authMode === 'login' }"
                type="button"
                @click="authMode = 'login'"
              >
                LOG IN
              </button>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'settings'" class="panel-view settings-view">
          <div class="settings-actions">
            <button class="settings-button" @click="toggleLanguage">
              LANGUAGE <span class="language-code">{{ selectedLanguage }}</span>
            </button>
            <button class="settings-button" @click="goBack">RETURN</button>
            <button v-if="currentUser" class="settings-button logout" :disabled="busy" @click="logoutUser">LOG OUT</button>
            <button v-else class="settings-button logout" @click="openScreen('login')">LOG IN</button>
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
              <span class="continue-text">{{ session.scenario_description }}</span>
              <span class="continue-time">{{ session.playtime }}</span>
            </button>

            <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
            <p v-else-if="!savedSessionsFromApi.length" class="continue-empty">NAV PIEEJAMU SAGLABĀTU SPĒLI.</p>
            <button class="return-button" @click="goBack">RETURN</button>
          </div>
        </section>

        <section v-else-if="currentScreen === 'admin'" class="panel-view admin-view">
          <div class="panel-card admin-card">
            <div class="admin-heading">
              <div>
                <p class="eyebrow">Administratora panelis</p>
                <h2>Spēles saturs</h2>
              </div>
              <button type="button" @click="goBack">RETURN</button>
            </div>
            <p v-if="apiError" class="api-error" role="alert">{{ apiError }}</p>
            <div class="admin-layout">
              <section class="admin-list">
                <div class="admin-heading">
                  <h3>Scenāriji</h3>
                  <button type="button" @click="newScenarioDraft">JAUNS</button>
                </div>
                <article v-for="scenario in adminScenarios" :key="scenario.id" class="admin-entry">
                  <div>
                    <strong>{{ scenario.title }}</strong>
                    <p>{{ scenario.description }}</p>
                  </div>
                  <div class="admin-entry-actions">
                    <button type="button" @click="editScenario(scenario)">REDIĢĒT</button>
                    <button type="button" @click="deleteScenario(scenario)">DZĒST</button>
                  </div>
                </article>
              </section>

              <form v-if="scenarioDraft" class="admin-editor" @submit.prevent="saveScenario">
                <h3>{{ editingScenarioId ? 'Rediģēt scenāriju' : 'Jauns scenārijs' }}</h3>
                <label>Nosaukums<input v-model="scenarioDraft.title" required maxlength="255" /></label>
                <label>Apraksts<textarea v-model="scenarioDraft.description" required rows="4" /></label>
                <div class="admin-inline-fields">
                  <label>Riska zona
                    <select v-model="scenarioDraft.risk_zone">
                      <option value="safe">Droša</option>
                      <option value="caution">Uzmanību</option>
                      <option value="danger">Bīstama</option>
                    </select>
                  </label>
                  <label>Kārtība<input v-model.number="scenarioDraft.sort_order" type="number" min="0" required /></label>
                </div>
                <fieldset v-for="(choice, index) in scenarioDraft.choices" :key="index" class="choice-editor">
                  <legend>Izvēle {{ index + 1 }}</legend>
                  <label>Teksts<input v-model="choice.label" required /></label>
                  <label>Sekas<textarea v-model="choice.aftermath" required rows="2" /></label>
                  <div class="admin-inline-fields">
                    <label>Nākamais scenārijs
                      <select v-model="choice.next_scenario_id">
                        <option value="">Nav</option>
                        <option v-for="option in adminScenarios" :key="option.id" :value="option.id">
                          {{ option.title }}
                        </option>
                      </select>
                    </label>
                    <label>Beigu veids
                      <select v-model="choice.ending_kind">
                        <option value="">Nav</option>
                        <option value="good">Labas beigas</option>
                        <option value="dead">Sliktas beigas</option>
                      </select>
                    </label>
                  </div>
                  <div class="admin-inline-fields">
                    <label>Nepieciešamais priekšmets
                      <select v-model="choice.required_item_id">
                        <option value="">Nav</option>
                        <option v-for="item in adminItems" :key="item.id" :value="item.id">{{ item.name }}</option>
                      </select>
                    </label>
                    <label>Atlīdzības priekšmets
                      <select v-model="choice.reward_item_id">
                        <option value="">Nav</option>
                        <option v-for="item in adminItems" :key="item.id" :value="item.id">{{ item.name }}</option>
                      </select>
                    </label>
                  </div>
                  <label>Atlīdzības iespēja (0–1)
                    <input v-model="choice.reward_chance" type="number" min="0" max="1" step="0.01" />
                  </label>
                  <label>Beigu virsraksts<input v-model="choice.ending_title" /></label>
                  <label>Beigu teksts<textarea v-model="choice.ending_text" rows="2" /></label>
                </fieldset>
                <button type="submit" :disabled="busy">{{ busy ? 'SAGLABĀ…' : 'SAGLABĀ SCENĀRIJU' }}</button>
              </form>

              <section class="admin-list item-admin">
                <h3>Priekšmeti</h3>
                <article v-for="item in adminItems" :key="item.id" class="admin-entry">
                  <div><strong>{{ item.name }}</strong><p>{{ item.description }}</p></div>
                  <div class="admin-entry-actions">
                    <button type="button" @click="editItem(item)">REDIĢĒT</button>
                    <button type="button" @click="deleteItem(item)">DZĒST</button>
                  </div>
                </article>
                <form class="item-editor" @submit.prevent="saveItem">
                  <h3>{{ editingItemId ? 'Rediģēt priekšmetu' : 'Jauns priekšmets' }}</h3>
                  <label>Nosaukums<input v-model="itemDraft.name" required maxlength="255" /></label>
                  <label>Apraksts<textarea v-model="itemDraft.description" required rows="3" /></label>
                  <div class="admin-entry-actions">
                    <button type="submit" :disabled="busy">{{ busy ? 'SAGLABĀ…' : 'SAGLABĀ PRIEKŠMETU' }}</button>
                    <button v-if="editingItemId" type="button" @click="editingItemId = null; itemDraft = { name: '', description: '' }">ATCELT</button>
                  </div>
                </form>
              </section>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'game'" :class="['game-view', `zone-${currentScene.risk_zone}`]">
          <div class="story-card">
            <p class="scene-indicator">{{ currentScene.title }}</p>
            <p class="story-text">{{ currentScene.description }}</p>

            <div class="choice-list">
              <button
                v-for="(choice, index) in currentScene.choices"
                :key="`${choice.label}-${index}`"
                :class="['choice-button', { locked: choice.required_item && !inventory.some((item) => item.id === choice.required_item.id) }]"
                :disabled="choice.required_item && !inventory.some((item) => item.id === choice.required_item.id)"
                :title="choice.required_item && !inventory.some((item) => item.id === choice.required_item.id) ? `Nepieciešams: ${choice.required_item.name}` : ''"
                @click="chooseStoryOption(choice)"
              >
                {{ choice.label }}
                <span v-if="choice.required_item && !inventory.some((item) => item.id === choice.required_item.id)" class="choice-requirement">
                  Nepieciešams: {{ choice.required_item.name }}
                </span>
              </button>
            </div>

            <p v-if="saveError" class="api-error" role="alert">
              {{ saveError }}
              <button type="button" @click="saveCurrentSession">MĒĢINĀT VĒLREIZ</button>
            </p>
            <div class="game-controls">
              <button class="game-control-button" @click="toggleInventory">INVENTORY</button>
              <button class="game-control-button" @click="quitGame">QUIT</button>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'inventory'" class="inventory-view">
          <div class="inventory-screen">
            <div class="inventory-header">
              <h2 class="inventory-title">INVENTORY</h2>
              <button class="inventory-return" @click="toggleInventory">BACK TO GAME</button>
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
                  {{ item ? 'ITEM' : '' }}
                </button>
              </div>
              <div class="inventory-details">
                <div class="inventory-item-preview">{{ selectedInventoryItem?.name ?? 'ITEM' }}</div>
                <p class="inventory-description">{{ selectedItemDescription }}</p>
              </div>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'aftermath'" class="aftermath-view">
          <div class="aftermath-panel" role="dialog" aria-modal="true" aria-label="Choice aftermath">
            <p>{{ aftermathText }}</p>
            <p v-if="saveError" class="api-error" role="alert">{{ saveError }}</p>
            <button v-if="saveError" class="aftermath-next" @click="saveCurrentSession">RETRY SAVE</button>
            <button v-else class="aftermath-next" @click="continueAftermath">NEXT</button>
          </div>
        </section>

        <section v-else-if="currentScreen === 'end'" class="panel-view end-view">
          <div :class="['end-screen', ending?.kind === 'dead' ? 'ending-dead' : 'ending-good']">
            <h2 class="end-title">{{ ending?.title ?? 'GAME END' }}</h2>
            <p v-if="ending" class="ending-text">{{ ending.text }}</p>
            <div class="end-items" aria-label="Items collected during the story">
              <p v-if="inventory.length === 0" class="empty-inventory">NO ITEMS COLLECTED</p>
              <p v-for="item in inventory" :key="item.id" class="end-item">{{ item.name }}</p>
            </div>
            <div class="playtime-section">
              <h3>PLAYTIME</h3>
              <div class="playtime-row">
                <span>TOTAL PLAYED:</span>
                <time>{{ formattedPlaytime }}</time>
              </div>
              </div>
              <button class="end-button" @click="goBack">END</button>
          </div>
        </section>
      </main>
    </div>

  </div>
</template>
