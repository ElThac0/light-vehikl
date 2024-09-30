<script setup>

import {computed, reactive} from "vue";

const props = defineProps({
  players: {x: Number, y: Number},
  x: Number,
  y: Number,
});

const isPlayerHere = computed(() => {
  return props.players.some(player => props.x === player.x && props.y === player.y);
});

</script>

<template>
  <div
    :class="{
      tile: true,
      player1: isPlayerHere,
    }"
  ></div>
</template>

<style>
.tile {
  aspect-ratio: 1;
  border: 1px solid navy;
}

.player1 {
  background-color: lime;
}
</style>
