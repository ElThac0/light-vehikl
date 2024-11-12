<script setup>

import { computed } from "vue";

const props = defineProps({
  contents: Number,
});

const occupied = computed(() => props.contents !== 0);

</script>

<template>
  <div
    :class="{
      tile: true,
      occupied,
    }"
  ></div>
</template>

<style>
.tile {
  aspect-ratio: 1;
  border: 1px solid navy;
}

.occupied {
  background-color: lime;
}
</style>
