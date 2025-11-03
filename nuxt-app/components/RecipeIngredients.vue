<template>
    <div class="ingredients-section">
        <label>材料（{{ servings || '人数未設定' }}）</label>
        <div id="ingredients">
            <div
                v-for="ingredient in ingredientsList"
                :key="ingredient.id"
                class="ingredient-row"
            >
                <div class="ingredient-name">{{ ingredient.name }}</div>
                <div class="ingredient-qty">{{ ingredient.quantity || ingredient.amount }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
// import { computed } from 'vue'

const props = defineProps({
    ingredients: {
        type: [Array, String],
        default: () => []
    },
    servings: {
        type: String,
        default: ''
    }
})

const ingredientsList = computed(() => {
    if (Array.isArray(props.ingredients)) {
        return props.ingredients
    }

    if (typeof props.ingredients === 'string') {
        return parseIngredientsString(props.ingredients)
    }

    return []
})

const parseIngredientsString = (ingredientsString) => {
    if (!ingredientsString || typeof ingredientsString !== 'string') {
        return []
    }

    const lines = ingredientsString.split('\n').filter(line => line.trim())

    return lines.map((line, index) => {
        const trimmedLine = line.trim()
        const lastSpaceIndex = trimmedLine.lastIndexOf(' ')

        if (lastSpaceIndex > 0) {
            const name = trimmedLine.substring(0, lastSpaceIndex).trim()
            const quantity = trimmedLine.substring(lastSpaceIndex + 1).trim()

            return {
                id: index + 1,
                name: name,
                quantity: quantity
            }
        } else {
            return {
                id: index + 1,
                name: trimmedLine,
                quantity: ''
            }
        }
    })
}
</script>

<style scoped>
.ingredients-section label {
    display: block;
    font-weight: bold;
    margin-top: 25px;
    margin-bottom: 10px;
}

.ingredient-row {
    display: flex;
    gap: 0px;
    margin-bottom: 10px;
}

.ingredient-name,
.ingredient-qty {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    box-sizing: border-box;
    background-color: transparent;
    border: none;
    border-bottom: 1px solid #ccc;
    border-radius: 0;
}

.ingredient-name {
    flex: 2;
}

.ingredient-qty {
    flex: 1;
}
</style>