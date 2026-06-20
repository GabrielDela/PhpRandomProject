(() => {
    'use strict';

    const config = window.GAME_CONFIG;
    let state = config.state;
    let pendingClicks = 0;
    let requestInFlight = false;
    let lastRender = performance.now();

    const elements = {
        count: document.querySelector('#cookieCount'),
        cps: document.querySelector('#cpsCount'),
        power: document.querySelector('#clickPower'),
        lifetime: document.querySelector('#lifetimeCount'),
        cookieButton: document.querySelector('#cookieButton'),
        stage: document.querySelector('#cookieStage'),
        producerList: document.querySelector('#producerList'),
        clickUpgrade: document.querySelector('#clickUpgrade'),
        clickLevel: document.querySelector('#clickLevel'),
        clickCost: document.querySelector('#clickCost'),
        reset: document.querySelector('#resetButton'),
        toast: document.querySelector('#toast'),
    };

    function formatNumber(value) {
        if (value < 1000) {
            return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: value < 10 ? 1 : 0 }).format(value);
        }

        const units = [
            [1e12, 'Bn'],
            [1e9, 'Md'],
            [1e6, 'M'],
            [1e3, 'k'],
        ];
        const [divisor, suffix] = units.find(([limit]) => value >= limit);
        return `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(value / divisor)} ${suffix}`;
    }

    function render() {
        elements.count.textContent = formatNumber(state.cookies);
        elements.cps.textContent = formatNumber(state.cps);
        elements.power.textContent = formatNumber(state.clickPower);
        elements.lifetime.textContent = formatNumber(state.lifetime);
        elements.clickLevel.textContent = `Niv. ${state.clickUpgrade.level}`;
        elements.clickCost.textContent = formatNumber(state.clickUpgrade.cost);
        elements.clickUpgrade.disabled = state.cookies < state.clickUpgrade.cost;

        for (const producer of Object.values(state.producers)) {
            const card = document.querySelector(`[data-producer="${producer.id}"]`);
            if (!card) continue;

            card.disabled = state.cookies < producer.cost;
            card.querySelector('[data-owned]').textContent = producer.owned;
            card.querySelector('[data-cost]').textContent = formatNumber(producer.cost);
        }
    }

    function buildShop() {
        elements.producerList.innerHTML = Object.values(state.producers).map((producer) => `
            <button class="upgrade-card" type="button" data-producer="${producer.id}">
                <span class="upgrade-icon">${producer.emoji}</span>
                <span class="upgrade-copy">
                    <span class="upgrade-name">${producer.name} <small>× <span data-owned>${producer.owned}</span></small></span>
                    <span class="upgrade-description">${producer.description}</span>
                    <span class="upgrade-meta">
                        <span class="upgrade-price"><span data-cost>${formatNumber(producer.cost)}</span> cookies</span>
                        <span>+${formatNumber(producer.cps)}/s</span>
                    </span>
                </span>
                <span class="upgrade-action">+</span>
            </button>
        `).join('');
    }

    async function api(action, data = {}) {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': config.csrf,
            },
            body: JSON.stringify({ action, ...data }),
        });
        const result = await response.json();

        if (result.state) state = result.state;
        if (!response.ok) throw new Error(result.error || 'Une miette a bloqué la machine.');
        if (result.message) showToast(result.message);
        return result;
    }

    async function flushClicks() {
        if (requestInFlight || pendingClicks === 0) return;

        requestInFlight = true;
        const count = Math.min(100, pendingClicks);
        pendingClicks -= count;

        try {
            const result = await api('click', { count });
            state = result.state;
            render();
        } catch (error) {
            pendingClicks += count;
            showToast(error.message, true);
        } finally {
            requestInFlight = false;
            if (pendingClicks > 0) flushClicks();
        }
    }

    function popCookie(event) {
        const rect = elements.stage.getBoundingClientRect();
        const x = (event.clientX || rect.left + rect.width / 2) - rect.left;
        const y = (event.clientY || rect.top + rect.height / 2) - rect.top;
        const pop = document.createElement('span');
        pop.className = 'click-pop';
        pop.textContent = `+${formatNumber(state.clickPower)}`;
        pop.style.left = `${x}px`;
        pop.style.top = `${y}px`;
        elements.stage.appendChild(pop);
        pop.addEventListener('animationend', () => pop.remove());
    }

    function showToast(message, isError = false) {
        elements.toast.textContent = message;
        elements.toast.classList.toggle('error', isError);
        elements.toast.classList.add('visible');
        window.clearTimeout(showToast.timer);
        showToast.timer = window.setTimeout(() => elements.toast.classList.remove('visible'), 2400);
    }

    elements.cookieButton.addEventListener('click', (event) => {
        pendingClicks++;
        state.cookies += state.clickPower;
        state.lifetime += state.clickPower;
        popCookie(event);
        render();
    });

    elements.clickUpgrade.addEventListener('click', async () => {
        await flushClicks();
        try {
            await api('buy_click');
        } catch (error) {
            showToast(error.message, true);
        }
        render();
    });

    elements.producerList.addEventListener('click', async (event) => {
        const card = event.target.closest('[data-producer]');
        if (!card) return;

        await flushClicks();
        try {
            await api('buy_producer', { id: card.dataset.producer });
        } catch (error) {
            showToast(error.message, true);
        }
        render();
    });

    elements.reset.addEventListener('click', async () => {
        if (!window.confirm('Repartir de zéro et vider le bocal ?')) return;

        pendingClicks = 0;
        try {
            await api('reset');
            buildShop();
            render();
        } catch (error) {
            showToast(error.message, true);
        }
    });

    function animate(now) {
        const elapsed = Math.min((now - lastRender) / 1000, 1);
        lastRender = now;
        state.cookies += state.cps * elapsed;
        state.lifetime += state.cps * elapsed;
        render();
        requestAnimationFrame(animate);
    }

    window.setInterval(flushClicks, 180);
    window.setInterval(async () => {
        if (pendingClicks > 0 || requestInFlight) return;
        try {
            await api('sync');
            render();
        } catch (error) {
            showToast(error.message, true);
        }
    }, 5000);

    buildShop();
    render();
    requestAnimationFrame(animate);
})();
