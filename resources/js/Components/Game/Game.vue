<script setup>
import { computed, onMounted, ref, watch } from "vue";
import GameList from "@/Components/Game/GameList.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Players from "@/Components/Game/Players.vue";
import GameBoard from "@/Components/Game/GameBoard.vue";
import GameBoardWebGL from "@/Components/Game/GameBoardWebGL.vue";
import GameBoard3D from "@/Components/Game/GameBoard3D.vue";
import PlayerName from "@/Components/Game/PlayerName.vue";
import errorMessage from "@/errorMessage";
import { playerColor } from "@/Components/Game/playerColors.js";

const props = defineProps({
  playerName: String,
});

const name = ref(props.playerName);

// Which board to draw with; remembered per browser. Storage can be
// unavailable (e.g. private browsing), so fall back to the HTML board.
const RENDER_MODE_KEY = 'render-mode';
const RENDER_MODES = ['html', 'webgl', '3d'];
const readRenderMode = () => {
  try {
    const mode = localStorage.getItem(RENDER_MODE_KEY);
    return RENDER_MODES.includes(mode) ? mode : 'html';
  } catch {
    return 'html';
  }
}
const renderMode = ref(readRenderMode());
watch(renderMode, (mode) => {
  try {
    localStorage.setItem(RENDER_MODE_KEY, mode);
  } catch {}
});

const activeGame = ref(null);
// Observers get the game's updates but aren't players in it.
const observing = ref(false);
const players = computed(() => activeGame.value?.players);
const board = computed(() => activeGame.value?.tiles);
const arenaSize = computed(() => activeGame.value?.arenaSize);
const gameStatus = computed(() => activeGame.value?.status);
// Once the game is over, the last player still riding (if any) has won.
const winner = computed(() => gameStatus.value === 'complete'
    ? players.value?.find((player) => player.status !== 'crashed') ?? null
    : undefined);

const addPlayer = (player) => {
  players.value.push(player);
}

const handleKeyPress = (e) => {
  let direction = null;
  switch (e.key) {
    case 'w':
      direction = 'North';
      break;
    case 'a':
      direction = 'West';
      break;
    case 's':
      direction = 'South';
      break;
    case 'd':
      direction = 'East';
      break;
  }

  if (direction && activeGame.value?.id && !observing.value) {
    axios.post(route('game.move', { id: activeGame.value.id }), { direction });
  }
}

const createGame = async () => {
  const response = await axios.post(route('game.create'));

  if (response.data?.id) {
    setActiveGame(response.data);
  }
}

// Create an empty game and watch it, leaving every slot open for others.
const createAndWatchGame = async () => {
  try {
    const response = await axios.post(route('game.create'), { spectate: true });

    if (response.data?.id) {
      watchGame(response.data);
    }
  } catch (e) {
    alert(`Couldn't create the game: ${errorMessage(e)}`);
  }
}

const addBot = async () => {
  const response = await axios.post(route('game.add-bot', { id: activeGame.value?.id }));

  if (response.data?.id) {
    setActiveGame(response.data);
  }
}

const leaveGame = async () => {
  await axios.post(route('game.leave', { id: activeGame.value?.id }));

  window.Echo.leave('GameChannel-' + activeGame.value?.id);
  window.Echo.leave('game.' + activeGame.value?.id);

  setActiveGame(null);
}

const watchGame = (gameState) => {
  observing.value = true;
  setActiveGame(gameState);
}

const stopWatching = () => {
  window.Echo.leave('GameChannel-' + activeGame.value?.id);

  observing.value = false;
  activeGame.value = null;
}

const startGame = async () => {
  try {
    await axios.post(route('game.start', {id: activeGame.value?.id}));
  } catch (e) {
    alert(`Error starting game - ${errorMessage(e)}`)
  }
}

const markReady = async () => {
  try {
    await axios.post(route('game.mark-ready', {id: activeGame.value.id}));
  } catch (e) {
    console.log('oops', e)
    alert(`Marking ready failed - ${errorMessage(e)}`);
  }
};

const setActiveGame = (gameState) => {
  activeGame.value = gameState;

  window.Echo.channel('GameChannel-' + gameState?.id)
      .listen('.game.updated', (event) => {
        console.log('game updated', event);
        activeGame.value = event;
      });

  // Being in this channel is how the server knows we're still connected.
  if (gameState?.id && !observing.value) {
    window.Echo.join('game.' + gameState.id);
  }
}

const getActiveGame = async () => {
  try {
    const response = await axios.get(route('game.my'));
    return response.data;
  } catch (e) {
    console.log(e);
    return null;
  }
}

onMounted(async () => {
  window.addEventListener('keydown', handleKeyPress);

  // if the player is in a game, join it
  const game = await getActiveGame();

  if (game?.id) {
    setActiveGame(game);
  }
});

</script>

<template>
  <PlayerName v-if="!activeGame" :name="name" @saved="name = $event" />
  <GameList v-if="!activeGame && name" @joined-game="setActiveGame" @watching-game="watchGame" class="py-1" />
  <div class="flex gap-1">
    <PrimaryButton @click="createGame" v-if="!activeGame && name" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create Game</PrimaryButton>
    <PrimaryButton @click="createAndWatchGame" v-if="!activeGame && name">Create &amp; Watch</PrimaryButton>
    <template v-if="activeGame && !observing">
      <PrimaryButton @click="addBot" v-if="activeGame.status === 'waiting'">Add Bot</PrimaryButton>
      <PrimaryButton @click="leaveGame">Leave Game</PrimaryButton>
      <PrimaryButton @click="startGame" v-if="activeGame.status === 'waiting'">Start Game</PrimaryButton>
      <PrimaryButton @click="markReady" v-if="activeGame.status === 'waiting'">Mark Ready</PrimaryButton>
    </template>
    <PrimaryButton @click="stopWatching" v-if="activeGame && observing">Stop Watching</PrimaryButton>
  </div>

  <div class="flex gap-8">
    <div class="w-1/3">
      <Players :players="players" />
    </div>
    <div v-if="activeGame" class="w-2/3">
      <div class="flex justify-between items-center gap-2 mb-2">
        <h2>{{ observing ? 'Watching Game' : 'In Game' }}: {{ activeGame?.id }}</h2>
        <label class="flex items-center gap-2 text-sm">
          Render mode
          <select v-model="renderMode" class="border border-gray-300 rounded py-1 pl-2 pr-8 text-sm text-black">
            <option value="html">HTML</option>
            <option value="webgl">Graphical (WebGL)</option>
            <option value="3d">3D (WebGL)</option>
          </select>
        </label>
      </div>
      <div class="relative max-w-[75vh] mx-auto">
        <GameBoard3D v-if="renderMode === '3d'" :arena-size="arenaSize" :board="board" :players="players"/>
        <GameBoardWebGL v-else-if="renderMode === 'webgl'" :arena-size="arenaSize" :board="board" :players="players"/>
        <GameBoard v-else :arena-size="arenaSize" :board="board" :players="players"/>
        <div v-if="gameStatus === 'complete'" class="absolute inset-0 flex items-center justify-center bg-black/60 pointer-events-none">
          <p class="text-4xl font-bold text-white drop-shadow-lg">
            <template v-if="winner">
              <span :style="{ color: playerColor(winner.slot) }">{{ winner.name }}</span> wins!
            </template>
            <template v-else>Everyone crashed!</template>
          </p>
        </div>
      </div>
    </div>
  </div>

</template>

