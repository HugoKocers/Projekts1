<script setup>
import { computed, onUnmounted, ref } from 'vue'

const currentScreen = ref('menu')
const inventoryOpen = ref(false)
const currentSceneIndex = ref(0)
const aftermathText = ref('')
const inventory = ref([])
const elapsedSeconds = ref(0)
const gameStartedAt = ref(null)
let gameTimer = null

const menuButtons = [
  { id: 'login', label: 'SIGN UP/LOG IN' },
  { id: 'game', label: 'START' },
  { id: 'continue', label: 'CONTINUE' },
  { id: 'settings', label: 'EXIT', accent: 'danger' },
]

const settingsActions = [
  { label: 'Valodas maiņa', type: 'primary' },
  { label: 'Atgriezties', type: 'secondary' },
  { label: 'Iziet', type: 'danger' },
]

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
    text: 'The village square is empty. A lantern flickers above the old well, and a note on its stone rim reads: “The bell will ring when the archive is found.”',
    choices: [
      {
        label: 'Take the note and head for the bell tower.',
        aftermath: 'You fold the note and follow the sound of a distant bell. The tower rises above the rooftops, its door slightly ajar.',
        reward: { name: 'Lantern Oil', chance: 0.35 },
      },
      {
        label: 'Search the well for another clue.',
        aftermath: 'At the bottom of the well, your lantern catches a metal glint. You retrieve it before heading toward the tower.',
        reward: { name: 'Brass Key', chance: 0.65 },
      },
      {
        label: 'Ask the innkeeper about the archive.',
        aftermath: 'The innkeeper remembers a locked room beneath the tower. They warn you that the old stairway is unstable.',
        reward: { name: 'Cloth Strip', chance: 0.3 },
      },
    ],
  },
  {
    text: 'Inside the tower, three stairways lead upward. Dust covers the steps, except for a fresh trail that disappears behind a heavy wooden door.',
    choices: [
      {
        label: 'Follow the fresh footprints.',
        aftermath: 'The footprints lead to a narrow landing. You find a torn page from the archive and hear movement above you.',
        reward: { name: 'Archive Page', chance: 0.5 },
      },
      {
        label: 'Try the brass key on the wooden door.',
        aftermath: 'The key turns with a click. Beyond the door, a spiral staircase descends beneath the tower.',
        requiredItem: 'Brass Key',
      },
      {
        label: 'Climb the stairs toward the bell.',
        aftermath: 'At the top, you discover the bell rope has been cut. A small service hatch opens onto a hidden lower level.',
        reward: { name: 'Bell Clapper', chance: 0.4 },
      },
    ],
  },
  {
    text: 'The hidden passage ends at a flooded archive chamber. Shelves lean over the water, and a sealed book rests on a stone table.',
    choices: [
      {
        label: 'Wade through the water to reach the book.',
        aftermath: 'The cold water reaches your waist, but you retrieve the sealed book. Its cover bears the same mark as the note.',
        reward: { name: 'Sealed Book', chance: 0.55 },
      },
      {
        label: 'Use a loose shelf to cross above the water.',
        aftermath: 'The shelf holds your weight. You reach the table without disturbing the water and notice a hidden latch beneath it.',
        reward: { name: 'Archive Seal', chance: 0.45 },
      },
      {
        label: 'Pull the drainage lever by the entrance.',
        aftermath: 'The water slowly drains away, revealing a second passage and a scattered set of archive pages.',
        reward: { name: 'Archive Page', chance: 0.55 },
      },
    ],
  },
  {
    text: 'The archive pages describe why the bell was silenced: its sound would reveal the entrance to a chamber beneath the village.',
    choices: [
      {
        label: 'Repair the bell rope with a strip of cloth.',
        aftermath: 'The rope holds. One clear note rings through the village, and a stone doorway opens beneath the tower.',
        requiredItem: 'Cloth Strip',
      },
      {
        label: 'Use the archive pages to find the bell mechanism.',
        aftermath: 'A diagram shows how to reset the mechanism. The bell rings once, opening a concealed stairway under the tower.',
        requiredItem: 'Archive Page',
      },
      {
        label: 'Leave the bell untouched and open the chamber manually.',
        aftermath: 'You find a hidden release behind the bell frame. The doorway opens, though the village remains quiet.',
        reward: { name: 'Old Coin', chance: 0.3 },
      },
    ],
  },
  {
    text: 'Beneath the village, you find the missing archive intact. Its final entry explains that the bell was meant to protect the records, not hide them forever.',
    choices: [
      {
        label: 'Return the archive to the village library.',
        aftermath: 'The villagers gather as the archive is returned. The records are safe again, and the bell rings to mark the end of your journey.',
        reward: { name: 'Library Token', chance: 0.5 },
      },
      {
        label: 'Read the final entry before returning.',
        aftermath: 'The final entry reveals the names of those who protected the archive. You carry the book back and share their story with the village.',
        requiredItem: 'Sealed Book',
      },
      {
        label: 'Leave the archive hidden for now.',
        aftermath: 'You secure the chamber and mark its entrance. The archive remains protected until the village is ready to care for it.',
      },
    ],
  },
]

