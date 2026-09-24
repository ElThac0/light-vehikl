<script setup>

const props = defineProps({
    players: {
      type: Array
    }
})
</script>

<template>
  <div class="font-bold text-lg">Players</div>
  <ul>
    <li v-for="player in players" :key="`player-${player.id}`" class="py-2 border-b border-gray-200">
      {{ player.name }}
      <span v-if="player.isBot" class="ml-1 px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 text-xs font-bold uppercase">
        Bot
      </span>

      <div class="font-bold uppercase text-sm">
        <div v-if="player.status === 'waiting'" class="text-gray-500">
          Waiting
        </div>
        <div v-if="player.status === 'ready'" class="text-blue-500">
          Ready
        </div>
        <div v-if="player.status === 'active'" class="text-green-500">
          Active
        </div>
        <div v-if="player.status === 'crashed'" class="text-red-500">
          Crashed
        </div>
      </div>
    </li>
  </ul>
</template>

<style scoped>

</style>
