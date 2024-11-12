<script setup>
import Tile from './Tile.vue'
import {onMounted, ref, computed, watch} from "vue";
import GameList from "@/Components/Game/GameList.vue";

const props = defineProps({
  sessionId: String,
  gameList: Array,
});

const activeGame = ref(null);
const players = computed(() => activeGame.value?.players);
const board = computed(() => activeGame.value?.tiles);
const arenaSize = computed(() => activeGame.value?.arenaSize);

const addPlayer = (player) => {
  players.value.push(player);
}

const handleKeyPress = (e) => {
  switch (e.key) {
    case 's':
      console.log(e);
  }
}

const createGame = async () => {
  const response = await axios.post(route('game.create'));

  if (response.data?.id) {
    setActiveGame(response.data);
  }
}

const setActiveGame = (gameState) => {
  activeGame.value = gameState;

  window.Echo.channel('GameChannel')
      .listen('.player.joined', (event) => {
        console.log('player joined', event);
        activeGame.value.players = event.players;
      })
      .listen('.game.updated', (event) => {
        console.log('game updated', event);
        activeGame.value.players = event.players;
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

const playerInGame = computed(() => {
  return players.value.map(player => player.playerId).includes(props.sessionId)
});

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
  <GameList :gameList="gameList" :activeGame="activeGame" @joined-game="setActiveGame"/>
  <button @click="createGame" v-if="!activeGame" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create Game</button>
  <template v-else>
    <h2>In Game: {{ activeGame.id }}</h2>
    <div id="board" :style="{ 'grid-template-columns': '1fr '.repeat(arenaSize), 'grid-template-rows': '1fr '.repeat(arenaSize) }">
      <Tile v-for="tile in board" :players="players" :x="tile.x" :y="tile.y" />
    </div>
  </template>
</template>

<style>
#board {
  max-width: 75vh;
  margin: auto;
  display: grid;
}
</style>
