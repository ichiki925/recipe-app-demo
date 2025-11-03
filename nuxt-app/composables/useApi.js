export const useApi = () => {
    const { public: { apiBaseUrl } } = useRuntimeConfig()
    const { $auth } = useNuxtApp()

    const base = apiBaseUrl.replace(/\/+$/, '')
    const buildUrl = (p) => {
        let path = String(p || '').replace(/^\/+/, '')
        // 先頭が api/ でなければ自動で付与（/api/api を防ぐため base には /api を入れない）
        if (!/^api(\/|$)/i.test(path)) path = `api/${path}`
        return `${base}/${path}`
    }


    const buildHeaders = async (needAuth, isForm) => {
        const h = { Accept: 'application/json' }
        if (!isForm) h['Content-Type'] = 'application/json'
        if (needAuth) {
            if (!process.client) throw Object.assign(new Error('AUTH_ON_SERVER'), { status: 401 })
            // getIdToken() は失敗時があるので一応 try/catch（任意）
            let token = null
            try { token = await $auth.currentUser?.getIdToken() } catch {}
            if (!token) throw Object.assign(new Error('UNAUTHENTICATED'), { status: 401 })
            h['Authorization'] = `Bearer ${token}`
        }
        return h
    }

    const request = async (path, { method='GET', body, auth=false, headers, query } = {}) => {
        const isForm = body instanceof FormData
        const h = await buildHeaders(auth, isForm)
        const payload = body && !isForm && typeof body !== 'string' ? JSON.stringify(body) : body

        let url = buildUrl(path)
        if (query && Object.keys(query).length) {
            const qs = new URLSearchParams()
            for (const [k, v] of Object.entries(query)) {
                if (v !== undefined && v !== null && v !== '') qs.append(k, String(v))
            }
            const qstr = qs.toString()
            if (qstr) url += (url.includes('?') ? '&' : '?') + qstr
        }
        const res = await fetch(url, {
            method,
            headers: { ...h, ...(headers || {}) },
            body: payload,
            mode: 'cors',
            credentials: 'omit',
            redirect: 'error',
        })

        if (!res.ok) {
            const text = await res.text()
            const err = Object.assign(new Error(`HTTP ${res.status}`), { status: res.status })
            try { err.data = JSON.parse(text) } catch { err.data = text }
            throw err
        }
        const ct = res.headers.get('content-type') || ''
        return ct.includes('application/json') ? res.json() : res.text()
    }

    // 認証なし（公開API：一覧・詳細・検索など）
    const get  = (p, o) => request(p, { ...o, method: 'GET' })
    const post = (p, b, o) => request(p, { ...o, method: 'POST', body: b })
    const put  = (p, b, o) => request(p, { ...o, method: 'PUT',  body: b })
    const del  = (p, o)    => request(p, { ...o, method: 'DELETE' })

    // 認証あり（管理API & ユーザー操作：いいね・コメント投稿など）
    const getAuth  = (p, o) => request(p, { ...o, method: 'GET',    auth: true })
    const postAuth = (p, b, o) => request(p, { ...o, method: 'POST', body: b, auth: true })
    const putAuth  = (p, b, o) => request(p, { ...o, method: 'PUT',  body: b, auth: true })
    const delAuth  = (p, o) => request(p, { ...o, method: 'DELETE', auth: true })

    return { get, post, put, delete: del, getAuth, postAuth, putAuth, delAuth }
}