const currentScene = computed(() => storyScenes[currentSceneIndex.value])
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
    inventory.value = []
    startGameTimer()
  }
  inventoryOpen.value = false
}

function goBack() {
  stopGameTimer()
  gameStartedAt.value = null
  inventoryOpen.value = false
  currentScreen.value = 'menu'
  currentSceneIndex.value = 0
  inventory.value = []
}

function toggleInventory() {
  inventoryOpen.value = !inventoryOpen.value
}

function handleSettingAction(action) {
  if (action.label === 'Atgriezties') {
    goBack()
    return
  }

  if (action.label === 'Iziet') {
    openScreen('menu')
    return
  }

  openScreen('menu')
}

function chooseStoryOption(option) {
  if (option.requiredItem && !inventory.value.includes(option.requiredItem)) {
    return
  }

  aftermathText.value = option.aftermath
  if (option.reward) {
    if (Math.random() < option.reward.chance) {
      if (!inventory.value.includes(option.reward.name)) {
        inventory.value.push(option.reward.name)
      }
      aftermathText.value += ` You found an item: ${option.reward.name}.`
    } else {
      aftermathText.value += ' You did not find an item this time.'
    }
  }
  currentScreen.value = 'aftermath'
}

function continueAftermath() {
  if (currentSceneIndex.value === storyScenes.length - 1) {
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

  currentSceneIndex.value += 1
  currentScreen.value = 'game'
}

onUnmounted(stopGameTimer)
</script>

<template>
  <div class="app-shell">
    <div class="screen-frame">
      <header v-if="currentScreen !== 'menu' && currentScreen !== 'continue' && currentScreen !== 'aftermath' && currentScreen !== 'end'" class="topbar topbar-compact">
        <button class="ghost-button" @click="goBack">Atgriezties</button>
        <span class="brand subtle">{{ currentScreen === 'game' ? 'Spēle' : currentScreen === 'end' ? 'Spēles beigas' : currentScreen === 'settings' ? 'Iestatījumi' : currentScreen === 'login' ? 'Pieslēgties' : 'Administrators' }}</span>
      </header>

      <main class="screen-body">
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
          <div class="panel-card login-card">
            <p class="eyebrow">Pieslēgties</p>
            <h2>Autentifikācija</h2>
            <div class="login-form">
              <label>
                <span>E-pasts</span>
                <input type="email" value="spēlētājs@ciems.lv" />
              </label>
              <label>
                <span>Parole</span>
                <input type="password" value="••••••••" />
              </label>
            </div>
            <button class="action-button primary" @click="openScreen('game')">Ienākt spēlē</button>
          </div>
        </section>

        <section v-else-if="currentScreen === 'settings'" class="panel-view settings-view">
          <div class="panel-card">
            <p class="eyebrow">Iestatījumi</p>
            <h2>Skicē parādīta lapa "iestatījumi"</h2>
            <p>Šeit plānots pogām būt: valodas nomainīšana, atgriezties un iziet.</p>

            <div class="stacked-actions">
              <button
                v-for="action in settingsActions"
                :key="action.label"
                :class="['action-button', action.type]"
                @click="handleSettingAction(action)"
              >
                {{ action.label }}
              </button>
            </div>
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

        <section v-else-if="currentScreen === 'game'" class="game-view">
          <div class="story-card">
            <p class="story-text">{{ currentScene.text }}</p>

            <div class="choice-list">
              <button
                v-for="(choice, index) in currentScene.choices"
                :key="`${choice.label}-${index}`"
                :class="['choice-button', { locked: choice.requiredItem && !inventory.includes(choice.requiredItem) }]"
                :disabled="choice.requiredItem && !inventory.includes(choice.requiredItem)"
                :title="choice.requiredItem && !inventory.includes(choice.requiredItem) ? `Requires ${choice.requiredItem}` : ''"
                @click="chooseStoryOption(choice)"
              >
                {{ choice.label }}
                <span v-if="choice.requiredItem && !inventory.includes(choice.requiredItem)" class="choice-requirement">
                  Requires {{ choice.requiredItem }}
                </span>
              </button>
            </div>

            <div class="game-controls">
              <button class="game-control-button" @click="toggleInventory">INVENTORY</button>
              <button class="game-control-button" @click="goBack">QUIT</button>
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
          <div class="end-screen">
            <h2 class="end-title">GAME END</h2>
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

    <div v-if="inventoryOpen" class="inventory-overlay" @click.self="toggleInventory">
      <div class="inventory-panel">
        <div class="inventory-header">
          <h3>Inventārs</h3>
          <button class="ghost-button" @click="toggleInventory">Aizvērt</button>
        </div>
        <ul>
          <li v-for="item in inventory" :key="item">{{ item }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>
