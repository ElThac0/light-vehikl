<script setup>

import {computed, reactive} from "vue";

const props = defineProps({
  player: {x: Number, y: Number},
  x: Number,
  y: Number,
});

const isPlayerHere = computed(() => {
  return props.x === props.player.x && props.y === props.player.y;
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
