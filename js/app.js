(function () {
    const DEFAULT_LANG = 'ar';
    const STORAGE_KEY = 'trakmile_lang';

    let currentLang = localStorage.getItem(STORAGE_KEY) || DEFAULT_LANG;

    function getTranslations() {
        return window.translations || (typeof translations !== 'undefined' ? translations : null);
    }

    function t(key) {
        const dict = getTranslations();
        if (!dict) return key;
        return dict[currentLang]?.[key] ?? dict[DEFAULT_LANG]?.[key] ?? key;
    }

    function applyLanguage(lang) {
        currentLang = lang;
        localStorage.setItem(STORAGE_KEY, lang);

        document.documentElement.lang = lang;
        document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
        document.title = t('meta.title');

        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (key) el.textContent = t(key);
        });

        document.querySelectorAll('[data-i18n-html]').forEach(el => {
            const key = el.getAttribute('data-i18n-html');
            if (key) el.innerHTML = t(key);
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (key) el.placeholder = t(key);
        });

        const langBtn = document.getElementById('langToggle');
        if (langBtn) {
            langBtn.textContent = lang === 'ar' ? 'EN' : 'عربي';
            langBtn.title = lang === 'ar' ? 'Switch to English' : 'التبديل للعربية';
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function toggleLanguage() {
        applyLanguage(currentLang === 'ar' ? 'en' : 'ar');
    }

    // Quote Modal
    const modal = document.getElementById('quoteModal');
    const quoteForm = document.getElementById('quoteForm');
    const formMessage = document.getElementById('formMessage');

    function openModal() {
        if (!modal) return;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        if (formMessage) {
            formMessage.style.display = 'none';
            formMessage.className = 'form-message';
        }
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-open-quote]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            openModal();
        });
    });

    document.getElementById('closeModal')?.addEventListener('click', closeModal);
    document.getElementById('cancelModal')?.addEventListener('click', closeModal);

    modal?.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });

    quoteForm?.addEventListener('submit', async e => {
        e.preventDefault();
        const submitBtn = quoteForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        const formData = new FormData();
        formData.append('name', document.getElementById('quoteName').value.trim());
        formData.append('phone', document.getElementById('quotePhone').value.trim());
        formData.append('email', document.getElementById('quoteEmail').value.trim());
        formData.append('description', document.getElementById('quoteDescription').value.trim());

        try {
            const res = await fetch('api/submit_quote.php', {
                method: 'POST',
                body: formData,
            });
            const text = await res.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch {
                console.error('Invalid JSON response:', text);
                if (formMessage) {
                    formMessage.style.display = 'block';
                    formMessage.className = 'form-message error';
                    formMessage.textContent = text
                        ? t('modal.fatal') + ' ' + text.substring(0, 200)
                        : t('modal.error');
                }
                submitBtn.disabled = false;
                return;
            }

            if (!formMessage) return;

            formMessage.style.display = 'block';
            if (result.success) {
                formMessage.className = 'form-message success';
                formMessage.textContent = t('modal.success');
                quoteForm.reset();
                setTimeout(closeModal, 2500);
            } else {
                formMessage.className = 'form-message error';
                const detail = result.error ? `\n(${result.error})` : '';
                formMessage.textContent = (result.message === 'server_error'
                    ? t('modal.serverError')
                    : t('modal.validation')) + detail;
                if (result.error) console.error('Quote submit error:', result.error);
            }
        } catch {
            if (formMessage) {
                formMessage.style.display = 'block';
                formMessage.className = 'form-message error';
                formMessage.textContent = t('modal.error');
            }
        }

        submitBtn.disabled = false;
    });

    // Init
    document.getElementById('langToggle')?.addEventListener('click', toggleLanguage);
    if (getTranslations()) {
        applyLanguage(currentLang);
    } else {
        console.error('Trakmile i18n: translations not loaded');
    }

    // Header scroll
    const header = document.getElementById('header');
    window.addEventListener('scroll', () => {
        header?.classList.toggle('scrolled', window.scrollY > 20);
    });

    // Mobile menu
    const menuToggle = document.getElementById('menuToggle');
    const mobileNav = document.getElementById('mobileNav');
    menuToggle?.addEventListener('click', () => {
        const isOpen = mobileNav.classList.toggle('open');
        menuToggle.innerHTML = isOpen ? '<i data-lucide="x"></i>' : '<i data-lucide="menu"></i>';
        lucide.createIcons();
    });
    mobileNav?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileNav.classList.remove('open');
            menuToggle.innerHTML = '<i data-lucide="menu"></i>';
            lucide.createIcons();
        });
    });

    // Scroll reveal
    const revealObserver = new IntersectionObserver(entries => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => entry.target.classList.add('visible'), i * 80);
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

    // Animated counters
    const counterObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target = parseFloat(el.dataset.target);
            const suffix = el.dataset.suffix || '';
            const isDecimal = el.dataset.decimal === 'true';
            const duration = 1800;
            const start = performance.now();

            function animate(now) {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = target * eased;
                el.textContent = isDecimal
                    ? current.toFixed(1) + suffix
                    : Math.floor(current) + suffix;
                if (progress < 1) requestAnimationFrame(animate);
            }
            requestAnimationFrame(animate);
            counterObserver.unobserve(el);
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('.stat-value[data-target]').forEach(el => counterObserver.observe(el));

    // FAQ accordion
    document.querySelectorAll('.faq-item').forEach(item => {
        item.querySelector('.faq-question')?.addEventListener('click', () => {
            const wasOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
            if (!wasOpen) item.classList.add('open');
        });
    });
})();
