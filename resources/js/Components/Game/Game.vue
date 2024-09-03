<script setup>
import Tile from './Tile.vue'
import {onMounted, ref, watch} from "vue";

window.Echo.listen('game', 'move', () => {

})

const arenaSize = 50;

const player = ref({x: 0, y: 0});

const board = new Array(arenaSize).fill(new Array(arenaSize).fill({}, 0, arenaSize), 0, arenaSize);

const handleKeyPress = (e) => {
  switch (e.key) {
    case 's':
      player.value.x = player.value.x + 1;
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyPress);
});

</script>

<template>
  <div id="board" :style="{ 'grid-template-columns': '1fr '.repeat(arenaSize), 'grid-template-rows': '1fr '.repeat(arenaSize) }">
    <template v-for="(row, rowIdx) in board">
      <Tile v-for="(tile, tileIdx) in row" :player="player" :x="rowIdx" :y="tileIdx"/>
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
