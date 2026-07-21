<script setup>
import { onMounted, ref, useAttrs } from 'vue';

const model = defineModel({
    type: String,
    required: false,
    default: '',
});

const props = defineProps({
    id: { type: String, default: null },
    autocomplete: { type: String, default: 'off' },
});

const attrs = useAttrs();
const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        v-bind="attrs"
        :id="props.id ?? attrs.id ?? 'text-input'"
        :autocomplete="props.autocomplete ?? attrs.autocomplete ?? 'off'"
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        v-model="model"
        ref="input"
    />
</template>
