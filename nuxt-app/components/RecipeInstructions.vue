<template>
    <div class="instructions-section">
        <label>作り方</label>
        <div class="recipe-body">
            <p v-if="typeof instructions === 'string'">{{ instructions }}</p>
            <ol v-else-if="Array.isArray(instructionsList) && instructionsList.length > 0">
                <li
                    v-for="(step, index) in instructionsList"
                    :key="index"
                    class="instruction-step"
                >
                    {{ step }}
                </li>
            </ol>
        </div>
    </div>
</template>

<script setup>
// import { computed } from 'vue'

const props = defineProps({
    instructions: {
        type: [String, Array],
        default: ''
    }
})

const instructionsList = computed(() => {
    if (Array.isArray(props.instructions)) {
        return props.instructions
    }

    if (typeof props.instructions === 'string') {
        return parseInstructionsString(props.instructions)
    }

    return []
})

const parseInstructionsString = (instructionsStr) => {
    if (!instructionsStr) return []

    return instructionsStr.split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0)
}
</script>

<style scoped>
.instructions-section label {
    display: block;
    font-weight: bold;
    margin-top: 25px;
    margin-bottom: 10px;
}

.recipe-body {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
    white-space: pre-wrap;
    line-height: 1.6;
}

.instruction-step {
    margin-bottom: 8px;
    line-height: 1.5;
}
</style>