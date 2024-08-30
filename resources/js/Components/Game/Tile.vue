<script setup>

defineProps({
  x: Number,
  y: Number,
});

</script>

<template>
  <div class="tile"></div>
</template>

<style>
.tile {
  aspect-ratio: 1;
  border: 1px solid navy;
}
</style>
