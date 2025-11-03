<template>
    <div class="image-preview">
        <span v-if="!hasValidImage" id="preview-text">No Image</span>
        <img
            v-else
            :src="imageUrl"
            :alt="altText"
            id="preview-image"
            @error="handleImageError"
            @load="handleImageLoad"
        />
    </div>
</template>

<script setup>
// import { computed } from 'vue'

const props = defineProps({
    imageUrl: {
        type: String,
        default: null
    },
    altText: {
        type: String,
        default: 'レシピ画像'
    }
})

const emit = defineEmits(['imageError', 'imageLoad'])

const hasValidImage = computed(() => {
    if (!props.imageUrl ||
        props.imageUrl === '' ||
        props.imageUrl === null ||
        props.imageUrl.includes('/images/no-image.png') ||
        props.imageUrl.includes('no-image.png')) {
        return false
    }
    return true
})

const handleImageError = (event) => {
    emit('imageError', event)
}

const handleImageLoad = (event) => {
    emit('imageLoad', event)
}
</script>

<style scoped>
.image-preview {
    width: 100%;
    aspect-ratio: 1 / 1;
    background-color: #f0f0f0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    height: 300px;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#preview-text {
    color: #999;
    font-size: 18px;
    font-weight: 500;
}

@media (max-width: 768px) {
    .image-preview {
        max-width: 280px;
        max-height: 280px;
    }
}
</style>