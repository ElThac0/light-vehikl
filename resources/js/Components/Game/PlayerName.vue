<script setup>
import { ref } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
  name: String,
});

const emit = defineEmits(['saved']);

const draft = ref(props.name ?? '');
const editing = ref(!props.name);
const error = ref(null);
const saving = ref(false);

const save = async () => {
  saving.value = true;
  error.value = null;

  try {
    const response = await axios.post(route('player.name'), { name: draft.value });
    draft.value = response.data.name;
    editing.value = false;
    emit('saved', response.data.name);
  } catch (e) {
    error.value = e.response?.data?.errors?.name?.[0] ?? "Couldn't save your name.";
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <form v-if="editing" @submit.prevent="save" class="py-2">
    <label for="player-name" class="block font-bold">Your name</label>
    <div class="flex gap-1">
      <input id="player-name" v-model="draft" maxlength="20" autocomplete="nickname"
             class="border border-gray-300 rounded px-2 py-1 text-black" />
      <PrimaryButton type="submit" :disabled="saving">Save</PrimaryButton>
    </div>
    <p v-if="error" class="text-sm text-red-600 mt-1">{{ error }}</p>
  </form>
  <div v-else class="py-2">
    Playing as <span class="font-bold">{{ name }}</span>
    <button type="button" @click="editing = true" class="ml-2 text-sm underline">Change</button>
  </div>
</template>
