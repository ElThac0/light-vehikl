<script setup>
import Tile from './Tile.vue'

defineProps({
  arenaSize: Number,
  board: {},
  players: {},
})
</script>

<template>
  <div id="board"
       :style="{ 'grid-template-columns': '1fr '.repeat(arenaSize), 'grid-template-rows': '1fr '.repeat(arenaSize) }">
    <Tile v-for="(tile, idx) in board" :contents="tile" :players="players" :key="idx"/>
  </div>
</template>

<style>
#board {
  max-width: 75vh;
  margin: auto;
  display: grid;
}
</style>
