<template>
  <ol>
    <li v-for="game in gameList" :key="game.id" class="py-1">
      <PrimaryButton @click="joinGame(game.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Join Game
      </PrimaryButton>
      <PrimaryButton @click="watchGame(game.id)" class="ml-1">
        Watch
      </PrimaryButton>
      <span class="ml-1">{{ game.createdBy ? `${game.createdBy}'s game` : 'Unnamed game' }}</span>
    </li>
  </ol>
</template>

<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { onBeforeMount, onUnmounted, ref } from "vue";
import errorMessage from "@/errorMessage";

const emit = defineEmits(['joined-game', 'watching-game']);

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
      .listen('.game.removed', (event) => {
        gameList.value = event.games
      })
})

onUnmounted(() => {
  window.Echo.channel('GameChannel')
      .stopListening('.game.created')
      .stopListening('.game.ended')
      .stopListening('.game.removed')
})

async function joinGame(id) {
  try {
    const response = await axios.post(route('game.join', id));

    if (response.data?.gameState?.id) {
      emit('joined-game', response.data.gameState);
    }
  } catch (e) {
    alert(`Couldn't join the game: ${errorMessage(e)}`);
  }
}

async function watchGame(id) {
  try {
    const response = await axios.get(route('game.observe', id));

    if (response.data?.gameState?.id) {
      emit('watching-game', response.data.gameState);
    }
  } catch (e) {
    alert(`Couldn't watch the game: ${errorMessage(e)}`);
  }
}
</script>
