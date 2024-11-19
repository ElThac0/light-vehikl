<script setup>

import { computed } from "vue";

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
  background-color: lime;
}

.player-2 {
  background-color: #00eaff;
}

.player-3 {
  background-color: #2563eb;
}

.player-4 {
  background-color: orange;
}

.crashed {
  background-color: red;
}
</style>
