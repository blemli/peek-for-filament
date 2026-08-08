const MARGIN = 16
const GAP = 8
const GRACE = 300

document.addEventListener('alpine:init', () => {
    window.Alpine.data('peekBox', () => ({
        levels: {},
        config: {},
        hoverTrigger: null,
        openTimer: null,
        closeTimer: null,
        longPressTimer: null,
        suppressClick: false,

        init() {
            this.config = JSON.parse(this.$el.dataset.peekConfig || '{}')

            document.addEventListener('pointerover', (e) => this.onPointerOver(e), { passive: true })
            document.addEventListener('keydown', (e) => e.key === 'Escape' && this.popTop())
            document.addEventListener('scroll', (e) => this.onScroll(e), { capture: true, passive: true })
            document.addEventListener('pointerdown', (e) => this.onPointerDown(e), { capture: true })
            document.documentElement.addEventListener('mouseleave', () => this.scheduleCloseAbove(-1))
            document.addEventListener('livewire:navigated', () => this.closeAll())

            if (this.config.longPress) {
                document.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: true })
                document.addEventListener('touchmove', () => this.cancelLongPress(), { passive: true })
                document.addEventListener('touchend', () => this.cancelLongPress(), { passive: true })
                document.addEventListener('click', (e) => this.onClick(e), { capture: true })
            }

            window.Livewire?.on('peek-denied', ({ level }) => {
                delete this.levels[level]
            })

            window.Livewire?.hook('commit', ({ succeed }) => {
                succeed(() => queueMicrotask(() => this.positionAll()))
            })
        },

        // --- state queries used by the blade template ---

        isOpen(level) {
            return !! this.levels[level]
        },

        hasContent(level, serverKey) {
            return serverKey !== '' && this.levels[level]?.key === serverKey
        },

        styleFor(level) {
            return this.levels[level]?.style ?? ''
        },

        // --- trigger discovery ---

        findTrigger(el) {
            let node = el

            while (node && node !== document.documentElement) {
                if (node.hasAttribute?.('data-peek-off')) {
                    return null
                }

                if (node.dataset?.peek) {
                    return { el: node, target: 'token:' + node.dataset.peek }
                }

                if (
                    this.config.anchors &&
                    node.tagName === 'A' &&
                    node.href &&
                    this.isPeekableAnchor(node)
                ) {
                    return { el: node, target: 'url:' + node.href }
                }

                node = node.parentElement
            }

            return null
        },

        isPeekableAnchor(node) {
            if (! node.href.startsWith(this.config.panelUrl)) return false
            if (node.href.split('#')[0] === location.href.split('#')[0]) return false

            return ! (this.config.ignoreSelectors ?? []).some((selector) => {
                try {
                    return node.closest(selector)
                } catch {
                    return false
                }
            })
        },

        levelForContext(el) {
            const panel = el.closest?.('[data-peek-panel]')

            return panel ? parseInt(panel.dataset.peekLevel, 10) : -1
        },

        // --- hover flow ---

        onPointerOver(event) {
            const el = event.target instanceof Element ? event.target : null
            if (! el || event.pointerType === 'touch') return

            const contextLevel = this.levelForContext(el)
            let trigger = this.findTrigger(el)

            if (trigger) {
                trigger.level = this.levelForContext(trigger.el) + 1

                if (trigger.level >= this.config.depth) trigger = null
            }

            if (trigger && this.hoverTrigger?.el !== trigger.el) {
                clearTimeout(this.openTimer)
                this.hoverTrigger = trigger
                this.openTimer = setTimeout(() => this.open(trigger), this.config.hoverDelay)
            }

            if (! trigger) {
                if (this.hoverTrigger && contextLevel === -1) {
                    clearTimeout(this.openTimer)
                    this.hoverTrigger = null
                }

                if (contextLevel >= 0) {
                    clearTimeout(this.openTimer)
                    this.hoverTrigger = null
                }
            }

            // Keep every level at or below the hovered context alive; anything
            // deeper closes after a grace period.
            const keepUpTo = trigger ? trigger.level : contextLevel
            this.cancelClose()
            this.scheduleCloseAbove(keepUpTo)
        },

        open(trigger) {
            const level = trigger.level
            const rect = trigger.el.getBoundingClientRect()

            Object.keys(this.levels).forEach((existing) => {
                if (parseInt(existing, 10) > level) delete this.levels[existing]
            })

            this.levels[level] = {
                key: this.hash(trigger.target),
                target: trigger.target,
                anchor: {
                    top: rect.top,
                    bottom: rect.bottom,
                    left: rect.left,
                    width: rect.width,
                },
                style: 'top: -9999px; left: -9999px',
            }

            this.$nextTick(() => this.position(level))
            window.Livewire?.dispatch('peek.open', {
                target: trigger.target,
                level: level,
                key: this.levels[level].key,
            })
        },

        // --- closing ---

        scheduleCloseAbove(keepUpTo) {
            const doomed = Object.keys(this.levels).some((level) => parseInt(level, 10) > keepUpTo)
            if (! doomed) return

            this.closeTimer = setTimeout(() => {
                Object.keys(this.levels).forEach((level) => {
                    if (parseInt(level, 10) > keepUpTo) delete this.levels[level]
                })

                window.Livewire?.dispatch('peek.close', { level: keepUpTo + 1 })
            }, GRACE)
        },

        cancelClose() {
            clearTimeout(this.closeTimer)
        },

        popTop() {
            const open = Object.keys(this.levels).map((level) => parseInt(level, 10))
            if (open.length === 0) return

            const top = Math.max(...open)
            delete this.levels[top]
            window.Livewire?.dispatch('peek.close', { level: top })
        },

        closeAll() {
            clearTimeout(this.openTimer)
            this.cancelClose()
            this.hoverTrigger = null

            if (Object.keys(this.levels).length === 0) return

            this.levels = {}
            window.Livewire?.dispatch('peek.close', { level: 0 })
        },

        onScroll(event) {
            if (event.target instanceof Element && event.target.closest('[data-peek-panel]')) return

            this.closeAll()
        },

        onPointerDown(event) {
            const el = event.target instanceof Element ? event.target : null
            if (el && el.closest('[data-peek-panel]')) return

            this.closeAll()
        },

        // --- touch / long-press ---

        onTouchStart(event) {
            const el = event.target instanceof Element ? event.target : null
            if (! el) return

            let trigger = this.findTrigger(el)
            if (! trigger) return

            trigger.level = this.levelForContext(trigger.el) + 1
            if (trigger.level >= this.config.depth) return

            this.longPressTimer = setTimeout(() => {
                this.suppressClick = true
                this.open(trigger)
            }, this.config.longPressDuration)
        },

        cancelLongPress() {
            clearTimeout(this.longPressTimer)
        },

        onClick(event) {
            if (! this.suppressClick) return

            this.suppressClick = false
            event.preventDefault()
            event.stopPropagation()
        },

        // --- positioning ---

        positionAll() {
            Object.keys(this.levels).forEach((level) => this.position(parseInt(level, 10)))
        },

        position(level) {
            const state = this.levels[level]
            if (! state) return

            const panel = this.$root.querySelector(`[data-peek-panel][data-peek-level="${level}"]`)
            if (! panel) return

            const width = panel.offsetWidth
            const height = panel.offsetHeight
            const anchor = state.anchor

            let left = anchor.left + anchor.width / 2 - width / 2
            left = Math.max(MARGIN, Math.min(left, window.innerWidth - width - MARGIN))

            let top = anchor.top - height - GAP

            if (top < MARGIN) {
                top = Math.min(anchor.bottom + GAP, window.innerHeight - height - MARGIN)
            }

            state.style = `top: ${Math.round(top)}px; left: ${Math.round(left)}px`
        },

        hash(value) {
            let hash = 5381

            for (let i = 0; i < value.length; i++) {
                hash = ((hash << 5) + hash + value.charCodeAt(i)) >>> 0
            }

            return hash.toString(36)
        },
    }))
})
