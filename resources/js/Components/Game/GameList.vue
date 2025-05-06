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

const emit = defineEmits(['joined-game']);
const props = defineProps({
  gameList: Array,
  activeGame: Object,
});

async function joinGame(id) {
  try {
    const response = await axios.post(route('game.join', id));

    if (response.data?.id) {
      emit('joined-game', response.data);
    }
  } catch (e) {
    alert(`Couldn't join the game: ${e.response.data}`);
  }
}
</script>
