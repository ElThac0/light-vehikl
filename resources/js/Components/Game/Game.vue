<script setup>
import Tile from './Tile.vue'
import {onMounted, ref, watch} from "vue";

window.Echo.channel('GameChannel')
    .listen('.player.joined', (event) => {
  console.log('player joined', event);
  addPlayer(event);
});

const props = defineProps({
  sessionId: String,
});

const arenaSize = 50;
const player = ref(null);
const board = new Array(arenaSize).fill(new Array(arenaSize).fill({}, 0, arenaSize), 0, arenaSize);

const addPlayer = (event) => {
  player.value = event.location;
}

const handleKeyPress = (e) => {
  switch (e.key) {
    case 's':
      player.value.x = player.value.x + 1;
      // window.Echo.channel('GameChannel').('PlayerJoined', { hello: 'world'});
  }
}

const handleStart = (e) => {
  e.preventDefault();
  axios.get(route('player.joined', {id: props.sessionId}));
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyPress);
  console.log(props.sessionId)
});

</script>

<template>
  <button @click="handleStart">Start</button>
  <div id="board" :style="{ 'grid-template-columns': '1fr '.repeat(arenaSize), 'grid-template-rows': '1fr '.repeat(arenaSize) }">
    <template v-for="(row, rowIdx) in board">
      <Tile v-for="(tile, tileIdx) in row" :player="player" :x="rowIdx" :y="tileIdx" />
    </template>
  </div>
</template>

<style>
#board {
  max-width: 75vh;
  margin: auto;
  display: grid;
}
</style>
