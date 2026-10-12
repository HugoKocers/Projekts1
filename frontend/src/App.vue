<script setup>
import { computed, onUnmounted, ref } from 'vue'

const currentScreen = ref('menu')
const currentSceneIndex = ref(0)
const aftermathText = ref('')
const inventory = ref([])
const selectedInventoryItem = ref(null)
const authMode = ref('signup')
const selectedLanguage = ref('EN')
const elapsedSeconds = ref(0)
const gameStartedAt = ref(null)
const pendingEnding = ref(null)
const pendingNextScene = ref(null)
const ending = ref(null)
let gameTimer = null

const menuButtons = [
  { id: 'login', label: 'SIGN UP/LOG IN' },
  { id: 'game', label: 'START' },
  { id: 'continue', label: 'CONTINUE' },
  { id: 'settings', label: 'EXIT', accent: 'danger' },
]

const itemDescriptions = {
  'Ticība pasakām': 'Stūrgalvīga ticība, ka siena varētu atvērties, ja tajā ieskriesies. Varbūt ne pārāk noderīga, bet noteikti neaizmirstama.',
  'Metāla gabals': 'Smags metāla gabals no vecajiem tuneļiem. Ar to varētu paplašināt šauru izeju.',
  'Auduma strēmele': 'No veca pārklāja noplēsta auduma strēmele. Tā varētu noderēt brūces apsiešanai.',
  'Mājas atslēga': 'Atslēga, kuru domāji pazaudējis. Tā ļauj tikt mājās, nelienot pa logu.',
  'Veiksmes monēta': 'Putekļos atrasta monēta. Tā izskatās pēc laba veiksmes talismana.',
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
  { title: 'Aktīvie spēlētāji', value: '184' },
  { title: 'Pabeigtas misijas', value: '53' },
  { title: 'Gadījumi', value: '12' },
  { title: 'Sistēmas statuss', value: 'Normāli' },
]

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

const currentScene = computed(() => storyScenes[currentSceneIndex.value])
const inventorySlots = computed(() =>
  Array.from({ length: Math.max(6, inventory.value.length) }, (_, index) => inventory.value[index] ?? null),
)
const selectedItemDescription = computed(() =>
  selectedInventoryItem.value ? itemDescriptions[selectedInventoryItem.value] ?? 'A found item from your journey.' : 'Select an item to view its description.',
)
const formattedPlaytime = computed(() => {
  const hours = Math.min(Math.floor(elapsedSeconds.value / 3600), 99)
  const minutes = Math.floor((elapsedSeconds.value % 3600) / 60)
  const seconds = elapsedSeconds.value % 60
  return [hours, minutes, seconds].map((part) => String(part).padStart(2, '0')).join(':')
})

function stopGameTimer() {
  if (gameTimer !== null) {
    clearInterval(gameTimer)
    gameTimer = null
  }
}

function startGameTimer() {
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

function openScreen(screen) {
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

function goBack() {
  stopGameTimer()
  gameStartedAt.value = null
  currentScreen.value = 'menu'
  currentSceneIndex.value = 0
  inventory.value = []
  pendingEnding.value = null
  pendingNextScene.value = null
  ending.value = null
}

function toggleInventory() {
  currentScreen.value = currentScreen.value === 'inventory' ? 'game' : 'inventory'
  selectedInventoryItem.value = inventory.value[0] ?? null
}

function toggleLanguage() {
  selectedLanguage.value = selectedLanguage.value === 'EN' ? 'LV' : 'EN'
}

function chooseStoryOption(option) {
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

function continueAftermath() {
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
            <form class="login-form" @submit.prevent>
              <input autocomplete="username" name="username" placeholder="USERNAME" required />
              <input
                autocomplete="new-password"
                name="password"
                placeholder="PASSWORD"
                required
                type="password"
              />
              <input
                v-if="authMode === 'signup'"
                autocomplete="new-password"
                name="confirm-password"
                placeholder="CONFIRM PASSWORD"
                required
                type="password"
              />
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
            <button class="settings-button logout" @click="goBack">LOG OUT</button>
          </div>
        </section>

        <section v-else-if="currentScreen === 'continue'" class="panel-view continue-view">
          <div class="continue-panel">
            <div v-for="(session, index) in savedSessions" :key="index" class="continue-row">
              <span class="continue-text">{{ session.description }}</span>
              <span class="continue-time">{{ session.playtime }}</span>
            </div>

            <button class="return-button" @click="goBack">RETURN</button>
          </div>
        </section>

        <section v-else-if="currentScreen === 'admin'" class="panel-view admin-view">
          <div class="panel-card admin-card">
            <p class="eyebrow">Administratora panelis</p>
            <h2>Administratīvā statistika</h2>
            <div class="admin-grid">
              <article v-for="card in adminCards" :key="card.title" class="admin-stat">
                <span>{{ card.title }}</span>
                <strong>{{ card.value }}</strong>
              </article>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'game'" :class="['game-view', `zone-${currentScene.risk}`]">
          <div class="story-card">
            <p class="scene-indicator">{{ currentScene.title }}</p>
            <p class="story-text">{{ currentScene.text }}</p>

            <div class="choice-list">
              <button
                v-for="(choice, index) in currentScene.choices"
                :key="`${choice.label}-${index}`"
                :class="['choice-button', { locked: choice.requiredItem && !inventory.includes(choice.requiredItem) }]"
                :disabled="choice.requiredItem && !inventory.includes(choice.requiredItem)"
                :title="choice.requiredItem && !inventory.includes(choice.requiredItem) ? `Nepieciešams: ${choice.requiredItem}` : ''"
                @click="chooseStoryOption(choice)"
              >
                {{ choice.label }}
                <span v-if="choice.requiredItem && !inventory.includes(choice.requiredItem)" class="choice-requirement">
                  Nepieciešams: {{ choice.requiredItem }}
                </span>
              </button>
            </div>

            <div class="game-controls">
              <button class="game-control-button" @click="toggleInventory">INVENTORY</button>
              <button class="game-control-button" @click="goBack">QUIT</button>
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
                  :aria-label="item ?? 'Empty inventory slot'"
                  @click="selectedInventoryItem = item"
                >
                  {{ item ? 'ITEM' : '' }}
                </button>
              </div>
              <div class="inventory-details">
                <div class="inventory-item-preview">{{ selectedInventoryItem ?? 'ITEM' }}</div>
                <p class="inventory-description">{{ selectedItemDescription }}</p>
              </div>
            </div>
          </div>
        </section>

        <section v-else-if="currentScreen === 'aftermath'" class="aftermath-view">
          <div class="aftermath-panel" role="dialog" aria-modal="true" aria-label="Choice aftermath">
            <p>{{ aftermathText }}</p>
            <button class="aftermath-next" @click="continueAftermath">NEXT</button>
          </div>
        </section>

        <section v-else-if="currentScreen === 'end'" class="panel-view end-view">
          <div :class="['end-screen', ending?.kind === 'dead' ? 'ending-dead' : 'ending-good']">
            <h2 class="end-title">{{ ending?.title ?? 'GAME END' }}</h2>
            <p v-if="ending" class="ending-text">{{ ending.text }}</p>
            <div class="end-items" aria-label="Items collected during the story">
              <p v-if="inventory.length === 0" class="empty-inventory">NO ITEMS COLLECTED</p>
              <p v-for="item in inventory" :key="item" class="end-item">{{ item }}</p>
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
