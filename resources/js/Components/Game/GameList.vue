<template>
  <ol>
    <li v-for="game in gameList">
      {{ game }}
      <button @click="joinGame(game)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Join Game
      </button>
    </li>
  </ol>
</template>

<script setup>
const props = defineProps({
  gameList: Array,
});

async function joinGame(id) {
  try {
    const response = await axios.post(route('game.join', id));

    if (response.data?.id) {
      activeGame.value = response.data;
    }
  } catch (e) {
    alert(`Couldn't join the game: ${e.response.data.message}`);
  }
}
</script>
