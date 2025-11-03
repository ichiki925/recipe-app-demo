<template>
    <div class="comment-section">
        <ul id="comment-list">
            <li
                v-for="comment in displayedComments"
                :key="comment.id"
                class="comment-item"
            >
                <img
                    v-if="getAvatarUrl(comment.user)"
                    :src="getAvatarUrl(comment.user)"
                    class="comment-avatar"
                    alt="avatar"
                >
                <i
                    v-else
                    class="fas fa-user comment-avatar-icon"
                ></i>
                <span class="username" :title="comment.user?.name || 'ユーザー'">
                    {{ truncateUsername(comment.user?.name || 'ユーザー') }}
                </span>
                <span class="comment-body">{{ comment.body || comment.content }}</span>
            </li>
        </ul>

        <div v-if="hasMoreComments" class="comment-toggle-section">
            <button
                v-if="!showAllComments"
                @click="showAllComments = true"
                class="comment-toggle-btn"
            >
                もっと見る ({{ remainingCount }}件)
            </button>
            <button
                v-else
                @click="showAllComments = false"
                class="comment-toggle-btn"
            >
                表示を折りたたむ
            </button>
        </div>

        <div v-if="!isAdmin" class="comment-wrapper">
            <textarea
                v-model="newComment"
                ref="commentTextarea"
                id="comment-box"
                class="auto-resize"
                :class="{ 'error': commentError }"
                placeholder="コメントを記入..."
                @input="handleCommentInput"
                :disabled="isSubmitting"
                maxlength="500"
            ></textarea>

            <div class="comment-counter">
                <span :class="{ 'warning': commentLength > 450, 'error': commentLength > 500 }">
                    {{ commentLength }}/500
                </span>
            </div>

            <div v-if="commentError" class="error-message">
                {{ commentError }}
            </div>

            <button
                type="button"
                class="send-button"
                :class="{ 'disabled': !!commentError || !newComment.trim() || isSubmitting }"
                :disabled="!!commentError || !newComment.trim() || isSubmitting"
                title="送信"
                @click="submitComment"
            >
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="far fa-paper-plane"></i>
            </button>
        </div>

        <div v-else class="admin-note">
            <i class="fas fa-info-circle"></i>
            管理者はコメント表示のみです
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'

const props = defineProps({
    comments: {
        type: Array,
        default: () => []
    },
    isAdmin: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['submitComment'])

const showAllComments = ref(false)
const newComment = ref('')
const commentTextarea = ref(null)
const commentError = ref('')
const isSubmitting = ref(false)

const displayedComments = computed(() => {
    if (showAllComments.value) {
        return [...props.comments]
    } else {
        return [...props.comments].slice(0, 3)
    }
})

const remainingCount = computed(() => {
    return Math.max(0, props.comments.length - 3)
})

const hasMoreComments = computed(() => {
    return props.comments.length > 3
})

const commentLength = computed(() => {
    return newComment.value.length
})

const getAvatarUrl = (user) => {
    if (!user || !user.avatar_path) {
        return null
    }

    if (user.avatar_path.startsWith('http://') || user.avatar_path.startsWith('https://')) {
        return user.avatar_path
    }

    if (user.avatar_path.startsWith('/storage/')) {
        return `http://localhost${user.avatar_path}`
    }

    const fileName = user.avatar_path.includes('/')
        ? user.avatar_path.split('/').pop()
        : user.avatar_path

    return `http://localhost/storage/avatars/${fileName}`
}

const truncateUsername = (username) => {
    if (!username) return 'ユーザー'
    return username.length > 10 ? username.substring(0, 10) + '...' : username
}

const validateComment = (comment) => {
    const trimmed = comment.trim()

    if (!trimmed) {
        return 'コメントを入力してください'
    }

    if (trimmed.length < 1) {
        return 'コメントは1文字以上で入力してください'
    }

    if (trimmed.length > 500) {
        return 'コメントは500文字以内で入力してください'
    }

    if (/(.)\1{9,}/.test(trimmed)) {
        return '同じ文字の連続は10文字までにしてください'
    }

    return null
}

const handleCommentInput = () => {
    commentError.value = ''
    autoResize()

    if (newComment.value.length > 500) {
        commentError.value = 'コメントは500文字以内で入力してください'
    }
}

const autoResize = () => {
    nextTick(() => {
        if (commentTextarea.value) {
            commentTextarea.value.style.height = 'auto'
            commentTextarea.value.style.height = commentTextarea.value.scrollHeight + 'px'
        }
    })
}

const submitComment = () => {
    const validationError = validateComment(newComment.value)
    if (validationError) {
        commentError.value = validationError
        return
    }

    if (isSubmitting.value) return
    isSubmitting.value = true

    emit('submitComment', {
        content: newComment.value.trim(),
        onSuccess: () => {
            newComment.value = ''
            commentError.value = ''
            autoResize()
            isSubmitting.value = false
        },
        onError: (error) => {
            commentError.value = error.message || 'コメントの送信に失敗しました'
            isSubmitting.value = false
        }
    })
}

defineExpose({
    autoResize
})
</script>

<style scoped>
.comment-section {
    width: 100%;
}

.comment-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.comment-avatar {
    object-fit: cover;
}

.comment-avatar-icon,
.comment-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    margin: 8px;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #eee;
    color: #666;
}

.username {
    margin-right: 2px;
    font-size: 10px;
    white-space: nowrap;
    max-width: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 600;
    color: #333;
    cursor: default;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
}

.username:hover {
    color: #666;
}

.comment-body {
    flex: 1;
    font-size: 12px;
    font-family: sans-serif;
    line-height: 1.4;
    word-wrap: break-word;
}

.comment-toggle-section {
    margin-top: 10px;
    margin-bottom: 10px;
    text-align: center;
}

.comment-toggle-btn {
    background: none;
    border: 1px solid #bbb;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 11px;
    color: #333;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
}

.comment-toggle-btn:hover {
    background-color: #f5f5f5;
    color: #333;
    border-color: #bbb;
}

.comment-wrapper {
    position: relative;
    width: 100%;
    display: inline-block;
}

#comment-box {
    width: 100%;
    padding: 10px 50px 10px 10px;
    resize: none;
    overflow: hidden;
    font-size: 14px;
    box-sizing: border-box;
    border-radius: 6px;
    border: 1px solid #aaa;
}

#comment-box.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
}

#comment-box:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.comment-counter {
    position: absolute;
    right: 50px;
    bottom: 12px;
    font-size: 10px;
    color: #666;
    pointer-events: none;
}

.comment-counter .warning {
    color: #ffc107;
}

.comment-counter .error {
    color: #dc3545;
    font-weight: bold;
}

.error-message {
    position: absolute;
    bottom: -20px;
    left: 0;
    font-size: 11px;
    color: #dc3545;
    background-color: #fff;
    padding: 2px 4px;
    border-radius: 3px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    z-index: 10;
    white-space: nowrap;
}

.send-button {
    position: absolute;
    right: 12px;
    bottom: 12px;
    background: none;
    border: none;
    font-size: 14px;
    cursor: pointer;
    transform: translateY(0);
}

.send-button:hover {
    color: #000;
}

.send-button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.send-button.disabled:hover {
    color: inherit;
}

.admin-note {
    background-color: #e3f2fd;
    color: #1976d2;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    text-align: center;
    margin: 15px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.auto-resize {
    overflow: hidden;
    resize: none;
}

.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>