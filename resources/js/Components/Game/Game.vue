<script setup>
import Tile from './Tile.vue'
import {onMounted, ref, computed} from "vue";
import GameList from "@/Components/Game/GameList.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Players from "@/Components/Game/Players.vue";

const props = defineProps({
  sessionId: String,
  gameList: Array,
});

const activeGame = ref(null);
const players = computed(() => activeGame.value?.players);
const board = computed(() => activeGame.value?.tiles);
const arenaSize = computed(() => activeGame.value?.arenaSize);
const gameStatus = computed(() => activeGame.value?.status);

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

  if (direction && activeGame.value?.id) {
    axios.post(route('game.move', { id: activeGame.value.id }), { direction });
  }
}

const createGame = async () => {
  const response = await axios.post(route('game.create'));

  if (response.data?.id) {
    setActiveGame(response.data);
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

  setActiveGame(null);
}

const startGame = async () => {
  try {
    await axios.post(route('game.start', {id: activeGame.value?.id}));
  } catch (e) {
    alert(`Error starting game - ${e.response?.data}`)
  }
}

const markReady = async () => {
  try {
    await axios.post(route('game.mark-ready', {id: activeGame.value.id}));
  } catch (e) {
    console.log('oops', e)
    alert(`Marking ready failed - ${e.response?.data}`);
  }
};

const setActiveGame = (gameState) => {
  activeGame.value = gameState;

  window.Echo.channel('GameChannel-' + gameState?.id)
      .listen('.game.updated', (event) => {
        console.log('game updated', event);
        activeGame.value = event;
      });
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
  <GameList :gameList="gameList" :activeGame="activeGame" @joined-game="setActiveGame" class="py-1" />
  <div class="flex gap-1">
    <PrimaryButton @click="createGame" v-if="!activeGame" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create Game</PrimaryButton>
    <PrimaryButton @click="addBot" v-if="activeGame">Add Bot</PrimaryButton>
    <PrimaryButton @click="leaveGame" v-if="activeGame">Leave Game</PrimaryButton>
    <PrimaryButton @click="startGame" v-if="activeGame && activeGame.status === 'waiting'">Start Game</PrimaryButton>
    <PrimaryButton @click="markReady" v-if="activeGame && activeGame.status === 'waiting'">Mark Ready</PrimaryButton>
  </div>

  <div class="flex gap-8">
    <div class="w-1/3">
      <Players :players="players" />
    </div>
    <div v-if="activeGame" class="w-2/3">
      <h2>In Game: {{ activeGame?.id }}</h2>
      <div id="board" :style="{ 'grid-template-columns': '1fr '.repeat(arenaSize), 'grid-template-rows': '1fr '.repeat(arenaSize) }">
        <Tile v-for="(tile, idx) in board" :contents="tile" :players="players" :key="idx" />
      </div>
    </div>
  </div>

</template>

<style>
#board {
  max-width: 75vh;
  margin: auto;
  display: grid;
}
</style>
