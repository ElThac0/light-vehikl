<template>
  <ol>
    <li v-for="game in gameList" class="py-1">
      <PrimaryButton @click="joinGame(game)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Join Game
      </PrimaryButton>
      {{ game }}
    </li>
  </ol>
</template>

<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { onBeforeMount, onUnmounted, ref } from "vue";

const emit = defineEmits(['joined-game']);

const gameList = ref([])

onBeforeMount(async () => {
  const response = await axios.get(route('game.list'));

  gameList.value = response.data

  window.Echo.channel('GameChannel')
      .listen('.game.created', (event) => {
        gameList.value = event.games
      })
      .listen('.game.ended', (event) => {
        gameList.value = event.games
      })
})

onUnmounted(() => {
  window.Echo.channel('GameChannel')
      .stopListening('.game.created')
      .stopListening('.game.ended')
})

async function joinGame(id) {
  try {
    const response = await axios.post(route('game.join', id));

    if (response.data?.gameState?.id) {
      emit('joined-game', response.data.gameState);
    }
  } catch (e) {
    alert(`Couldn't join the game: ${e.response.data}`);
  }
}
</script>
