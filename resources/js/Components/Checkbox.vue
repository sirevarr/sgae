<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        type: [String, Number, Boolean, Array, Object],
        default: null,
    },
    label: {
        type: String,
        default: 'Opción',
    },
    id: {
        type: String,
        default: 'checkbox',
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <div class="inline-flex items-center gap-2">
        <input
            :id="id"
            type="checkbox"
            :value="value"
            v-model="proxyChecked"
            :aria-label="label"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        />
        <span class="text-sm text-gray-600">{{ label }}</span>
    </div>
</template>
