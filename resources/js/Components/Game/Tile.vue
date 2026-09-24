<script setup>

import { computed } from "vue";
// Defines the --player-*-color variables used below.
import "./playerColors.js";

const props = defineProps({
  contents: Number,
  players: Array,
});

const player = computed(() => {
    switch(props.contents)
    {
      case 2:
      case 3:
        return 'player-1';
      case 4:
      case 5:
        return 'player-2';
      case 6:
      case 7:
        return 'player-3';
      case 8:
      case 9:
        return 'player-4';
      default:
        return 'empty';
    }
});

const crashed = computed(() => {
  if ([2, 4, 6, 8].includes(props.contents)) {
    return props.players.find((value) => value.slot === props.contents)?.status === 'crashed';
  }
  return false;
});

</script>

<template>
  <div
    :class="{
      tile: true,
      [player]: true,
      crashed: crashed,
    }"
  ></div>
</template>

<style>
.tile {
  aspect-ratio: 1;
  border: 1px solid navy;
}

.player-1 {
  background-color: var(--player-1-color);
}

.player-2 {
  background-color: var(--player-2-color);
}

.player-3 {
  background-color: var(--player-3-color);
}

.player-4 {
  background-color: var(--player-4-color);
}

.crashed {
  background-color: var(--player-crashed-color);
}
</style>
