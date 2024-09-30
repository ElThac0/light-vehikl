<script setup>
import Tile from './Tile.vue'
import {onMounted, ref, computed, watch} from "vue";

window.Echo.channel('GameChannel')
    .listen('.player.joined', (event) => {
      console.log('player joined', event);
      players.value = event.players;
    });

const props = defineProps({
  sessionId: String,
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
      player.value.x = player.value.x + 1;
      // window.Echo.channel('GameChannel').('PlayerJoined', { hello: 'world'});
  }
}

const createGame = async () => {
  const response = await axios.post(route('game.create'));

  if (response.data?.id) {
    activeGame.value = response.data;
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

const playerInGame = computed(() => {
  return players.value.map(player => player.playerId).includes(props.sessionId)
});

onMounted(async () => {
  window.addEventListener('keydown', handleKeyPress);

  // if the player is in a game, join it
  const game = await getActiveGame();

  if (game?.id) {
    activeGame.value = game;
  }
});

</script>

<template>
  <button @click="createGame" v-if="!activeGame" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create Game</button>
  <div v-if="activeGame" id="board" :style="{ 'grid-template-columns': '1fr '.repeat(arenaSize), 'grid-template-rows': '1fr '.repeat(arenaSize) }">
    <Tile v-for="tile in board" :players="players" :x="tile.x" :y="tile.y" />
  </div>
</template>

<style>
#board {
  max-width: 75vh;
  margin: auto;
  display: grid;
}
</style>
