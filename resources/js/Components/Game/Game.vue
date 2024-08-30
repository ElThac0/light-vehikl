<script setup>
import Tile from './Tile.vue'
import {onMounted, ref} from "vue";

const arenaSize = 25;

const board = new Array(arenaSize).fill(new Array(arenaSize).fill({}, 0, arenaSize), 0, arenaSize);

</script>

<template>
  <div id="board" :style="{ 'grid-template-columns': '1fr '.repeat(arenaSize), 'grid-template-rows': '1fr '.repeat(arenaSize) }">
    <template v-for="row in board">
      <Tile v-for="tile in row" />
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
