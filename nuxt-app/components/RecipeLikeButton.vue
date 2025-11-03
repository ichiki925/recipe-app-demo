<template>
    <div class="action-buttons">
        <template v-if="mounted">
            <button
                v-if="!isAdmin"
                class="icon-button"
                @click="handleToggleLike"
                :disabled="loading"
            >
                <i
                    :class="isLiked ? 'fas fa-heart heart-icon-filled' : 'far fa-heart heart-icon-outline'"
                ></i>
                <span class="like-count">{{ likeCount }}</span>
            </button>

            <!-- 管理者の場合は表示のみ -->
            <div v-else class="like-display">
                <i class="far fa-heart heart-icon"></i>
                <span class="like-count">{{ likeCount }}</span>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const mounted = ref(false)

onMounted(() => {
    mounted.value = true
})

const props = defineProps({
    isLiked: {
        type: Boolean,
        default: false
    },
    likeCount: {
        type: Number,
        default: 0
    },
    isAdmin: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['toggleLike'])

const handleToggleLike = () => {
    if (!props.loading) {
        emit('toggleLike')
    }
}
</script>

<style scoped>
.action-buttons {
    display: flex;
    justify-content: flex-start;
    width: 100%;
    margin-top: -20px;
}

.icon-button {
    background: none;
    border: none;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    font-size: 14px;
    padding-right: 15px;
    transition: opacity 0.2s;
}

.icon-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.like-display {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #333;
    justify-content: flex-start;
}

.like-count {
    font-size: 10px;
}

.icon-button .heart-icon-filled,
.like-display .heart-icon-filled {
    color: #dc3545 !important;
    font-size: 18px !important;
}

.icon-button .heart-icon-outline,
.like-display .heart-icon-outline {
    color: #666 !important;
    font-size: 18px !important;
}

.icon-button .heart-icon,
.like-display .heart-icon {
    font-size: 18px;
    color: #dc3545 !important;
}
</style>